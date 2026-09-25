<?php

namespace App\Console\Commands;

use App\Models\HotspotUser;
use App\Models\Radcheck;
use App\Models\Radusergroup;
use App\Models\Router;
use App\Services\MikrotikConnector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PurgeExpiredUsers extends Command
{
    protected $signature = 'users:purge-expired';

    protected $description = 'Delete hotspot users whose expires_at has passed, disconnect their active sessions, and remove their RADIUS entries';

    public function handle(MikrotikConnector $connector): int
    {
        $expired = HotspotUser::whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        if ($expired->isEmpty()) {
            $this->info('Tidak ada user yang kadaluarsa.');

            return self::SUCCESS;
        }

        $usernames = $expired->pluck('username')->all();

        $this->disconnectActiveSessions($connector, $usernames);
        $this->unbindFromRouters($connector, $expired);

        Radcheck::whereIn('username', $usernames)->delete();
        Radusergroup::whereIn('username', $usernames)->delete();
        HotspotUser::whereIn('id', $expired->pluck('id'))->delete();

        $this->info(count($usernames).' user kadaluarsa berhasil dihapus.');

        return self::SUCCESS;
    }

    private function disconnectActiveSessions(MikrotikConnector $connector, array $usernames): void
    {
        $sessions = DB::table('radacct')
            ->whereIn('username', $usernames)
            ->whereNull('acctstoptime')
            ->get();

        if ($sessions->isEmpty()) {
            return;
        }

        $routers = Router::whereIn('nas_ip', $sessions->pluck('nasipaddress')->unique())->get()->keyBy('nas_ip');

        foreach ($sessions as $session) {
            if ($router = $routers->get($session->nasipaddress)) {
                $connector->killHotspotSession($router, $session->framedipaddress);
            }
        }
    }

    /**
     * Bindings/queues aren't tied to whether a session happens to be
     * active right now, so this runs independently of disconnectActiveSessions()
     * — a device that's simply powered off at purge time still needs its
     * bypass revoked before it reconnects.
     */
    private function unbindFromRouters(MikrotikConnector $connector, $expired): void
    {
        foreach ($expired->groupBy('client_id') as $clientId => $usersForClient) {
            $routers = Router::where('client_id', $clientId)->where('status', 'verified')->get();
            $usernames = $usersForClient->pluck('username')->all();

            foreach ($routers as $router) {
                $connector->unbindUsers($router, $usernames);
            }
        }
    }
}
