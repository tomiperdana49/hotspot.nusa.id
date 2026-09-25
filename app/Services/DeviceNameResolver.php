<?php

namespace App\Services;

use App\Models\DeviceName;
use App\Models\Router;
use Illuminate\Support\Collection;

class DeviceNameResolver
{
    public function __construct(private readonly MikrotikConnector $mikrotik) {}

    /**
     * Resolve a device name for each radacct row: the router's live DHCP
     * leases first, falling back to the last name recorded for that MAC.
     * Live matches are written back to device_names so the connection
     * history keeps the name after the device leaves.
     *
     * @param  Collection<int, object>  $rows  radacct rows
     * @param  Collection<string, Router>  $routersByNasIp
     * @return array<int, string>  radacctid => device name (only rows with a known name)
     */
    public function forSessions(Collection $rows, Collection $routersByNasIp): array
    {
        $names = [];

        foreach ($rows->groupBy('nasipaddress') as $nasIp => $routerRows) {
            $router = $routersByNasIp->get($nasIp);

            if (! $router) {
                continue;
            }

            $macs = $routerRows->map(fn ($row) => DeviceName::normalizeMac($row->callingstationid))->filter()->unique();
            $live = array_intersect_key($this->mikrotik->deviceNames($router), $macs->flip()->all());
            $stored = DeviceName::where('router_id', $router->id)
                ->whereIn('mac_address', $macs)
                ->pluck('name', 'mac_address');

            $this->record($router, $live);

            foreach ($routerRows as $row) {
                $mac = DeviceName::normalizeMac($row->callingstationid);
                $name = $live[$mac] ?? $stored[$mac] ?? null;

                if ($name !== null) {
                    $names[$row->radacctid] = $name;
                }
            }
        }

        return $names;
    }

    /**
     * @param  array<string, string>  $names  normalised MAC => name
     */
    private function record(Router $router, array $names): void
    {
        if ($names === []) {
            return;
        }

        $now = now();

        DeviceName::upsert(
            collect($names)->map(fn ($name, $mac) => [
                'router_id' => $router->id,
                'mac_address' => $mac,
                'name' => mb_substr($name, 0, 191),
                'last_seen_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ])->values()->all(),
            ['router_id', 'mac_address'],
            ['name', 'last_seen_at', 'updated_at'],
        );
    }
}
