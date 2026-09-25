<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\HotspotUser;
use App\Models\Profile;
use App\Models\Radcheck;
use App\Models\Radusergroup;
use App\Models\Router;
use App\Models\UserBatch;
use App\Services\DeviceNameResolver;
use App\Services\MikrotikConnector;
use App\Services\UserGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserGenerator $generator,
        private readonly MikrotikConnector $mikrotik,
        private readonly DeviceNameResolver $deviceNames,
    ) {}

    public function index(Request $request)
    {
        $clientId = Auth::guard('client')->user()->client_id;

        $users = HotspotUser::with('profile')
            ->where('client_id', $clientId)
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest()
            ->get();

        $onlineCounts = DB::table('radacct')
            ->whereIn('username', $users->pluck('username'))
            ->whereNull('acctstoptime')
            ->groupBy('username')
            ->selectRaw('username, count(*) as total')
            ->pluck('total', 'username');

        return view('client.users.index', compact('users', 'onlineCounts'));
    }

    public function create()
    {
        $clientId = Auth::guard('client')->user()->client_id;
        $profiles = Profile::where('client_id', $clientId)->where('is_active', true)->get();

        return view('client.users.create', compact('profiles'));
    }

    public function store(Request $request): RedirectResponse
    {
        $client = Auth::guard('client')->user()->client;

        $data = $request->validate([
            'profile_id' => ['required', 'exists:profiles,id'],
            'note' => ['nullable', 'string', 'max:190'],
        ]);

        $profile = Profile::where('client_id', $client->id)->findOrFail($data['profile_id']);
        $this->guardUserQuota($client, 1);

        $user = $this->generator->generateOne($profile, note: $data['note'] ?? null);

        return redirect()->route('client.users.index')
            ->with('status', "User dibuat: {$user->username} / {$user->password}");
    }

    public function storeBatch(Request $request): RedirectResponse
    {
        $client = Auth::guard('client')->user()->client;

        $data = $request->validate([
            'profile_id' => ['required', 'exists:profiles,id'],
            'name' => ['required', 'string', 'max:100'],
            'qty' => ['required', 'integer', 'min:1', 'max:1000'],
            'prefix' => ['nullable', 'string', 'max:16'],
            'code_length' => ['required', 'integer', 'min:4', 'max:20'],
            'charset' => ['required', 'in:numeric,lower,upper,alnum'],
            'same_password' => ['nullable', 'boolean'],
        ]);

        Profile::where('client_id', $client->id)->findOrFail($data['profile_id']);
        $this->guardUserQuota($client, $data['qty']);

        $batch = UserBatch::create([
            'client_id' => $client->id,
            'profile_id' => $data['profile_id'],
            'name' => $data['name'],
            'qty' => $data['qty'],
            'prefix' => $data['prefix'] ?? null,
            'code_length' => $data['code_length'],
            'charset' => $data['charset'],
            'same_password' => $request->boolean('same_password'),
            'created_by' => Auth::guard('client')->id(),
        ]);

        $this->generator->generateBatch($batch);

        return redirect()->route('client.users.index')
            ->with('status', "Batch '{$batch->name}' berhasil dibuat: {$batch->qty} user.");
    }

    public function edit(HotspotUser $hotspotUser)
    {
        $this->authorizeUser($hotspotUser);

        $profiles = Profile::where('client_id', $hotspotUser->client_id)->where('is_active', true)->get();

        return view('client.users.edit', ['user' => $hotspotUser, 'profiles' => $profiles]);
    }

    public function update(Request $request, HotspotUser $hotspotUser): RedirectResponse
    {
        $this->authorizeUser($hotspotUser);

        $data = $request->validate([
            'username' => [
                'required', 'string', 'max:64',
                Rule::unique('hotspot_users', 'username')->ignore($hotspotUser->id),
            ],
            'password' => ['required', 'string', 'max:64'],
            'profile_id' => ['required', 'exists:profiles,id'],
            'status' => ['required', 'in:active,used,expired,disabled'],
            'expires_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:190'],
        ]);

        $profile = Profile::where('client_id', $hotspotUser->client_id)->findOrFail($data['profile_id']);

        $oldUsername = $hotspotUser->username;

        $hotspotUser->update([
            'username' => $data['username'],
            'password' => $data['password'],
            'profile_id' => $profile->id,
            'status' => $data['status'],
            'expires_at' => $data['expires_at'] ?: null,
            'note' => $data['note'] ?? null,
        ]);

        Radcheck::where('username', $oldUsername)->update([
            'username' => $data['username'],
            'value' => $data['password'],
        ]);

        Radusergroup::where('username', $oldUsername)->update([
            'username' => $data['username'],
            'groupname' => $profile->group_name,
        ]);

        // Keep any currently-open session's accounting rows pointing at the
        // new username so the "Online" count doesn't lose track of it.
        DB::table('radacct')
            ->where('username', $oldUsername)
            ->whereNull('acctstoptime')
            ->update(['username' => $data['username']]);

        return redirect()->route('client.users.index')->with('status', 'User diperbarui.');
    }

    public function destroy(HotspotUser $hotspotUser): RedirectResponse
    {
        $this->authorizeUser($hotspotUser);

        $this->purgeRadiusUsers([$hotspotUser->username], $hotspotUser->client_id);
        $hotspotUser->delete();

        return redirect()->route('client.users.index')->with('status', 'User dihapus.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $clientId = Auth::guard('client')->user()->client_id;

        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $users = HotspotUser::where('client_id', $clientId)->whereIn('id', $data['ids'])->get();

        $this->purgeRadiusUsers($users->pluck('username')->all(), $clientId);
        HotspotUser::whereIn('id', $users->pluck('id'))->delete();

        return redirect()->route('client.users.index')->with('status', $users->count().' user dihapus.');
    }

    public function sessions(HotspotUser $hotspotUser): JsonResponse
    {
        $this->authorizeUser($hotspotUser);

        $limiter = trim(($hotspotUser->profile->rate_up ?? '-').' / '.($hotspotUser->profile->rate_down ?? '-'));
        $expiresAt = $hotspotUser->expires_at?->format('d M Y H:i') ?? '-';

        $rows = DB::table('radacct')
            ->where('username', $hotspotUser->username)
            ->whereNull('acctstoptime')
            ->orderByDesc('acctstarttime')
            ->get();

        $deviceNames = $this->deviceNames->forSessions(
            $rows,
            Router::where('client_id', $hotspotUser->client_id)->get()->keyBy('nas_ip'),
        );

        $sessions = $rows
            ->map(fn ($row) => [
                'id' => $row->radacctid,
                'username' => $row->username,
                'device_name' => $deviceNames[$row->radacctid] ?? '-',
                'ip_address' => $row->framedipaddress ?: '-',
                'mac_address' => $row->callingstationid ?: '-',
                'limiter' => $limiter,
                'start_time' => Carbon::parse($row->acctstarttime)->format('d M Y H:i'),
                'expires_at' => $expiresAt,
                'uptime' => $this->formatDuration((int) Carbon::parse($row->acctstarttime)->diffInSeconds(now())),
                'volume' => $this->formatBytes((int) $row->acctinputoctets + (int) $row->acctoutputoctets),
            ]);

        return response()->json(['sessions' => $sessions]);
    }

    public function killSession(HotspotUser $hotspotUser, int $radacctId): JsonResponse
    {
        $this->authorizeUser($hotspotUser);

        $session = DB::table('radacct')
            ->where('radacctid', $radacctId)
            ->where('username', $hotspotUser->username)
            ->whereNull('acctstoptime')
            ->first();

        if (! $session) {
            return response()->json(['ok' => false, 'message' => 'Sesi tidak ditemukan atau sudah tidak aktif.'], 404);
        }

        $router = Router::where('client_id', $hotspotUser->client_id)
            ->where('nas_ip', $session->nasipaddress)
            ->first();

        if (! $router) {
            return response()->json(['ok' => false, 'message' => 'Router untuk sesi ini tidak ditemukan.'], 404);
        }

        $result = $session->framedipaddress
            ? $this->mikrotik->killHotspotSession($router, $session->framedipaddress)
            : ['ok' => false, 'message' => 'Sesi tidak ditemukan di router, mungkin sudah terputus.'];

        return response()->json($result, $result['ok'] ? 200 : 502);
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

    private function purgeRadiusUsers(array $usernames, int $clientId): void
    {
        if (empty($usernames)) {
            return;
        }

        $this->disconnectActiveSessions($usernames, $clientId);
        $this->unbindFromRouters($usernames, $clientId);

        Radcheck::whereIn('username', $usernames)->delete();
        Radusergroup::whereIn('username', $usernames)->delete();
    }

    private function unbindFromRouters(array $usernames, int $clientId): void
    {
        $routers = Router::where('client_id', $clientId)->where('status', 'verified')->get();

        foreach ($routers as $router) {
            $this->mikrotik->unbindUsers($router, $usernames);
        }
    }

    private function disconnectActiveSessions(array $usernames, int $clientId): void
    {
        $sessions = DB::table('radacct')
            ->whereIn('username', $usernames)
            ->whereNull('acctstoptime')
            ->get();

        if ($sessions->isEmpty()) {
            return;
        }

        $routers = Router::where('client_id', $clientId)->get()->keyBy('nas_ip');

        foreach ($sessions as $session) {
            if ($router = $routers->get($session->nasipaddress)) {
                $this->mikrotik->killHotspotSession($router, $session->framedipaddress);
            }
        }
    }

    private function authorizeUser(HotspotUser $hotspotUser): void
    {
        abort_if($hotspotUser->client_id !== Auth::guard('client')->user()->client_id, Response::HTTP_FORBIDDEN);
    }

    private function guardUserQuota($client, int $additional): void
    {
        $current = HotspotUser::where('client_id', $client->id)->count();

        abort_if(
            $client->max_users !== null && $current + $additional > $client->max_users,
            403,
            "Kuota user client ini ({$client->max_users}) akan terlampaui."
        );
    }
}
