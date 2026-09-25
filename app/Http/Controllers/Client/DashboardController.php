<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\HotspotUser;
use App\Models\Profile;
use App\Models\Router;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $client = Auth::guard('client')->user()->client;

        $usernames = HotspotUser::where('client_id', $client->id)->pluck('username');

        $stats = [
            'routers_verified' => Router::where('client_id', $client->id)->where('status', 'verified')->count(),
            'users_active' => HotspotUser::where('client_id', $client->id)->where('status', 'active')->count(),
            'users_used' => HotspotUser::where('client_id', $client->id)->where('status', 'used')->count(),
            'devices_online' => DB::table('radacct')->whereIn('username', $usernames)->whereNull('acctstoptime')->count(),
            'profiles_total' => Profile::where('client_id', $client->id)->count(),
            'users_total' => $usernames->count(),
        ];

        return view('client.dashboard', compact('client', 'stats'));
    }
}
