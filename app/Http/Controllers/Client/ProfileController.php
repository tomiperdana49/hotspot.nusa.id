<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Models\Router;
use App\Services\MikrotikConnector;
use App\Services\ProfileRadiusSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ProfileRadiusSync $radiusSync,
        private readonly MikrotikConnector $connector,
    ) {}

    public function index()
    {
        $clientId = Auth::guard('client')->user()->client_id;
        $profiles = Profile::where('client_id', $clientId)->with('routers:id,name')->latest()->get();

        return view('client.profiles.index', compact('profiles'));
    }

    public function create()
    {
        return view('client.profiles.create', ['routers' => $this->clientRouters()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $client = Auth::guard('client')->user()->client;

        $data = $this->validated($request);
        $data['client_id'] = $client->id;
        $data['group_name'] = $this->uniqueGroupName($client->code, $data['name']);
        $data['is_active'] = true;

        $routerIds = $this->routerIds($request, $data['router_scope']);

        $profile = Profile::create($data);
        $profile->routers()->sync($routerIds);
        $this->radiusSync->sync($profile);
        $this->connector->syncCookieLifetimeForClient($client);

        return redirect()->route('client.profiles.index')->with('status', 'Profile dibuat.');
    }

    public function edit(Profile $profile)
    {
        $this->authorizeProfile($profile);

        return view('client.profiles.edit', [
            'profile' => $profile->load('routers:id'),
            'routers' => $this->clientRouters(),
        ]);
    }

    public function update(Request $request, Profile $profile): RedirectResponse
    {
        $this->authorizeProfile($profile);

        $oldGroupName = $profile->group_name;
        $data = $this->validated($request, $profile->id);
        $routerIds = $this->routerIds($request, $data['router_scope']);

        $profile->update($data);
        $profile->routers()->sync($routerIds);
        $this->radiusSync->sync($profile, $oldGroupName);
        $this->connector->syncCookieLifetimeForClient($profile->client);

        return redirect()->route('client.profiles.index')->with('status', 'Profile diperbarui.');
    }

    public function destroy(Profile $profile): RedirectResponse
    {
        $this->authorizeProfile($profile);

        if ($profile->hotspotUsers()->exists()) {
            return back()->withErrors(['profile' => 'Profile ini masih dipakai user, tidak bisa dihapus.']);
        }

        $client = $profile->client;

        $this->radiusSync->removeGroup($profile->group_name);
        $profile->routers()->detach();
        $profile->delete();
        $this->connector->syncCookieLifetimeForClient($client);

        return redirect()->route('client.profiles.index')->with('status', 'Profile dihapus.');
    }

    private function authorizeProfile(Profile $profile): void
    {
        abort_if($profile->client_id !== Auth::guard('client')->user()->client_id, Response::HTTP_FORBIDDEN);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $clientId = Auth::guard('client')->user()->client_id;

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:profiles,name,'.($ignoreId ?? 'NULL').',id,client_id,'.$clientId],
            'rate_down' => ['nullable', 'string', 'max:20'],
            'rate_up' => ['nullable', 'string', 'max:20'],
            'bandwidth_mode' => ['required', 'in:per_device,shared'],
            'connection_mode' => ['required', 'in:binding,hotspot'],
            'router_scope' => ['required', 'in:all,selected'],
            'session_timeout' => ['nullable', 'integer', 'min:0'],
            'idle_timeout' => ['nullable', 'integer', 'min:0'],
            'validity_value' => ['nullable', 'integer', 'min:1'],
            'validity_unit' => ['nullable', 'in:hour,day,month'],
            'validity_mode' => ['required', 'in:from_create,from_first_login'],
            'simultaneous_use' => ['required', 'integer', 'min:1', 'max:255'],
            'mikrotik_group' => ['required', 'string', 'max:64'],
        ]);

        // Sharing a voucher's rate needs the per-device queues that only
        // binding mode creates; a plain hotspot session gets the full rate.
        if ($data['connection_mode'] === 'hotspot') {
            $data['bandwidth_mode'] = 'per_device';
        }

        return $data;
    }

    /**
     * Routers ticked for a "selected" profile, limited to the client's own.
     *
     * @return array<int, int>
     */
    private function routerIds(Request $request, string $scope): array
    {
        if ($scope !== 'selected') {
            return [];
        }

        $clientId = Auth::guard('client')->user()->client_id;
        $request->validate([
            'router_ids' => ['required', 'array', 'min:1'],
            'router_ids.*' => ['integer', Rule::exists('routers', 'id')->where('client_id', $clientId)],
        ], ['router_ids.required' => __('app.profile_form.router_ids_required')]);

        return array_map('intval', $request->input('router_ids'));
    }

    private function clientRouters()
    {
        return Router::where('client_id', Auth::guard('client')->user()->client_id)->orderBy('name')->get(['id', 'name']);
    }

    private function uniqueGroupName(string $clientCode, string $profileName): string
    {
        $base = Str::slug($clientCode.'-'.$profileName, '_');
        $groupName = $base;
        $i = 1;

        while (Profile::where('group_name', $groupName)->exists()) {
            $groupName = $base.'_'.(++$i);
        }

        return $groupName;
    }
}
