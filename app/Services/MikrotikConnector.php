<?php

namespace App\Services;

use App\Models\Client as NusaClient;
use App\Models\DeviceName;
use App\Models\HotspotUser;
use App\Models\Nas;
use App\Models\Profile;
use App\Models\Router;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use RouterOS\Client;
use RouterOS\Exceptions\BadCredentialsException;
use RouterOS\Exceptions\ClientException;
use RouterOS\Exceptions\ConfigException;
use RouterOS\Exceptions\ConnectException;
use RouterOS\Exceptions\QueryException;
use RouterOS\Exceptions\StreamException;
use RouterOS\Query;
use Throwable;

class MikrotikConnector
{
    /**
     * Name of the hotspot server profile and user profile provision()
     * creates on every router — also the default MikroTik Group for new
     * voucher profiles, so their Mikrotik-Group reply matches it.
     */
    public const RADIUS_NUSA_PROFILE = 'radius-nusa';

    /**
     * "radius-nusa" hotspot server profile. login-by leaves out "cookie"
     * so a device can't skip RADIUS by replaying an old login cookie.
     */
    public const RADIUS_NUSA_SERVER_PROFILE = [
        'login-by' => 'http-chap',
        'use-radius' => 'yes',
        'radius-accounting' => 'yes',
        'radius-interim-update' => '00:02:00',
        'radius-mac-format' => 'XX:XX:XX:XX:XX:XX',
        'nas-port-type' => 'wireless-802.11',
    ];

    /**
     * "radius-nusa" hotspot user profile. Timeouts, shared users and rate
     * limit are left blank (see RADIUS_NUSA_USER_PROFILE_UNSET) — session
     * limits come from RADIUS per voucher profile instead.
     */
    public const RADIUS_NUSA_USER_PROFILE = [
        'address-pool' => 'none',
        'status-autorefresh' => '1m',
        'shared-users' => 'unlimited',
        'add-mac-cookie' => 'no',
        'open-status-page' => 'always',
        'transparent-proxy' => 'no',
    ];

    /**
     * Fields cleared on the "radius-nusa" user profile so they show blank
     * in WinBox. Setting e.g. idle-timeout=none is not the same thing.
     * (shared-users is the exception: unset means 1, blank is "unlimited".)
     */
    public const RADIUS_NUSA_USER_PROFILE_UNSET = [
        'session-timeout', 'idle-timeout', 'keepalive-timeout', 'rate-limit',
    ];

    private const SHARED_QUEUE_SUFFIX = ' shared';

    /**
     * Builds the ip-binding comment / simple queue name this class uses
     * to tag entries it manages: "{username}-{expires_at Y-m-d H:i}" (or
     * "-no-expiry" for a voucher with no set expiry), so an admin can
     * read who a binding belongs to and when it expires straight from
     * WinBox instead of an opaque internal tag.
     */
    private function bindingTag(HotspotUser $user): string
    {
        $expiry = $user->expires_at?->format('Y-m-d H:i') ?? 'no-expiry';

        return "{$user->username}-{$expiry}";
    }

    /**
     * Simple queue name for one bound device: bindingTag() plus the
     * device's MAC, e.g. "nusa-2026-10-15 14:17 12:EC:FB:3F:CB:A1". One queue
     * per device (queue names must be unique), so every device of a
     * voucher keeps its own rate limit.
     */
    private function queueName(HotspotUser $user, string $mac): string
    {
        return $this->bindingTag($user).' '.DeviceName::normalizeMac($mac);
    }

    /**
     * Parent queue name for a voucher on a "shared" bandwidth profile:
     * bindingTag() plus " shared", e.g. "nusa-2026-10-15 14:17 shared".
     */
    private function sharedQueueName(HotspotUser $user): string
    {
        return $this->bindingTag($user).self::SHARED_QUEUE_SUFFIX;
    }

    /**
     * Reverses sharedQueueName(): the username, or null if $name isn't a
     * parent queue this class created.
     */
    private function usernameFromSharedQueueName(string $name): ?string
    {
        if (! str_ends_with($name, self::SHARED_QUEUE_SUFFIX)) {
            return null;
        }

        return $this->usernameFromTag(substr($name, 0, -strlen(self::SHARED_QUEUE_SUFFIX)));
    }

    /**
     * RouterOS rate ("5M", "512k", "5000000") to bits per second.
     */
    private function rateBps(string $rate): int
    {
        if (! preg_match('/^\s*(\d+(?:\.\d+)?)\s*([kmg]?)/i', $rate, $m)) {
            return 0;
        }

        $multiplier = ['' => 1, 'k' => 1000, 'm' => 1000000, 'g' => 1000000000][strtolower($m[2])];

        return (int) round((float) $m[1] * $multiplier);
    }

    /**
     * Whether two "upload/download" rate pairs are equal, ignoring the
     * format — RouterOS prints "5M/5M" back as "5000000/5000000".
     */
    private function sameRates(string $a, string $b): bool
    {
        $a = array_map(fn ($rate) => $this->rateBps($rate), explode('/', $a));
        $b = array_map(fn ($rate) => $this->rateBps($rate), explode('/', $b));

        return $a === $b;
    }

    /**
     * Parses a queue name this class generated: [username, MAC], with a
     * null MAC for the older one-queue-per-user names (plain bindingTag()).
     * Returns null for queues an admin created by hand.
     *
     * @return array{0: string, 1: ?string}|null
     */
    private function parseQueueName(string $name): ?array
    {
        $mac = null;

        if (preg_match('/^(.+) ([0-9A-F]{2}(?::[0-9A-F]{2}){5})$/', $name, $matches)) {
            [$name, $mac] = [$matches[1], $matches[2]];
        }

        $username = $this->usernameFromTag($name);

        return $username === null ? null : [$username, $mac];
    }

    /**
     * Reverses bindingTag(): returns the username if $tag has the shape
     * this class generates, or null if it doesn't — which is how
     * syncActiveBindings()/unbindUsers() tell their own entries apart
     * from anything an admin created by hand.
     */
    private function usernameFromTag(string $tag): ?string
    {
        if (preg_match('/^(.+)-(\d{4}-\d{2}-\d{2}(?: \d{2}:\d{2})?|no-expiry)$/', $tag, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Connect to the router live via the MikroTik API and push identity +
     * RADIUS configuration. On success the router is marked verified and
     * registered in the `nas` table, same as the manual pairing flow did.
     *
     * @return array{ok: bool, message: string, steps: array<string, bool>}
     */
    public function provision(Router $router, string $serverIp): array
    {
        $steps = [
            'connect' => false,
            'radius' => false,
            'radius_incoming' => false,
            'hotspot_profile' => false,
            'server_profile_radius_nusa' => false,
            'user_profile_radius_nusa' => false,
        ];

        try {
            $client = new Client([
                'host' => $router->api_host,
                'user' => $router->api_user,
                'pass' => $router->api_pass,
                'port' => (int) $router->api_port,
                'timeout' => 8,
            ]);
            $steps['connect'] = true;

            foreach ($client->query((new Query('/radius/print'))->where('comment', 'nusa-hotspot'))->read() as $row) {
                if (isset($row['.id'])) {
                    $client->query((new Query('/radius/remove'))->equal('.id', $row['.id']))->read();
                }
            }
            $client->query(
                (new Query('/radius/add'))
                    ->equal('service', 'hotspot')
                    ->equal('address', $serverIp)
                    ->equal('secret', $router->radius_secret)
                    ->equal('timeout', '5000ms')
                    ->equal('comment', 'nusa-hotspot')
            )->read();
            $steps['radius'] = true;

            $client->query((new Query('/radius/incoming/set'))->equal('accept', 'yes')->equal('port', '3799'))->read();
            $steps['radius_incoming'] = true;

            // Dedicated RADIUS-enabled server profile + matching user
            // profile, so the admin can point a hotspot server at
            // "radius-nusa" without hand-editing the default ones.
            $this->ensureProfile($client, '/ip/hotspot/profile', self::RADIUS_NUSA_PROFILE, self::RADIUS_NUSA_SERVER_PROFILE);
            $steps['server_profile_radius_nusa'] = true;

            $this->ensureProfile($client, '/ip/hotspot/user/profile', self::RADIUS_NUSA_PROFILE, self::RADIUS_NUSA_USER_PROFILE, self::RADIUS_NUSA_USER_PROFILE_UNSET);
            $steps['user_profile_radius_nusa'] = true;

            // Only touch the profile(s) actually attached to a real hotspot
            // server — updating the first profile returned by the API can
            // silently miss the one in use if it isn't named "default".
            $hotspotServers = $client->query(new Query('/ip/hotspot/print'))->read();
            $activeProfileNames = array_unique(array_filter(array_column($hotspotServers, 'profile')));

            if (empty($activeProfileNames)) {
                $activeProfileNames = ['default'];
            }

            $profiles = $client->query(new Query('/ip/hotspot/profile/print'))->read();
            $configuredAny = false;

            foreach ($profiles as $profile) {
                if (! in_array($profile['name'] ?? null, $activeProfileNames, true)) {
                    continue;
                }

                $client->query(
                    (new Query('/ip/hotspot/profile/set'))
                        ->equal('.id', $profile['.id'])
                        ->equal('use-radius', 'yes')
                        ->equal('radius-accounting', 'yes')
                        ->equal('radius-interim-update', '00:02:00')
                        ->equal('radius-mac-format', 'XX:XX:XX:XX:XX:XX')
                        ->equal('nas-port-type', 'wireless-802.11')
                )->read();
                $configuredAny = true;
            }

            $steps['hotspot_profile'] = $configuredAny;

            try {
                $board = $client->query(new Query('/system/routerboard/print'))->read();
                if (! empty($board[0]['serial-number'])) {
                    $router->board_serial = $board[0]['serial-number'];
                }
            } catch (Throwable) {
                // Best-effort only — not every device/permission set exposes this.
            }

            try {
                $identity = $client->query(new Query('/system/identity/print'))->read();
                if (! empty($identity[0]['name'])) {
                    $router->name = $identity[0]['name'];
                }
            } catch (Throwable) {
                // Best-effort only — keep the existing name if this fails.
            }

            Nas::updateOrCreate(
                ['nasname' => $router->api_host],
                [
                    'client_id' => $router->client_id,
                    'enabled' => 1,
                    'shortname' => $router->nas_identifier,
                    'type' => 'other',
                    'secret' => $router->radius_secret,
                    'description' => $router->name,
                ],
            );

            $router->nas_ip = $router->api_host;
            $router->status = 'verified';
            $router->verified_at = now();
            $router->pairing_code = null;
            $router->save();

            return ['ok' => true, 'message' => 'Router berhasil terhubung dan dikonfigurasi otomatis.', 'steps' => $steps];
        } catch (BadCredentialsException) {
            return $this->fail($steps, 'Username atau password API salah.');
        } catch (ConnectException) {
            return $this->fail($steps, "Tidak bisa terhubung ke {$router->api_host}:{$router->api_port}. Pastikan IP benar, router menyala, dan API service aktif (\"/ip service enable api\").");
        } catch (ConfigException $e) {
            return $this->fail($steps, 'Konfigurasi koneksi tidak valid: '.$e->getMessage());
        } catch (QueryException|ClientException|StreamException $e) {
            return $this->fail($steps, 'Perintah ke router gagal: '.$e->getMessage());
        } catch (Throwable $e) {
            return $this->fail($steps, 'Gagal terhubung: '.$e->getMessage());
        }
    }

    /**
     * Lightweight reachability check used by the heartbeat scheduler —
     * connects and reads the System Identity, without touching the
     * router's configuration. Returns the identity name when reachable
     * (empty string if the router reports none), or null when it isn't.
     */
    public function ping(Router $router, int $timeout = 5): ?string
    {
        if (blank($router->api_host)) {
            return null;
        }

        try {
            $client = new Client([
                'host' => $router->api_host,
                'user' => $router->api_user,
                'pass' => $router->api_pass,
                'port' => (int) $router->api_port,
                'timeout' => $timeout,
                'attempts' => 1,
            ]);

            $identity = $client->query(new Query('/system/identity/print'))->read();

            return trim($identity[0]['name'] ?? '');
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Keep the stored router name (and its NAS description) in step with
     * the router's System Identity, so renaming it in WinBox shows up here
     * without a manual "Konfigurasi Ulang". Returns true if it changed.
     */
    public function syncIdentity(Router $router, ?string $identity): bool
    {
        if (blank($identity) || $identity === $router->name) {
            return false;
        }

        $router->update(['name' => $identity]);

        if ($router->nas_ip) {
            Nas::where('nasname', $router->nas_ip)->update(['description' => $identity]);
        }

        return true;
    }

    /**
     * Disconnect a live hotspot session on the router, matched by its
     * framed IP address. `/ip hotspot active` has no session-id field to
     * correlate against RADIUS's Acct-Session-Id, and matching by username
     * breaks if the user was renamed mid-session (the router keeps
     * reporting whatever username the device originally authenticated
     * with) — the IP address is stable for the life of the session and
     * unique per active client on a given router, so it's used instead.
     *
     * @return array{ok: bool, message: string}
     */
    public function killHotspotSession(Router $router, string $ipAddress): array
    {
        if (blank($router->api_host)) {
            return ['ok' => false, 'message' => 'Router belum terhubung via API (api_host kosong).'];
        }

        try {
            $client = new Client([
                'host' => $router->api_host,
                'user' => $router->api_user,
                'pass' => $router->api_pass,
                'port' => (int) $router->api_port,
                'timeout' => 8,
            ]);

            $active = $client->query(
                (new Query('/ip/hotspot/active/print'))->where('address', $ipAddress)
            )->read();

            $id = $active[0]['.id'] ?? null;

            if (! $id) {
                return ['ok' => false, 'message' => 'Sesi tidak ditemukan di router, mungkin sudah terputus.'];
            }

            $client->query((new Query('/ip/hotspot/active/remove'))->equal('.id', $id))->read();

            $macAddress = $active[0]['mac-address'] ?? null;

            if ($macAddress) {
                $cookies = $client->query(
                    (new Query('/ip/hotspot/cookie/print'))->where('mac-address', $macAddress)
                )->read();

                foreach ($cookies as $cookie) {
                    if (isset($cookie['.id'])) {
                        $client->query((new Query('/ip/hotspot/cookie/remove'))->equal('.id', $cookie['.id']))->read();
                    }
                }
            }

            return ['ok' => true, 'message' => 'Sesi berhasil diputus.'];
        } catch (BadCredentialsException) {
            return ['ok' => false, 'message' => 'Username atau password API router salah.'];
        } catch (ConnectException) {
            return ['ok' => false, 'message' => "Tidak bisa terhubung ke router {$router->api_host}:{$router->api_port}."];
        } catch (ConfigException|QueryException|ClientException|StreamException $e) {
            return ['ok' => false, 'message' => 'Perintah ke router gagal: '.$e->getMessage()];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Gagal terhubung ke router: '.$e->getMessage()];
        }
    }

    /**
     * Keep the router's hotspot cookie lifetime in step with the client's
     * own settings: the cookie ("remember me" auto-login) must never
     * expire sooner than the longest active profile's validity, or users
     * get forced to log back in before their voucher actually runs out.
     * MikroTik has no per-user cookie duration, so this pushes the
     * longest active profile's validity as one router-wide value.
     * Best-effort — called after profiles are created/edited/deleted.
     */
    public function syncCookieLifetimeForClient(NusaClient $client): void
    {
        $maxDays = $client->profiles()
            ->where('is_active', true)
            ->whereNotNull('validity_value')
            ->whereNotNull('validity_unit')
            ->get()
            ->map(fn (Profile $profile) => $this->validityDays($profile))
            ->max();

        if (! $maxDays) {
            return;
        }

        foreach ($client->routers()->where('status', 'verified')->get() as $router) {
            try {
                $this->setCookieLifetime($router, $maxDays);
            } catch (Throwable) {
                // Best-effort — router may be offline; next profile save retries.
            }
        }
    }

    private function validityDays(Profile $profile): int
    {
        return match ($profile->validity_unit) {
            'hour' => max(1, (int) ceil($profile->validity_value / 24)),
            'month' => $profile->validity_value * 30,
            default => $profile->validity_value,
        };
    }

    /**
     * @return array{ok: bool, message: string}
     */
    public function setCookieLifetime(Router $router, int $days): array
    {
        if (blank($router->api_host)) {
            return ['ok' => false, 'message' => 'Router belum terhubung via API (api_host kosong).'];
        }

        try {
            $client = new Client([
                'host' => $router->api_host,
                'user' => $router->api_user,
                'pass' => $router->api_pass,
                'port' => (int) $router->api_port,
                'timeout' => 8,
            ]);

            // Same "only touch profiles attached to a real hotspot server"
            // rule as provision() — see the comment there.
            $hotspotServers = $client->query(new Query('/ip/hotspot/print'))->read();
            $activeProfileNames = array_unique(array_filter(array_column($hotspotServers, 'profile')));

            if (empty($activeProfileNames)) {
                $activeProfileNames = ['default'];
            }

            $profiles = $client->query(new Query('/ip/hotspot/profile/print'))->read();
            $updated = false;

            foreach ($profiles as $profile) {
                if (! in_array($profile['name'] ?? null, $activeProfileNames, true)) {
                    continue;
                }

                $client->query(
                    (new Query('/ip/hotspot/profile/set'))
                        ->equal('.id', $profile['.id'])
                        ->equal('http-cookie-lifetime', "{$days}d")
                )->read();
                $updated = true;
            }

            return $updated
                ? ['ok' => true, 'message' => "Cookie lifetime diset ke {$days} hari."]
                : ['ok' => false, 'message' => 'Tidak ada hotspot profile aktif yang cocok.'];
        } catch (BadCredentialsException) {
            return ['ok' => false, 'message' => 'Username atau password API router salah.'];
        } catch (ConnectException) {
            return ['ok' => false, 'message' => "Tidak bisa terhubung ke router {$router->api_host}:{$router->api_port}."];
        } catch (ConfigException|QueryException|ClientException|StreamException $e) {
            return ['ok' => false, 'message' => 'Perintah ke router gagal: '.$e->getMessage()];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Gagal terhubung ke router: '.$e->getMessage()];
        }
    }

    /**
     * Bypass the hotspot login for every device that's currently active
     * and still within its own voucher's validity: adds an ip-binding
     * (type=bypassed, pinned to the device's current IP) so it never has
     * to log in again, and a simple queue on that same IP so it keeps
     * getting its profile's rate limit even though it's skipping the
     * hotspot auth pipeline that normally applies it. Idempotent — safe
     * to run on a short schedule against already-bound devices.
     *
     * @return array{ok: bool, message: string}
     */
    public function syncActiveBindings(Router $router): array
    {
        if (blank($router->api_host)) {
            return ['ok' => false, 'message' => 'Router belum terhubung via API (api_host kosong).'];
        }

        try {
            $client = new Client([
                'host' => $router->api_host,
                'user' => $router->api_user,
                'pass' => $router->api_pass,
                'port' => (int) $router->api_port,
                'timeout' => 8,
            ]);

            $active = $client->query(new Query('/ip/hotspot/active/print'))->read();

            $boundByMac = [];
            $boundCount = [];
            foreach ($client->query(new Query('/ip/hotspot/ip-binding/print'))->read() as $binding) {
                $mac = strtoupper($binding['mac-address'] ?? '');
                $bindingUser = $this->usernameFromTag($binding['comment'] ?? '');
                if ($mac && $bindingUser !== null) {
                    $boundByMac[$mac] = $binding;
                    $boundCount[$bindingUser] = ($boundCount[$bindingUser] ?? 0) + 1;
                }
            }

            $queueByMac = [];
            $legacyQueues = [];
            foreach ($client->query(new Query('/queue/simple/print'))->read() as $queue) {
                $parsed = $this->parseQueueName($queue['name'] ?? '');
                if ($parsed === null) {
                    continue;
                }

                if ($parsed[1] !== null) {
                    $queueByMac[$parsed[1]] = $queue;
                } else {
                    $legacyQueues[] = $queue + ['username' => $parsed[0]];
                }
            }

            $usernames = array_unique(array_merge(
                array_filter(array_column($active, 'user')),
                array_keys($boundCount),
            ));

            if (empty($usernames)) {
                // Still drop shared parents left behind by the last device
                // going away, or they would linger until someone logs in.
                $this->syncSharedQueues($client, collect());

                return ['ok' => true, 'message' => 'Tidak ada sesi aktif.'];
            }

            $users = HotspotUser::whereIn('username', $usernames)->with('profile')->get()->keyBy('username');
            $handledMacs = [];

            foreach ($active as $session) {
                $username = $session['user'] ?? null;
                $mac = strtoupper($session['mac-address'] ?? '');
                $ip = $session['address'] ?? null;

                if (! $username || ! $mac || ! $ip) {
                    continue;
                }

                $user = $users->get($username);

                if (! $user || $user->status === 'disabled' || ($user->expires_at && $user->expires_at->isPast())) {
                    continue;
                }

                $tag = $this->bindingTag($user);
                $existingBinding = $boundByMac[$mac] ?? null;
                $handledMacs[$mac] = true;

                if (! $existingBinding) {
                    // A bypassed device has no open RADIUS session, so
                    // RADIUS Simultaneous-Use can't see it — enforce the
                    // profile's Shared limit here instead, counting bound
                    // devices. Over the limit: drop this login rather
                    // than bind it.
                    $limit = (int) ($user->profile?->simultaneous_use ?? 0);
                    if ($limit > 0 && ($boundCount[$username] ?? 0) >= $limit) {
                        if (isset($session['.id'])) {
                            $client->query((new Query('/ip/hotspot/active/remove'))->equal('.id', $session['.id']))->read();
                        }

                        continue;
                    }

                    $boundCount[$username] = ($boundCount[$username] ?? 0) + 1;
                    Cache::forget("mikrotik:bypassed-hosts:{$router->id}");

                    $client->query(
                        (new Query('/ip/hotspot/ip-binding/add'))
                            ->equal('mac-address', $mac)
                            ->equal('to-address', $ip)
                            ->equal('type', 'bypassed')
                            ->equal('comment', $tag)
                    )->read();
                } elseif (($existingBinding['comment'] ?? null) !== $tag) {
                    // Keeps the comment's expiry date current across renewals.
                    $client->query(
                        (new Query('/ip/hotspot/ip-binding/set'))
                            ->equal('.id', $existingBinding['.id'])
                            ->equal('comment', $tag)
                    )->read();
                }

                $profile = $user->profile;

                if (! $profile || ! $profile->rate_up || ! $profile->rate_down) {
                    continue;
                }

                $target = "{$ip}/32";
                $maxLimit = "{$profile->rate_up}/{$profile->rate_down}";
                $queueName = $this->queueName($user, $mac);
                $existingQueue = $queueByMac[$mac] ?? null;

                // Adopt an old one-queue-per-user entry already pointing at
                // this device's IP (renamed below) instead of adding a
                // second queue on the same target.
                if (! $existingQueue) {
                    foreach ($legacyQueues as $i => $legacy) {
                        if ($legacy['username'] === $username && ($legacy['target'] ?? null) === $target) {
                            $existingQueue = $legacy;
                            unset($legacyQueues[$i]);
                            break;
                        }
                    }
                }

                if (! $existingQueue) {
                    // keepQueuesOnTop() below moves it above the hotspot's
                    // catch-all queue. No place-before here: an .id read at
                    // the start of the sync can be stale by now (RouterOS
                    // re-creates its dynamic hs-<server> queue), failing the
                    // add and leaving the device bound with no rate limit.
                    $client->query(
                        (new Query('/queue/simple/add'))
                            ->equal('name', $queueName)
                            ->equal('target', $target)
                            ->equal('max-limit', $maxLimit)
                    )->read();
                } elseif (
                    ($existingQueue['name'] ?? null) !== $queueName
                    || ($existingQueue['target'] ?? null) !== $target
                    || ($existingQueue['max-limit'] ?? null) !== $maxLimit
                ) {
                    $client->query(
                        (new Query('/queue/simple/set'))
                            ->equal('.id', $existingQueue['.id'])
                            ->equal('name', $queueName)
                            ->equal('target', $target)
                            ->equal('max-limit', $maxLimit)
                    )->read();
                }
            }

            // Bypassed devices never show up in /ip/hotspot/active, so the
            // loop above can't reach them: keep their binding comment and
            // queue name current here (voucher renewals, tag format changes).
            foreach ($boundByMac as $mac => $binding) {
                if (isset($handledMacs[$mac])) {
                    continue;
                }

                $user = $users->get($this->usernameFromTag($binding['comment'] ?? ''));

                if (! $user) {
                    continue;
                }

                $tag = $this->bindingTag($user);

                if (($binding['comment'] ?? null) !== $tag) {
                    $client->query(
                        (new Query('/ip/hotspot/ip-binding/set'))
                            ->equal('.id', $binding['.id'])
                            ->equal('comment', $tag)
                    )->read();
                }

                $queue = $queueByMac[$mac] ?? null;
                $queueName = $this->queueName($user, $mac);
                $profile = $user->profile;

                if ($queue && ($queue['name'] ?? null) !== $queueName) {
                    $client->query(
                        (new Query('/queue/simple/set'))
                            ->equal('.id', $queue['.id'])
                            ->equal('name', $queueName)
                    )->read();
                } elseif (! $queue && ! empty($binding['to-address']) && $profile?->rate_up && $profile?->rate_down) {
                    // A bound device has left /ip/hotspot/active for good, so
                    // if its queue was never created (or got removed) this is
                    // the only place left to give it its rate limit back.
                    $client->query(
                        (new Query('/queue/simple/add'))
                            ->equal('name', $queueName)
                            ->equal('target', "{$binding['to-address']}/32")
                            ->equal('max-limit', "{$profile->rate_up}/{$profile->rate_down}")
                    )->read();
                }
            }

            $this->syncSharedQueues($client, $users);
            $this->keepQueuesOnTop($client);

            return ['ok' => true, 'message' => 'Sinkronisasi binding selesai.'];
        } catch (BadCredentialsException) {
            return ['ok' => false, 'message' => 'Username atau password API router salah.'];
        } catch (ConnectException) {
            return ['ok' => false, 'message' => "Tidak bisa terhubung ke router {$router->api_host}:{$router->api_port}."];
        } catch (ConfigException|QueryException|ClientException|StreamException $e) {
            return ['ok' => false, 'message' => 'Perintah ke router gagal: '.$e->getMessage()];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Gagal terhubung ke router: '.$e->getMessage()];
        }
    }

    /**
     * Bandwidth mode "shared": hang every device queue of a voucher under
     * one parent queue capped at the profile's rate, so its devices share
     * that rate instead of each getting it in full. Each device is
     * guaranteed an equal slice (limit-at = rate / Shared) and may borrow
     * up to the full rate while the others are idle. Vouchers on a
     * per-device profile get their device queues detached again and any
     * leftover parent removed.
     *
     * @param  Collection<string, HotspotUser>  $users
     */
    private function syncSharedQueues(Client $client, Collection $users): void
    {
        $parents = [];
        $children = [];
        foreach ($client->query(new Query('/queue/simple/print'))->read() as $position => $queue) {
            $name = $queue['name'] ?? '';
            $queue['position'] = $position;

            if (($username = $this->usernameFromSharedQueueName($name)) !== null) {
                $parents[$username] = $queue;
            } elseif (($parsed = $this->parseQueueName($name)) !== null && $parsed[1] !== null) {
                $children[$parsed[0]][] = $queue;
            }
        }

        foreach ($children as $username => $queues) {
            $user = $users->get($username);
            $parent = $parents[$username] ?? null;
            unset($parents[$username]);

            if (! $user) {
                continue;
            }

            $profile = $user->profile;

            if (! $profile || $profile->bandwidth_mode !== 'shared' || ! $profile->rate_up || ! $profile->rate_down) {
                foreach ($queues as $queue) {
                    if (($queue['parent'] ?? 'none') !== 'none') {
                        $client->query(
                            (new Query('/queue/simple/set'))
                                ->equal('.id', $queue['.id'])
                                ->equal('parent', 'none')
                                ->equal('limit-at', '0/0')
                        )->read();
                    }
                }

                if ($parent) {
                    $client->query((new Query('/queue/simple/remove'))->equal('.id', $parent['.id']))->read();
                }

                continue;
            }

            $parentName = $this->sharedQueueName($user);
            $maxLimit = "{$profile->rate_up}/{$profile->rate_down}";
            $targets = array_column($queues, 'target');
            sort($targets);

            if (! $parent) {
                // Keep the parent above its device queues, like WinBox
                // shows a queue tree.
                $client->query(
                    (new Query('/queue/simple/add'))
                        ->equal('name', $parentName)
                        ->equal('target', implode(',', $targets))
                        ->equal('max-limit', $maxLimit)
                        ->equal('place-before', $queues[0]['.id'])
                )->read();
            } else {
                $parentTargets = explode(',', $parent['target'] ?? '');
                sort($parentTargets);

                if (
                    ($parent['name'] ?? null) !== $parentName
                    || $parentTargets !== $targets
                    || ! $this->sameRates($parent['max-limit'] ?? '', $maxLimit)
                ) {
                    $client->query(
                        (new Query('/queue/simple/set'))
                            ->equal('.id', $parent['.id'])
                            ->equal('name', $parentName)
                            ->equal('target', implode(',', $targets))
                            ->equal('max-limit', $maxLimit)
                    )->read();
                }

                if ($parent['position'] > $queues[0]['position']) {
                    $client->query(
                        (new Query('/queue/simple/move'))
                            ->equal('numbers', $parent['.id'])
                            ->equal('destination', $queues[0]['.id'])
                    )->read();
                }
            }

            $devices = max(1, (int) $profile->simultaneous_use);
            $limitAt = intdiv($this->rateBps($profile->rate_up), $devices).'/'.intdiv($this->rateBps($profile->rate_down), $devices);

            foreach ($queues as $queue) {
                if (($queue['parent'] ?? 'none') !== $parentName || ! $this->sameRates($queue['limit-at'] ?? '', $limitAt)) {
                    $client->query(
                        (new Query('/queue/simple/set'))
                            ->equal('.id', $queue['.id'])
                            ->equal('parent', $parentName)
                            ->equal('limit-at', $limitAt)
                    )->read();
                }
            }
        }

        // Parents whose device queues are all gone.
        foreach ($parents as $parent) {
            $client->query((new Query('/queue/simple/remove'))->equal('.id', $parent['.id']))->read();
        }
    }

    /**
     * Move every per-device queue this class manages above the first queue
     * it doesn't — typically the hotspot's dynamic "hs-<server>" queue on
     * the whole bridge, which would otherwise catch the device's traffic
     * first (simple queues match top-down) and skip its rate limit. That
     * dynamic queue can be re-created on top (e.g. after a reboot), hence
     * re-checked on every sync.
     */
    private function keepQueuesOnTop(Client $client): void
    {
        $firstForeignId = null;

        foreach ($client->query(new Query('/queue/simple/print'))->read() as $queue) {
            $ours = ($this->parseQueueName($queue['name'] ?? '')[1] ?? null) !== null
                || $this->usernameFromSharedQueueName($queue['name'] ?? '') !== null;

            if (! $ours) {
                $firstForeignId ??= $queue['.id'] ?? null;
            } elseif ($firstForeignId && isset($queue['.id'])) {
                $client->query(
                    (new Query('/queue/simple/move'))
                        ->equal('numbers', $queue['.id'])
                        ->equal('destination', $firstForeignId)
                )->read();
            }
        }
    }

    /**
     * Devices currently riding a bypass ip-binding this class created —
     * read from the hotspot host table, which carries the binding's
     * comment. A host idle longer than $maxIdle seconds counts as gone,
     * since the hotspot server may be set to never expire hosts. Cached
     * briefly; an unreachable router just yields none.
     *
     * @return array<int, array{username: string, mac: string, ip: string, uptime: int, bytes: int}>
     */
    public function bypassedHosts(Router $router, int $maxIdle = 300): array
    {
        if (blank($router->api_host)) {
            return [];
        }

        return Cache::remember("mikrotik:bypassed-hosts:{$router->id}", 20, function () use ($router, $maxIdle) {
            try {
                $client = new Client([
                    'host' => $router->api_host,
                    'user' => $router->api_user,
                    'pass' => $router->api_pass,
                    'port' => (int) $router->api_port,
                    'timeout' => 4,
                    'attempts' => 1,
                ]);

                $hosts = $client->query(new Query('/ip/hotspot/host/print'))->read();
            } catch (Throwable) {
                return [];
            }

            $devices = [];
            foreach ($hosts as $host) {
                $username = $this->usernameFromTag($host['comment'] ?? '');

                if (
                    ($host['bypassed'] ?? '') !== 'true'
                    || $username === null
                    || $this->durationSeconds($host['idle-time'] ?? '') > $maxIdle
                ) {
                    continue;
                }

                $devices[] = [
                    'username' => $username,
                    'mac' => DeviceName::normalizeMac($host['mac-address'] ?? ''),
                    'ip' => $host['address'] ?? '',
                    'uptime' => $this->durationSeconds($host['uptime'] ?? ''),
                    'bytes' => (int) ($host['bytes-in'] ?? 0) + (int) ($host['bytes-out'] ?? 0),
                ];
            }

            return $devices;
        });
    }

    /**
     * Take a bypassed device offline: remove this class's ip-binding for
     * that MAC (only if it belongs to $username), the simple queue pinned
     * to its IP — left behind, it would throttle whichever device DHCP
     * hands that IP to next — and its hotspot host entry, so the device
     * lands back on the login page. If the user has other bound devices,
     * syncActiveBindings() recreates a queue for them on its next run.
     *
     * @return array{ok: bool, message: string}
     */
    public function disconnectBypassedHost(Router $router, string $username, string $mac): array
    {
        if (blank($router->api_host)) {
            return ['ok' => false, 'message' => 'Router belum terhubung via API (api_host kosong).'];
        }

        $mac = DeviceName::normalizeMac($mac);

        try {
            $client = new Client([
                'host' => $router->api_host,
                'user' => $router->api_user,
                'pass' => $router->api_pass,
                'port' => (int) $router->api_port,
                'timeout' => 8,
            ]);

            $removed = false;
            $targets = [];
            foreach ($client->query(new Query('/ip/hotspot/ip-binding/print'))->read() as $binding) {
                if (
                    DeviceName::normalizeMac($binding['mac-address'] ?? '') === $mac
                    && $this->usernameFromTag($binding['comment'] ?? '') === $username
                    && isset($binding['.id'])
                ) {
                    $client->query((new Query('/ip/hotspot/ip-binding/remove'))->equal('.id', $binding['.id']))->read();
                    $removed = true;

                    if (! empty($binding['to-address'])) {
                        $targets[] = "{$binding['to-address']}/32";
                    }
                }
            }

            if (! $removed) {
                return ['ok' => false, 'message' => 'Perangkat tidak ditemukan di router, mungkin sudah terputus.'];
            }

            foreach ($client->query(new Query('/queue/simple/print'))->read() as $queue) {
                $parsed = $this->parseQueueName($queue['name'] ?? '');

                if (
                    $parsed !== null
                    && $parsed[0] === $username
                    && ($parsed[1] === $mac || ($parsed[1] === null && in_array($queue['target'] ?? '', $targets, true)))
                    && isset($queue['.id'])
                ) {
                    $client->query((new Query('/queue/simple/remove'))->equal('.id', $queue['.id']))->read();
                }
            }

            foreach ($client->query(new Query('/ip/hotspot/host/print'))->read() as $host) {
                if (DeviceName::normalizeMac($host['mac-address'] ?? '') === $mac && isset($host['.id'])) {
                    $client->query((new Query('/ip/hotspot/host/remove'))->equal('.id', $host['.id']))->read();
                }
            }

            // Shrink the voucher's shared parent queue to the devices left,
            // or remove it with the last one.
            $this->syncSharedQueues(
                $client,
                HotspotUser::where('username', $username)->with('profile')->get()->keyBy('username'),
            );

            Cache::forget("mikrotik:bypassed-hosts:{$router->id}");

            return ['ok' => true, 'message' => 'Perangkat berhasil diputus.'];
        } catch (BadCredentialsException) {
            return ['ok' => false, 'message' => 'Username atau password API router salah.'];
        } catch (ConnectException) {
            return ['ok' => false, 'message' => "Tidak bisa terhubung ke router {$router->api_host}:{$router->api_port}."];
        } catch (ConfigException|QueryException|ClientException|StreamException $e) {
            return ['ok' => false, 'message' => 'Perintah ke router gagal: '.$e->getMessage()];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Gagal terhubung ke router: '.$e->getMessage()];
        }
    }

    /**
     * RouterOS duration ("1w2d3h4m5s", "39m21s", or "01:02:03") to seconds.
     */
    private function durationSeconds(string $value): int
    {
        if (preg_match('/^(\d+):(\d+):(\d+)$/', $value, $m)) {
            return (int) $m[1] * 3600 + (int) $m[2] * 60 + (int) $m[3];
        }

        $units = ['w' => 604800, 'd' => 86400, 'h' => 3600, 'm' => 60, 's' => 1, 'ms' => 0];
        preg_match_all('/(\d+)(ms|w|d|h|m|s)/', $value, $matches, PREG_SET_ORDER);

        $seconds = 0;
        foreach ($matches as [, $amount, $unit]) {
            $seconds += (int) $amount * $units[$unit];
        }

        return $seconds;
    }

    /**
     * Remove the ip-binding and simple queue this class created for a
     * username — called whenever a single user is purged, expired, or
     * deleted. For more than one username against the same router, call
     * unbindUsers() instead: this fetches the full binding/queue list on
     * every call, so looping it per-user re-fetches those lists once per
     * user instead of once per router.
     *
     * @return array{ok: bool, message: string}
     */
    public function unbindUser(Router $router, string $username): array
    {
        return $this->unbindUsers($router, [$username]);
    }

    /**
     * Batch form of unbindUser() — fetches the ip-binding and simple
     * queue lists once per router no matter how many usernames are
     * passed, so a purge run covering many users at once doesn't
     * multiply API round-trips per router.
     *
     * @param  array<int, string>  $usernames
     * @return array{ok: bool, message: string}
     */
    public function unbindUsers(Router $router, array $usernames): array
    {
        if (blank($router->api_host) || empty($usernames)) {
            return ['ok' => false, 'message' => 'Tidak ada yang perlu di-unbind.'];
        }

        $usernameSet = array_flip($usernames);

        try {
            $client = new Client([
                'host' => $router->api_host,
                'user' => $router->api_user,
                'pass' => $router->api_pass,
                'port' => (int) $router->api_port,
                'timeout' => 8,
            ]);

            $unboundMacs = [];
            foreach ($client->query(new Query('/ip/hotspot/ip-binding/print'))->read() as $binding) {
                $username = $this->usernameFromTag($binding['comment'] ?? '');
                if ($username !== null && isset($usernameSet[$username]) && isset($binding['.id'])) {
                    $client->query((new Query('/ip/hotspot/ip-binding/remove'))->equal('.id', $binding['.id']))->read();
                    $unboundMacs[DeviceName::normalizeMac($binding['mac-address'] ?? '')] = true;
                }
            }

            // Drop the hosts that rode those bindings too, so the devices
            // land back on the login page right away (same as
            // disconnectBypassedHost()) instead of staying bypassed.
            if ($unboundMacs !== []) {
                foreach ($client->query(new Query('/ip/hotspot/host/print'))->read() as $host) {
                    if (isset($unboundMacs[DeviceName::normalizeMac($host['mac-address'] ?? '')]) && isset($host['.id'])) {
                        $client->query((new Query('/ip/hotspot/host/remove'))->equal('.id', $host['.id']))->read();
                    }
                }

                Cache::forget("mikrotik:bypassed-hosts:{$router->id}");
            }

            $parentIds = [];
            foreach ($client->query(new Query('/queue/simple/print'))->read() as $queue) {
                if (
                    ($parentUser = $this->usernameFromSharedQueueName($queue['name'] ?? '')) !== null
                    && isset($usernameSet[$parentUser]) && isset($queue['.id'])
                ) {
                    $parentIds[] = $queue['.id'];

                    continue;
                }

                $username = $this->parseQueueName($queue['name'] ?? '')[0] ?? null;
                if ($username !== null && isset($usernameSet[$username]) && isset($queue['.id'])) {
                    $client->query((new Query('/queue/simple/remove'))->equal('.id', $queue['.id']))->read();
                }
            }

            // Shared-bandwidth parents go last, once their device queues are gone.
            foreach ($parentIds as $parentId) {
                $client->query((new Query('/queue/simple/remove'))->equal('.id', $parentId))->read();
            }

            return ['ok' => true, 'message' => count($usernames).' user di-unbind.'];
        } catch (BadCredentialsException) {
            return ['ok' => false, 'message' => 'Username atau password API router salah.'];
        } catch (ConnectException) {
            return ['ok' => false, 'message' => "Tidak bisa terhubung ke router {$router->api_host}:{$router->api_port}."];
        } catch (ConfigException|QueryException|ClientException|StreamException $e) {
            return ['ok' => false, 'message' => 'Perintah ke router gagal: '.$e->getMessage()];
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => 'Gagal terhubung ke router: '.$e->getMessage()];
        }
    }

    /**
     * Device names (DHCP lease host-name, or the lease comment an admin set
     * in WinBox) keyed by MAC address, normalised via DeviceName::normalizeMac().
     * RADIUS accounting only carries the MAC, so the router is the only
     * source of a human-readable name. Cached briefly so opening the session
     * modal / device list doesn't hit the router API every time; an
     * unreachable router just yields no names instead of failing the page.
     *
     * @return array<string, string>
     */
    public function deviceNames(Router $router): array
    {
        if (blank($router->api_host)) {
            return [];
        }

        return Cache::remember("mikrotik:device-names:{$router->id}", 60, function () use ($router) {
            try {
                $client = new Client([
                    'host' => $router->api_host,
                    'user' => $router->api_user,
                    'pass' => $router->api_pass,
                    'port' => (int) $router->api_port,
                    'timeout' => 4,
                    'attempts' => 1,
                ]);

                $leases = $client->query(
                    new Query('/ip/dhcp-server/lease/print', ['=.proplist=mac-address,host-name,comment'])
                )->read();
            } catch (Throwable) {
                return [];
            }

            $names = [];
            foreach ($leases as $lease) {
                $mac = DeviceName::normalizeMac($lease['mac-address'] ?? '');
                $name = trim($lease['comment'] ?? '') ?: trim($lease['host-name'] ?? '');
                if ($mac !== '' && $name !== '') {
                    $names[$mac] = $name;
                }
            }

            return $names;
        });
    }

    /**
     * Add the named profile under $menu, or update it in place if it
     * already exists — safe to call on every re-provision. $unset fields
     * are cleared back to blank afterwards.
     *
     * @param  array<string, string>  $attributes
     * @param  array<int, string>  $unset
     */
    private function ensureProfile(Client $client, string $menu, string $name, array $attributes = [], array $unset = []): void
    {
        $existing = $client->query((new Query("{$menu}/print"))->where('name', $name))->read();
        $id = $existing[0]['.id'] ?? null;

        if (! $id || ! empty($attributes)) {
            $query = $id
                ? (new Query("{$menu}/set"))->equal('.id', $id)
                : (new Query("{$menu}/add"))->equal('name', $name);

            foreach ($attributes as $key => $value) {
                $query->equal($key, $value);
            }

            $result = $client->query($query)->read();
            $id ??= $result['after']['ret'] ?? null;
        }

        if (! $id) {
            return;
        }

        foreach ($unset as $field) {
            $client->query(
                (new Query("{$menu}/unset"))->equal('numbers', $id)->equal('value-name', $field)
            )->read();
        }
    }

    private function fail(array $steps, string $message): array
    {
        return ['ok' => false, 'message' => $message, 'steps' => $steps];
    }
}
