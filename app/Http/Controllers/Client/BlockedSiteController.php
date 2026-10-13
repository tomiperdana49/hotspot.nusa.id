<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Models\RouterBlockedSite;
use App\Services\MikrotikConnector;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class BlockedSiteController extends Controller
{
    /**
     * Each domain costs three firewall rules on the router, checked on
     * every new web connection, so the list is capped.
     */
    private const MAX_PER_ROUTER = 100;

    public function __construct(private readonly MikrotikConnector $connector) {}

    public function store(Request $request, Router $router): RedirectResponse
    {
        $this->authorizeRouter($router);
        $request->validate(['domains' => ['required', 'string', 'max:10000']]);

        $lines = preg_split('/[\s,]+/', $request->string('domains'), -1, PREG_SPLIT_NO_EMPTY);
        $domains = [];
        $invalid = [];
        foreach ($lines as $line) {
            $domain = RouterBlockedSite::normalizeDomain($line);
            $domain === null ? $invalid[] = $line : $domains[$domain] = true;
        }

        if ($invalid !== []) {
            throw ValidationException::withMessages([
                'domains' => __('app.blocked_sites.invalid', ['domains' => implode(', ', array_slice($invalid, 0, 5))]),
            ]);
        }

        $existing = $router->blockedSites()->pluck('domain')->all();
        $new = array_values(array_diff(array_keys($domains), $existing));

        if (count($existing) + count($new) > self::MAX_PER_ROUTER) {
            throw ValidationException::withMessages([
                'domains' => __('app.blocked_sites.too_many', ['max' => self::MAX_PER_ROUTER]),
            ]);
        }

        foreach ($new as $domain) {
            $router->blockedSites()->create(['domain' => $domain]);
        }

        return $this->sync($router);
    }

    public function destroy(Router $router, RouterBlockedSite $blockedSite): RedirectResponse
    {
        $this->authorizeRouter($router);
        abort_if($blockedSite->router_id !== $router->id, Response::HTTP_NOT_FOUND);

        $blockedSite->delete();

        return $this->sync($router);
    }

    /**
     * Push the list to the router. The list itself is already saved, so a
     * router that can't be reached only delays the block until the client
     * presses "apply again".
     */
    public function sync(Router $router): RedirectResponse
    {
        $this->authorizeRouter($router);

        $result = $this->connector->syncBlockedSites($router);

        return redirect()->to(route('client.routers.show', $router).'#blocked-sites')->with(
            $result['ok'] ? 'status' : 'connect_error',
            $result['ok'] ? $result['message'] : __('app.blocked_sites.not_applied', ['reason' => $result['message']]),
        );
    }

    private function authorizeRouter(Router $router): void
    {
        abort_if($router->client_id !== Auth::guard('client')->user()->client_id, Response::HTTP_FORBIDDEN);
        abort_if($router->status !== 'verified', Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
