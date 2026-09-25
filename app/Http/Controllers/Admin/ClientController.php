<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\ClientUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::withCount(['routers', 'hotspotUsers'])->latest()->get();

        return view('admin.clients.index', compact('clients'));
    }

    public function create()
    {
        return view('admin.clients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:clients,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:150'],
            'owner_email' => ['required', 'email', 'max:150', 'unique:client_users,email'],
            'owner_password' => ['required', 'string', 'min:8'],
        ]);

        DB::transaction(function () use ($data) {
            $client = Client::create([
                'code' => $this->uniqueClientCode(),
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => 'active',
            ]);

            ClientUser::create([
                'client_id' => $client->id,
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'password' => Hash::make($data['owner_password']),
                'role' => 'owner',
                'is_active' => true,
            ]);
        });

        return redirect()->route('admin.clients.index')->with('status', 'Client baru berhasil dibuat.');
    }

    public function show(Client $client)
    {
        $client->load([
            'routers' => fn ($q) => $q->latest(),
            'profiles' => fn ($q) => $q->latest(),
            'users' => fn ($q) => $q->latest(),
        ]);

        $userStats = [
            'active' => $client->hotspotUsers()->where('status', 'active')->count(),
            'used' => $client->hotspotUsers()->where('status', 'used')->count(),
            'total' => $client->hotspotUsers()->count(),
        ];

        return view('admin.clients.show', compact('client', 'userStats'));
    }

    public function resetUserPassword(Request $request, Client $client, ClientUser $clientUser): RedirectResponse
    {
        abort_if($clientUser->client_id !== $client->id, 404);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $clientUser->update(['password' => Hash::make($data['password'])]);

        return redirect()->route('admin.clients.show', $client)
            ->with('status', "Password akun {$clientUser->email} berhasil diganti.");
    }

    public function edit(Client $client)
    {
        return view('admin.clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:clients,email,'.$client->id],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:active,suspended,expired'],
            'max_routers' => ['nullable', 'integer', 'min:1'],
            'max_users' => ['nullable', 'integer', 'min:1'],
            'expired_at' => ['nullable', 'date'],
        ]);

        $data['max_routers'] = ($data['max_routers'] ?? '') !== '' ? $data['max_routers'] : null;
        $data['max_users'] = ($data['max_users'] ?? '') !== '' ? $data['max_users'] : null;

        $client->update($data);

        return redirect()->route('admin.clients.index')->with('status', 'Client diperbarui.');
    }

    private function uniqueClientCode(): string
    {
        do {
            $code = 'CLI'.random_int(100, 999).Str::upper(Str::random(2));
        } while (Client::where('code', $code)->exists());

        return $code;
    }
}
