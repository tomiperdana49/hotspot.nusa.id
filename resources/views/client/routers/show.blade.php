@extends('layouts.client')

@section('title', $router->name)

@section('content')
    <div class="mb-7">
        <a href="{{ route('client.routers.index') }}" class="text-sm text-gray-500 hover:text-gray-800 mb-2 inline-flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            {{ __('app.router_show.back') }}
        </a>
        <div class="flex items-center gap-3">
            <h1 class="page-title">{{ $router->name }}</h1>
            @if ($router->status === 'verified')
                <span class="badge bg-green-50 text-green-700"><span class="dot bg-green-500"></span>{{ __('app.ui.router_status.verified') }}</span>
            @else
                <span class="badge bg-amber-50 text-amber-700"><span class="dot bg-amber-500"></span>{{ __('app.ui.router_status.pending') }}</span>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2 items-start">
        <div class="card p-6">
            <h2 class="font-semibold mb-1">{{ $router->status === 'verified' ? __('app.router_show.api_connection') : __('app.router_show.connect_to_mikrotik') }}</h2>
            <p class="text-sm text-gray-500 mb-4">
                @if ($router->status === 'verified')
                    {{ __('app.router_show.connected_hint') }}
                @else
                    {{ __('app.router_show.connect_hint') }}
                @endif
            </p>
            <form method="POST" action="{{ route('client.routers.connect', $router) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="label">{{ __('app.router_create.identity_label') }}</label>
                    <input value="{{ $router->name }}" disabled class="input bg-gray-50 text-gray-500">
                    <p class="text-xs text-gray-400 mt-1">{{ __('app.router_create.identity_hint') }}</p>
                </div>
                <div>
                    <label class="label">{{ __('app.router_create.ip_label') }}</label>
                    <input name="api_host" value="{{ old('api_host', $router->api_host) }}" required placeholder="{{ __('app.router_create.ip_placeholder') }}" class="input">
                </div>
                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="label">{{ __('app.router_create.api_user_label') }}</label>
                        <input name="api_user" value="{{ old('api_user', $router->api_user ?? 'admin') }}" required class="input">
                    </div>
                    <div>
                        <label class="label">{{ __('app.router_create.api_pass_label') }}</label>
                        <input type="password" name="api_pass" value="{{ old('api_pass', $router->api_pass) }}" required class="input">
                    </div>
                </div>
                <div>
                    <label class="label">{{ __('app.router_create.api_port_label') }}</label>
                    <input type="number" name="api_port" value="{{ old('api_port', $router->api_port ?? 8728) }}" required class="input">
                </div>
                <button type="submit" class="btn-primary w-full">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L4.5 13.5H12L11 22l8.5-11.5H12L13 2z" stroke-linejoin="round"/></svg>
                    {{ $router->status === 'verified' ? __('app.router_show.reconfigure') : __('app.router_create.connect') }}
                </button>
            </form>
        </div>

        <div class="space-y-6">
            @if ($router->status === 'verified')
                <div class="card p-6">
                    <h2 class="font-semibold mb-4">{{ __('app.router_show.detail_title') }}</h2>
                    <dl class="text-sm divide-y divide-gray-100">
                        <div class="flex justify-between gap-4 py-2.5"><dt class="text-gray-500">{{ __('app.router_create.ip_label') }}</dt><dd class="font-mono">{{ $router->nas_ip }}</dd></div>
                        <div class="flex justify-between gap-4 py-2.5"><dt class="text-gray-500">{{ __('app.router_show.serial_board') }}</dt><dd class="font-mono">{{ $router->board_serial ?? '-' }}</dd></div>
                        <div class="flex justify-between gap-4 py-2.5"><dt class="text-gray-500">{{ __('app.router_show.verified_at') }}</dt><dd>{{ $router->verified_at?->format('d M Y H:i') }}</dd></div>
                        <div class="flex justify-between gap-4 py-2.5"><dt class="text-gray-500">{{ __('app.router_index.col_last_seen') }}</dt><dd>{{ $router->last_seen_at?->diffForHumans() ?? '-' }}</dd></div>
                    </dl>
                </div>

                <div class="card p-6" id="blocked-sites">
                    <h2 class="font-semibold mb-1">{{ __('app.blocked_sites.title') }}</h2>
                    <p class="text-sm text-gray-500 mb-4">{{ __('app.blocked_sites.hint') }}</p>

                    @if ($blockedSites->isNotEmpty())
                        <ul class="text-sm divide-y divide-gray-100 mb-4">
                            @foreach ($blockedSites as $site)
                                <li class="flex items-center justify-between gap-4 py-2">
                                    <span class="font-mono break-all">{{ $site->domain }}</span>
                                    <form method="POST" action="{{ route('client.routers.blocked-sites.destroy', [$router, $site]) }}"
                                          onsubmit="return confirm(@js(__('app.blocked_sites.confirm_unblock', ['domain' => $site->domain])))">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn-sm-danger">{{ __('app.blocked_sites.unblock') }}</button>
                                    </form>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-400 mb-4">{{ __('app.blocked_sites.empty') }}</p>
                    @endif

                    <form method="POST" action="{{ route('client.routers.blocked-sites.store', $router) }}" class="space-y-3">
                        @csrf
                        <label class="label" for="blocked-domains">{{ __('app.blocked_sites.add_label') }}</label>
                        <textarea id="blocked-domains" name="domains" rows="3" required class="input font-mono" placeholder="tiktok.com&#10;example.com">{{ old('domains') }}</textarea>
                        <p class="text-xs text-gray-400">{{ __('app.blocked_sites.add_hint') }}</p>
                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="btn-primary">{{ __('app.blocked_sites.add') }}</button>
                            @if ($blockedSites->isNotEmpty())
                                <button type="submit" formaction="{{ route('client.routers.blocked-sites.sync', $router) }}" formnovalidate class="btn-secondary">{{ __('app.blocked_sites.reapply') }}</button>
                            @endif
                        </div>
                    </form>
                    <p class="text-xs text-gray-400 mt-4">{{ __('app.blocked_sites.limits') }}</p>
                </div>
            @endif

            @if ($mikrotikScript)
                <details class="card p-6">
                    <summary class="font-semibold cursor-pointer select-none">{{ __('app.router_show.manual_script') }}</summary>
                    <pre class="mt-4 bg-gray-900 text-gray-100 text-xs p-4 rounded-xl overflow-x-auto leading-relaxed">{{ $mikrotikScript }}</pre>
                </details>
            @endif

            <div class="card p-6">
                <h2 class="font-semibold mb-1">{{ __('app.router_show.delete_title') }}</h2>
                <p class="text-sm text-gray-500 mb-4">{{ __('app.router_show.delete_hint') }}</p>
                <form method="POST" action="{{ route('client.routers.destroy', $router) }}" class="space-y-4"
                      onsubmit="return confirm(@js(__('app.router_show.confirm_delete', ['name' => $router->name])))">
                    @csrf @method('DELETE')
                    @if ($router->status === 'verified')
                        <label class="flex items-start gap-2 text-sm text-gray-600">
                            <input type="checkbox" name="force" value="1" class="mt-0.5">
                            <span>{{ __('app.router_show.delete_force') }}</span>
                        </label>
                    @endif
                    <button type="submit" class="btn-sm-danger">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18" stroke-linecap="round"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        {{ __('app.router_show.delete') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
@endsection
