<?php

namespace App\Services;

use App\Models\Client as NusaClient;
use App\Models\DeviceName;
use App\Models\HotspotUser;
use App\Models\Nas;
use App\Models\Profile;
use App\Models\Router;
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
     * Builds the ip-binding comment / simple queue name this class uses
     * to tag entries it manages: "{username}-{expires_at date}" (or
     * "-no-expiry" for a voucher with no set expiry), so an admin can
     * read who a binding belongs to and when it expires straight from
     * WinBox instead of an opaque internal tag.
     */
    private function bindingTag(HotspotUser $user): string
    {
        $expiry = $user->expires_at?->format('Y-m-d') ?? 'no-expiry';

        return "{$user->username}-{$expiry}";
    }

    /**
     * Reverses bindingTag(): returns the username if $tag has the shape
     * this class generates, or null if it doesn't — which is how
     * syncActiveBindings()/unbindUsers() tell their own entries apart
     * from anything an admin created by hand.
     */
    private function usernameFromTag(string $tag): ?string
    {
        if (preg_match('/^(.+)-(\d{4}-\d{2}-\d{2}|no-expiry)$/', $tag, $matches)) {
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
     * connects and runs a trivial read-only query, without touching the
     * router's configuration.
     */
    public function ping(Router $router): bool
    {
        if (blank($router->api_host)) {
            return false;
        }

        try {
            $client = new Client([
                'host' => $router->api_host,
                'user' => $router->api_user,
                'pass' => $router->api_pass,
                'port' => (int) $router->api_port,
                'timeout' => 5,
            ]);

            $client->query(new Query('/system/identity/print'))->read();

            return true;
        } catch (Throwable) {
            return false;
        }
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

            if (empty($active)) {
                return ['ok' => true, 'message' => 'Tidak ada sesi aktif.'];
            }

            $boundByMac = [];
            foreach ($client->query(new Query('/ip/hotspot/ip-binding/print'))->read() as $binding) {
                $mac = strtoupper($binding['mac-address'] ?? '');
                if ($mac && $this->usernameFromTag($binding['comment'] ?? '') !== null) {
                    $boundByMac[$mac] = $binding;
                }
            }

            $queueByUsername = [];
            foreach ($client->query(new Query('/queue/simple/print'))->read() as $queue) {
                $username = $this->usernameFromTag($queue['name'] ?? '');
                if ($username !== null) {
                    $queueByUsername[$username] = $queue;
                }
            }

            $usernames = array_unique(array_filter(array_column($active, 'user')));
            $users = HotspotUser::whereIn('username', $usernames)->with('profile')->get()->keyBy('username');

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

                if (! $existingBinding) {
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
                $existingQueue = $queueByUsername[$username] ?? null;

                if (! $existingQueue) {
                    $client->query(
                        (new Query('/queue/simple/add'))
                            ->equal('name', $tag)
                            ->equal('target', $target)
                            ->equal('max-limit', $maxLimit)
                    )->read();
                } elseif (
                    ($existingQueue['name'] ?? null) !== $tag
                    || ($existingQueue['target'] ?? null) !== $target
                    || ($existingQueue['max-limit'] ?? null) !== $maxLimit
                ) {
                    $client->query(
                        (new Query('/queue/simple/set'))
                            ->equal('.id', $existingQueue['.id'])
                            ->equal('name', $tag)
                            ->equal('target', $target)
                            ->equal('max-limit', $maxLimit)
                    )->read();
                }
            }

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

            foreach ($client->query(new Query('/ip/hotspot/ip-binding/print'))->read() as $binding) {
                $username = $this->usernameFromTag($binding['comment'] ?? '');
                if ($username !== null && isset($usernameSet[$username]) && isset($binding['.id'])) {
                    $client->query((new Query('/ip/hotspot/ip-binding/remove'))->equal('.id', $binding['.id']))->read();
                }
            }

            foreach ($client->query(new Query('/queue/simple/print'))->read() as $queue) {
                $username = $this->usernameFromTag($queue['name'] ?? '');
                if ($username !== null && isset($usernameSet[$username]) && isset($queue['.id'])) {
                    $client->query((new Query('/queue/simple/remove'))->equal('.id', $queue['.id']))->read();
                }
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

    private function fail(array $steps, string $message): array
    {
        return ['ok' => false, 'message' => $message, 'steps' => $steps];
    }
}
