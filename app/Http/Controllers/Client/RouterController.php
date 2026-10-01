<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Router;
use App\Services\MikrotikConnector;
use App\Services\RouterPairingService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class RouterController extends Controller
{
    /**
     * Never a router: loopback, "this host", link-local (incl. the cloud
     * metadata service at 169.254.169.254), multicast, reserved, and
     * IPv4-mapped IPv6 that could smuggle any of those past this list.
     */
    private const BLOCKED_API_RANGES = [
        '0.0.0.0/8', '127.0.0.0/8', '169.254.0.0/16', '224.0.0.0/4', '240.0.0.0/4',
        '::/128', '::1/128', '::ffff:0:0/96', 'fe80::/10', 'ff00::/8',
    ];

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

    /**
     * Ping each of the client's verified routers right now, so the list
     * page can show a router that just came up (or went down) without
     * waiting for the next heartbeat. Called from the page after it loads.
     */
    public function liveStatus(): JsonResponse
    {
        $clientId = Auth::guard('client')->user()->client_id;
        $routers = Router::where('client_id', $clientId)->where('status', 'verified')->get();

        $status = $routers->map(function (Router $router) {
            $identity = $this->connector->ping($router, 3);

            if ($identity !== null) {
                $router->update(['last_seen_at' => now()]);
                $this->connector->syncIdentity($router, $identity);
            }

            return [
                'id' => $router->id,
                'online' => $identity !== null,
                'name' => $router->name,
                'last_seen' => $router->last_seen_at?->diffForHumans() ?? '-',
            ];
        });

        return response()->json($status->values());
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

        // Pick up a System Identity renamed on the router since the last
        // heartbeat. Short timeout so an offline router doesn't stall the page.
        if ($router->status === 'verified') {
            $identity = $this->connector->ping($router, 3);
            if ($identity !== null) {
                $router->update(['last_seen_at' => now()]);
                $this->connector->syncIdentity($router, $identity);
            }
        }

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
            'api_host' => ['required', 'ip', function (string $attribute, mixed $value, Closure $fail) {
                if ($error = $this->apiHostError((string) $value)) {
                    $fail($error);
                }
            }],
            'api_user' => ['required', 'string', 'max:64'],
            'api_pass' => ['required', 'string', 'max:255'],
            'api_port' => ['required', 'integer', 'min:1', 'max:65535'],
        ]);
    }

    /**
     * The server dials api_host itself, so an arbitrary address would let a
     * client point it at the server's own services or at another
     * client's router (SSRF). Private ranges stay allowed: routers are
     * reached over the LAN and WireGuard.
     */
    private function apiHostError(string $ip): ?string
    {
        $ownIps = collect(net_get_interfaces() ?: [])
            ->flatMap(fn (array $interface) => array_column($interface['unicast'] ?? [], 'address'));

        if (IpUtils::checkIp($ip, self::BLOCKED_API_RANGES) || $ownIps->contains($ip)) {
            return 'Alamat IP ini tidak bisa dipakai untuk router.';
        }

        $takenByOtherClient = Router::where('client_id', '!=', Auth::guard('client')->user()->client_id)
            ->where(fn ($q) => $q->where('api_host', $ip)->orWhere('vpn_ip', $ip)->orWhere('nas_ip', $ip))
            ->exists();

        return $takenByOtherClient ? 'Alamat IP ini sudah dipakai router lain.' : null;
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
