<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\HotspotTemplate;
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

        $users = HotspotUser::with('profile.routers:id')->where('client_id', $client->id)->get()->keyBy('username');
        $usernames = $users->keys();
        $routers = Router::where('client_id', $client->id)->orderBy('name')->get();
        $devices = $onlineDevices->forUsers($users, $routers);

        $stats = [
            'routers_verified' => Router::where('client_id', $client->id)->where('status', 'verified')->count(),
            'users_active' => HotspotUser::where('client_id', $client->id)->where('status', 'active')->count(),
            'users_used' => HotspotUser::where('client_id', $client->id)->where('status', 'used')->count(),
            'devices_online' => $devices->count(),
            'profiles_total' => Profile::where('client_id', $client->id)->count(),
            'users_total' => $usernames->count(),
        ];

        // One card per router: what is online there, which vouchers can log
        // in there, and which login page it is set to show.
        $profiles = Profile::with('routers:id')->where('client_id', $client->id)->get();
        $ownTemplates = HotspotTemplate::where('client_id', $client->id)->whereNotNull('router_id')->pluck('title', 'router_id');
        $defaultTemplate = HotspotTemplate::where('client_id', $client->id)->whereNull('router_id')->value('title');
        $devicesByRouter = $devices->countBy('router_id');

        $routerCards = $routers->map(fn (Router $router) => [
            'router' => $router,
            'devices' => $devicesByRouter[$router->id] ?? 0,
            'vouchers' => $users->filter(fn (HotspotUser $user) => in_array($user->status, ['active', 'used'], true)
                && $user->profile
                && ($user->profile->router_scope === 'all' || $user->profile->routers->contains('id', $router->id)))->count(),
            'own_profiles' => $profiles->filter(fn (Profile $p) => $p->router_scope === 'selected' && $p->routers->contains('id', $router->id))->count(),
            'template' => $ownTemplates[$router->id] ?? $defaultTemplate,
            'own_template' => isset($ownTemplates[$router->id]),
        ]);

        return view('client.dashboard', compact('client', 'stats', 'routerCards'));
    }
}
