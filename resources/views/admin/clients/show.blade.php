@extends('layouts.admin')

@section('title', $client->name)

@section('content')
    <div class="mb-7 flex justify-between items-start">
        <div>
            <a href="{{ route('admin.clients.index') }}" class="text-sm text-gray-400 hover:text-gray-700 mb-2 inline-flex items-center gap-1">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ __('app.router_show.back') }}
            </a>
            <h1 class="text-2xl font-bold tracking-tight">{{ $client->name }}</h1>
            <p class="text-sm text-gray-500 mt-1">
                <span class="font-mono">{{ $client->code }}</span> · {{ $client->email }}
                @if ($client->phone) · {{ $client->phone }} @endif
            </p>
            @if ($client->address)
                <p class="text-sm text-gray-500 mt-1">{{ $client->address }}</p>
            @endif
        </div>
        <div class="flex items-center gap-3">
            @if ($client->status === 'active')
                <span class="badge bg-green-50 text-green-700"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>{{ $client->status }}</span>
            @else
                <span class="badge bg-red-50 text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>{{ $client->status }}</span>
            @endif
            <a href="{{ route('admin.clients.edit', $client) }}" class="btn-secondary">{{ __('app.user_index.edit') }}</a>
            <form method="POST" action="{{ route('admin.clients.impersonate', $client) }}">
                @csrf
                <button class="btn-primary">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h3a2 2 0 012 2v14a2 2 0 01-2 2h-3" stroke-linecap="round" stroke-linejoin="round"/><path d="M10 17l5-5-5-5M15 12H3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    {{ __('app.impersonate.button') }}
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="card p-5">
            <div class="text-xs font-medium text-gray-500 mb-2">{{ __('app.nav.router') }}</div>
            <div class="text-3xl font-bold tracking-tight">{{ $client->routers->count() }} <span class="text-base font-normal text-gray-400">/ {{ $client->max_routers ?? '∞' }}</span></div>
        </div>
        <div class="card p-5">
            <div class="text-xs font-medium text-gray-500 mb-2">{{ __('app.nav.profile') }}</div>
            <div class="text-3xl font-bold tracking-tight">{{ $client->profiles->count() }}</div>
        </div>
        <div class="card p-5">
            <div class="text-xs font-medium text-gray-500 mb-2">{{ __('app.client_dashboard.users_active') }}</div>
            <div class="text-3xl font-bold tracking-tight text-green-600">{{ $userStats['active'] }}</div>
        </div>
        <div class="card p-5">
            <div class="text-xs font-medium text-gray-500 mb-2">{{ __('app.admin_clients_show.total_user') }}</div>
            <div class="text-3xl font-bold tracking-tight">{{ $userStats['total'] }} <span class="text-base font-normal text-gray-400">/ {{ $client->max_users ?? '∞' }}</span></div>
        </div>
    </div>

    <div class="card overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-gray-100">
            <span class="font-semibold text-sm">{{ __('app.nav.router') }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <th class="px-5 py-3">{{ __('app.router_index.col_identity') }}</th>
                        <th class="px-5 py-3">{{ __('app.router_index.col_ip') }}</th>
                        <th class="px-5 py-3">{{ __('app.router_index.col_status') }}</th>
                        <th class="px-5 py-3">{{ __('app.router_show.serial_board') }}</th>
                        <th class="px-5 py-3">{{ __('app.router_show.verified_at') }}</th>
                        <th class="px-5 py-3">{{ __('app.router_index.col_last_seen') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($client->routers as $router)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3.5 font-medium">{{ $router->name }}</td>
                            <td class="px-5 py-3.5 font-mono text-xs text-gray-500">{{ $router->nas_ip ?? '-' }}</td>
                            <td class="px-5 py-3.5">
                                @php $colors = ['pending' => 'bg-amber-50 text-amber-700', 'verified' => 'bg-green-50 text-green-700', 'disabled' => 'bg-red-50 text-red-700']; @endphp
                                @php $dots = ['pending' => 'bg-amber-500', 'verified' => 'bg-green-500', 'disabled' => 'bg-red-500']; @endphp
                                <span class="badge {{ $colors[$router->status] }}"><span class="w-1.5 h-1.5 rounded-full {{ $dots[$router->status] }}"></span>{{ $router->status }}</span>
                            </td>
                            <td class="px-5 py-3.5 font-mono text-xs text-gray-500">{{ $router->board_serial ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-gray-500">{{ $router->verified_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-gray-500">{{ $router->last_seen_at?->diffForHumans() ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-5 py-8 text-center text-gray-400" colspan="6">{{ __('app.admin_clients_show.no_routers') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-gray-100">
            <span class="font-semibold text-sm">{{ __('app.nav.profile') }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <th class="px-5 py-3">{{ __('app.profile_index.col_name') }}</th>
                        <th class="px-5 py-3">{{ __('app.profile_index.col_speed') }}</th>
                        <th class="px-5 py-3">{{ __('app.admin_clients_show.group_name') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($client->profiles as $profile)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3.5 font-medium">{{ $profile->name }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ $profile->rate_up ?? '-' }} / {{ $profile->rate_down ?? '-' }}</td>
                            <td class="px-5 py-3.5 font-mono text-xs text-gray-500">{{ $profile->group_name }}</td>
                        </tr>
                    @empty
                        <tr><td class="px-5 py-8 text-center text-gray-400" colspan="3">{{ __('app.admin_clients_show.no_profiles') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100">
            <span class="font-semibold text-sm">{{ __('app.admin_clients_show.login_accounts') }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <th class="px-5 py-3">{{ __('app.profile_index.col_name') }}</th>
                        <th class="px-5 py-3">{{ __('app.admin_clients_create.email') }}</th>
                        <th class="px-5 py-3">{{ __('app.admin_clients_show.role') }}</th>
                        <th class="px-5 py-3">{{ __('app.admin_clients_show.last_login') }}</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($client->users as $user)
                        <tr class="hover:bg-gray-50 align-top">
                            <td class="px-5 py-3.5 font-medium">{{ $user->name }}</td>
                            <td class="px-5 py-3.5">{{ $user->email }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ ucfirst($user->role) }}</td>
                            <td class="px-5 py-3.5 text-gray-500">{{ $user->last_login_at?->diffForHumans() ?? __('app.admin_clients_show.never') }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <details class="inline-block text-left">
                                    <summary class="text-gray-500 hover:text-gray-900 font-medium cursor-pointer select-none list-none">{{ __('app.admin_clients_show.reset_password') }}</summary>
                                    <form method="POST" action="{{ route('admin.clients.users.reset-password', [$client, $user]) }}"
                                          class="mt-3 flex items-center gap-2 justify-end">
                                        @csrf
                                        <input type="password" name="password" required minlength="8" placeholder="{{ __('app.admin_clients_show.new_password') }}" class="input w-48">
                                        <button type="submit" class="btn-secondary shrink-0">{{ __('app.profile_form.save') }}</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td class="px-5 py-8 text-center text-gray-400" colspan="5">{{ __('app.admin_clients_show.no_login_accounts') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
