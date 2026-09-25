<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\HotspotUser;
use App\Models\Router;
use App\Services\DeviceNameResolver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DeviceController extends Controller
{
    public function __construct(private readonly DeviceNameResolver $deviceNames) {}

    public function index()
    {
        $clientId = Auth::guard('client')->user()->client_id;

        $users = HotspotUser::with('profile')
            ->where('client_id', $clientId)
            ->get()
            ->keyBy('username');

        $routers = Router::where('client_id', $clientId)->get()->keyBy('nas_ip');

        $rows = DB::table('radacct')
            ->whereIn('username', $users->keys())
            ->whereNull('acctstoptime')
            ->orderByDesc('acctstarttime')
            ->get();

        $deviceNames = $this->deviceNames->forSessions($rows, $routers);

        $devices = $rows
            ->map(function ($row) use ($users, $routers, $deviceNames) {
                $user = $users->get($row->username);
                $router = $routers->get($row->nasipaddress);
                $limiter = $user
                    ? trim(($user->profile->rate_up ?? '-').' / '.($user->profile->rate_down ?? '-'))
                    : '-';

                return [
                    'hotspot_user_id' => $user?->id,
                    'username' => $row->username,
                    'device_name' => $deviceNames[$row->radacctid] ?? '-',
                    'router_name' => $router?->name ?? $row->nasipaddress,
                    'ip_address' => $row->framedipaddress ?: '-',
                    'mac_address' => $row->callingstationid ?: '-',
                    'limiter' => $limiter,
                    'start_time' => Carbon::parse($row->acctstarttime)->format('d M Y H:i'),
                    'uptime' => $this->formatDuration((int) Carbon::parse($row->acctstarttime)->diffInSeconds(now())),
                    'volume' => $this->formatBytes((int) $row->acctinputoctets + (int) $row->acctoutputoctets),
                    'expires_at' => $user?->expires_at?->format('d M Y H:i') ?? '-',
                    'radacct_id' => $row->radacctid,
                ];
            });

        return view('client.devices.index', compact('devices'));
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
