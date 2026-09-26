<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\HotspotUser;
use App\Models\Profile;
use App\Models\Router;
use App\Services\OnlineDevices;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(OnlineDevices $onlineDevices)
    {
        $client = Auth::guard('client')->user()->client;

        $users = HotspotUser::with('profile')->where('client_id', $client->id)->get()->keyBy('username');
        $usernames = $users->keys();

        $stats = [
            'routers_verified' => Router::where('client_id', $client->id)->where('status', 'verified')->count(),
            'users_active' => HotspotUser::where('client_id', $client->id)->where('status', 'active')->count(),
            'users_used' => HotspotUser::where('client_id', $client->id)->where('status', 'used')->count(),
            'devices_online' => $onlineDevices->forUsers($users, Router::where('client_id', $client->id)->get())->count(),
            'profiles_total' => Profile::where('client_id', $client->id)->count(),
            'users_total' => $usernames->count(),
        ];

        return view('client.dashboard', compact('client', 'stats'));
    }
}
