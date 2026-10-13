<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\HotspotUser;
use App\Models\Nas;
use App\Models\Router;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Answers "can voucher X log in on router Y?" for the client's admin,
 * with the reason when it can't. The checks mirror authorize_check_query
 * in FreeRADIUS's queries.conf, in the order a failure is worth reporting;
 * keep the two in step. The password itself isn't checked here.
 */
class LoginTestController extends Controller
{
    public function index(Request $request)
    {
        $client = Auth::guard('client')->user()->client;
        $routers = Router::where('client_id', $client->id)->where('status', 'verified')->orderBy('name')->get();
        $usernames = HotspotUser::where('client_id', $client->id)->orderBy('username')->pluck('username');

        $username = trim((string) $request->query('username'));
        $router = $routers->firstWhere('id', (int) $request->query('router'));
        $checks = null;

        if ($username !== '' && $router) {
            $user = HotspotUser::with('profile.routers:id,name')
                ->where('client_id', $client->id)
                ->where('username', $username)
                ->first();
            $checks = $this->checks($client, $user, $router);
        }

        return view('client.login-test.index', compact('routers', 'usernames', 'username', 'router', 'checks'));
    }

    /**
     * @return array<int, array{label: string, ok: bool, detail: string}>
     */
    private function checks($client, ?HotspotUser $user, Router $router): array
    {
        $profile = $user?->profile;
        $clientActive = $client->status === 'active' && ($client->expired_at === null || now()->lt($client->expired_at));
        $nasRegistered = Nas::where('nasname', $router->nas_ip)->where('client_id', $client->id)->exists();
        $scopeOk = $profile && ($profile->router_scope === 'all' || $profile->routers->contains('id', $router->id));

        return [
            [
                'label' => __('app.login_test.check_exists'),
                'ok' => $user !== null,
                'detail' => $user ? __('app.login_test.detail_exists', ['profile' => $profile?->name ?? '-']) : __('app.login_test.detail_missing'),
            ],
            [
                'label' => __('app.login_test.check_status'),
                'ok' => $user && in_array($user->status, ['active', 'used'], true),
                'detail' => $user ? __('app.ui.user_status.'.$user->status).($user->expires_at ? ' · '.__('app.login_test.until', ['date' => $user->expires_at->format('d M Y H:i')]) : '') : '-',
            ],
            [
                'label' => __('app.login_test.check_client'),
                'ok' => $clientActive,
                'detail' => $clientActive ? __('app.login_test.detail_client_ok') : __('app.login_test.detail_client_bad'),
            ],
            [
                'label' => __('app.login_test.check_router'),
                'ok' => $nasRegistered,
                'detail' => $nasRegistered ? $router->nas_ip : __('app.login_test.detail_router_bad'),
            ],
            [
                'label' => __('app.login_test.check_scope'),
                'ok' => $scopeOk,
                'detail' => $profile ? __('app.login_test.detail_scope', ['profile' => $profile->name, 'routers' => $profile->router_label]) : '-',
            ],
        ];
    }
}
