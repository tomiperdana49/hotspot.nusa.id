<?php

namespace App\Services;

use App\Models\DeviceName;
use App\Models\HotspotUser;
use App\Models\Router;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Every device currently online for a set of hotspot users, from two
 * sources: open RADIUS sessions (radacct), and devices riding a bypass
 * ip-binding from MikrotikConnector::syncActiveBindings(). A bypassed
 * device skips the hotspot login, so RADIUS closes its session the
 * moment it's bound — radacct alone would show it offline while it's
 * still using the network.
 */
class OnlineDevices
{
    public function __construct(
        private readonly MikrotikConnector $mikrotik,
        private readonly DeviceNameResolver $deviceNames,
    ) {}

    /**
     * @param  Collection<string, HotspotUser>  $users  keyed by username
     * @param  Collection<int, Router>  $routers  the client's routers
     * @return Collection<int, array<string, mixed>>
     */
    public function forUsers(Collection $users, Collection $routers): Collection
    {
        if ($users->isEmpty()) {
            return collect();
        }

        $routersByNasIp = $routers->keyBy('nas_ip');

        $rows = DB::table('radacct')
            ->whereIn('username', $users->keys())
            ->whereNull('acctstoptime')
            ->get();

        $radiusNames = $this->deviceNames->forSessions($rows, $routersByNasIp);
        $seenMacs = [];

        $devices = $rows->map(function ($row) use ($users, $routersByNasIp, $radiusNames, &$seenMacs) {
            $mac = DeviceName::normalizeMac($row->callingstationid);
            $seenMacs[$row->username.'|'.$mac] = true;
            $user = $users->get($row->username);
            $router = $routersByNasIp->get($row->nasipaddress);

            return $this->row(
                $user,
                $row->username,
                $radiusNames[$row->radacctid] ?? '-',
                $router?->name ?? $row->nasipaddress,
                $row->framedipaddress ?: '-',
                $row->callingstationid ?: '-',
                Carbon::parse($row->acctstarttime),
                (int) $row->acctinputoctets + (int) $row->acctoutputoctets,
                $user ? route('client.users.sessions.kill', [$user, $row->radacctid]) : null,
                null,
            );
        });

        foreach ($routers->where('status', 'verified') as $router) {
            $hosts = array_filter(
                $this->mikrotik->bypassedHosts($router),
                fn ($host) => $users->has($host['username']) && ! isset($seenMacs[$host['username'].'|'.$host['mac']]),
            );

            if ($hosts === []) {
                continue;
            }

            $liveNames = $this->mikrotik->deviceNames($router);
            $storedNames = DeviceName::where('router_id', $router->id)
                ->whereIn('mac_address', array_column($hosts, 'mac'))
                ->pluck('name', 'mac_address');

            foreach ($hosts as $host) {
                $user = $users->get($host['username']);
                $devices->push($this->row(
                    $user,
                    $host['username'],
                    $liveNames[$host['mac']] ?? $storedNames[$host['mac']] ?? '-',
                    $router->name,
                    $host['ip'] ?: '-',
                    $host['mac'],
                    now()->subSeconds($host['uptime']),
                    $host['bytes'],
                    route('client.users.bypass.kill', [$user, $router]),
                    $host['mac'],
                ));
            }
        }

        return $devices->sortByDesc('started_at')->values();
    }

    private function row(
        ?HotspotUser $user,
        string $username,
        string $deviceName,
        string $routerName,
        string $ip,
        string $mac,
        Carbon $startedAt,
        int $bytes,
        ?string $killUrl,
        ?string $killMac,
    ): array {
        return [
            'hotspot_user_id' => $user?->id,
            'username' => $username,
            'device_name' => $deviceName,
            'router_name' => $routerName,
            'ip_address' => $ip,
            'mac_address' => $mac,
            'limiter' => $user ? trim(($user->profile->rate_up ?? '-').' / '.($user->profile->rate_down ?? '-')) : '-',
            'started_at' => $startedAt->timestamp,
            'start_time' => $startedAt->format('d M Y H:i'),
            'uptime' => $this->formatDuration((int) $startedAt->diffInSeconds(now())),
            'volume' => $this->formatBytes($bytes),
            'expires_at' => $user?->expires_at?->format('d M Y H:i') ?? '-',
            'kill_url' => $killUrl,
            'kill_mac' => $killMac,
        ];
    }

    private function formatDuration(int $seconds): string
    {
        $seconds = max(0, $seconds);
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);

        return $h > 0 ? "{$h}j {$m}m" : "{$m}m";
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 MB';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        $i = min($i, count($units) - 1);

        return round($bytes / (1024 ** $i), 2).' '.$units[$i];
    }
}
