<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\DeviceNameResolver;
use App\Services\MikrotikConnector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncHotspotBindings extends Command
{
    protected $signature = 'hotspot:sync-bindings';

    protected $description = 'Bypass the hotspot login for devices that are already authenticated (ip-binding) and pin their bandwidth to a simple queue, so a device is never forced to log back in before its own voucher actually expires.';

    public function handle(MikrotikConnector $connector, DeviceNameResolver $deviceNames): int
    {
        $routers = Router::where('status', 'verified')->get();

        foreach ($routers as $router) {
            $connector->syncActiveBindings($router);
        }

        // Capture device names while devices are online, so the connection
        // history can still show them after their DHCP lease is gone.
        $openSessions = DB::table('radacct')
            ->whereNull('acctstoptime')
            ->whereIn('nasipaddress', $routers->pluck('nas_ip')->filter())
            ->get(['radacctid', 'nasipaddress', 'callingstationid']);

        if ($openSessions->isNotEmpty()) {
            $deviceNames->forSessions($openSessions, $routers->keyBy('nas_ip'));
        }

        return self::SUCCESS;
    }
}
