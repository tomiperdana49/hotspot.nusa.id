<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\MikrotikConnector;
use Illuminate\Console\Command;

class RouterHeartbeat extends Command
{
    protected $signature = 'routers:heartbeat';

    protected $description = 'Ping every verified router via API and update last_seen_at for reachable ones';

    public function handle(MikrotikConnector $connector): int
    {
        $routers = Router::where('status', 'verified')->whereNotNull('api_host')->get();

        $reachable = 0;

        foreach ($routers as $router) {
            if ($connector->ping($router)) {
                $router->update(['last_seen_at' => now()]);
                $reachable++;
            }
        }

        $this->info("Heartbeat: {$reachable}/{$routers->count()} router terjangkau.");

        return self::SUCCESS;
    }
}
