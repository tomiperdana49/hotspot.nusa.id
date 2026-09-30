<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Profile;
use App\Services\MikrotikConnector;
use App\Services\ProfileRadiusSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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
        $profiles = Profile::where('client_id', $clientId)->latest()->get();

        return view('client.profiles.index', compact('profiles'));
    }

    public function create()
    {
        return view('client.profiles.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $client = Auth::guard('client')->user()->client;

        $data = $this->validated($request);
        $data['client_id'] = $client->id;
        $data['group_name'] = $this->uniqueGroupName($client->code, $data['name']);
        $data['is_active'] = true;

        $profile = Profile::create($data);
        $this->radiusSync->sync($profile);
        $this->connector->syncCookieLifetimeForClient($client);

        return redirect()->route('client.profiles.index')->with('status', 'Profile dibuat.');
    }

    public function edit(Profile $profile)
    {
        $this->authorizeProfile($profile);

        return view('client.profiles.edit', compact('profile'));
    }

    public function update(Request $request, Profile $profile): RedirectResponse
    {
        $this->authorizeProfile($profile);

        $oldGroupName = $profile->group_name;
        $data = $this->validated($request, $profile->id);

        $profile->update($data);
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

        return $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:profiles,name,'.($ignoreId ?? 'NULL').',id,client_id,'.$clientId],
            'rate_down' => ['nullable', 'string', 'max:20'],
            'rate_up' => ['nullable', 'string', 'max:20'],
            'bandwidth_mode' => ['required', 'in:per_device,shared'],
            'session_timeout' => ['nullable', 'integer', 'min:0'],
            'idle_timeout' => ['nullable', 'integer', 'min:0'],
            'validity_value' => ['nullable', 'integer', 'min:1'],
            'validity_unit' => ['nullable', 'in:hour,day,month'],
            'validity_mode' => ['required', 'in:from_create,from_first_login'],
            'simultaneous_use' => ['required', 'integer', 'min:1', 'max:255'],
            'mikrotik_group' => ['required', 'string', 'max:64'],
        ]);
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
