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
            @endif

            @if ($mikrotikScript)
                <details class="card p-6">
                    <summary class="font-semibold cursor-pointer select-none">{{ __('app.router_show.manual_script') }}</summary>
                    <pre class="mt-4 bg-gray-900 text-gray-100 text-xs p-4 rounded-xl overflow-x-auto leading-relaxed">{{ $mikrotikScript }}</pre>
                </details>
            @endif
        </div>
    </div>
@endsection
