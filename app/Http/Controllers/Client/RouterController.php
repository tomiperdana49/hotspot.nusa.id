<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Services\MikrotikConnector;
use App\Services\RouterPairingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RouterController extends Controller
{
    public function __construct(
        private readonly RouterPairingService $pairing,
        private readonly MikrotikConnector $connector,
    ) {}

    public function index()
    {
        $clientId = Auth::guard('client')->user()->client_id;
        $routers = Router::where('client_id', $clientId)->latest()->get();

        return view('client.routers.index', compact('routers'));
    }

    public function create()
    {
        $client = Auth::guard('client')->user()->client;
        $current = Router::where('client_id', $client->id)->count();

        if ($client->max_routers !== null && $current >= $client->max_routers) {
            return redirect()->route('client.routers.index')
                ->withErrors(['router' => "Batas maksimum router ({$client->max_routers}) sudah tercapai."]);
        }

        return view('client.routers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $client = Auth::guard('client')->user()->client;
        $data = $this->validated($request);

        $router = $this->pairing->start($client, "Router {$data['api_host']}");
        $router->update([
            'api_host' => $data['api_host'],
            'api_user' => $data['api_user'],
            'api_pass' => $data['api_pass'],
            'api_port' => $data['api_port'],
        ]);

        $result = $this->connector->provision($router, $this->serverIp());

        return redirect()->route('client.routers.show', $router)
            ->with($result['ok'] ? 'status' : 'connect_error', $result['message']);
    }

    public function show(Router $router)
    {
        $this->authorizeRouter($router);

        $serverIp = $this->serverIp();
        $viaWireguard = $router->api_host && str_starts_with((string) $router->api_host, '10.88.');

        return view('client.routers.show', [
            'router' => $router,
            'mikrotikScript' => $router->api_host ? $this->pairing->mikrotikScript($router, $serverIp, $viaWireguard) : null,
        ]);
    }

    public function connect(Request $request, Router $router): RedirectResponse
    {
        $this->authorizeRouter($router);
        $data = $this->validated($request);

        $router->update([
            'api_host' => $data['api_host'],
            'api_user' => $data['api_user'],
            'api_pass' => $data['api_pass'],
            'api_port' => $data['api_port'],
        ]);

        $result = $this->connector->provision($router, $this->serverIp());

        return redirect()->route('client.routers.show', $router)
            ->with($result['ok'] ? 'status' : 'connect_error', $result['message']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'api_host' => ['required', 'ip'],
            'api_user' => ['required', 'string', 'max:64'],
            'api_pass' => ['required', 'string', 'max:255'],
            'api_port' => ['required', 'integer', 'min:1', 'max:65535'],
        ]);
    }

    private function serverIp(): string
    {
        return config('services.nusa.radius_server_ip') ?: request()->getHost();
    }

    private function authorizeRouter(Router $router): void
    {
        abort_if($router->client_id !== Auth::guard('client')->user()->client_id, Response::HTTP_FORBIDDEN);
    }
}
