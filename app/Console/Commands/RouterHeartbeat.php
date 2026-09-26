<?php

namespace App\Console\Commands;

use App\Models\Router;
use App\Services\MikrotikConnector;
use Illuminate\Console\Command;

class RouterHeartbeat extends Command
{
    protected $signature = 'routers:heartbeat';

    protected $description = 'Ping every verified router via API and update last_seen_at + identity for reachable ones';

    public function handle(MikrotikConnector $connector): int
    {
        $routers = Router::where('status', 'verified')->whereNotNull('api_host')->get();

        $reachable = 0;

        foreach ($routers as $router) {
            $identity = $connector->ping($router);

            if ($identity !== null) {
                $router->update(['last_seen_at' => now()]);
                $connector->syncIdentity($router, $identity);
                $reachable++;
            }
        }

        $this->info("Heartbeat: {$reachable}/{$routers->count()} router terjangkau.");

        return self::SUCCESS;
    }
}
