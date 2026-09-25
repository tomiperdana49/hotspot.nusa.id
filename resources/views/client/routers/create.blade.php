@extends('layouts.client')

@section('title', __('app.router_create.title'))

@section('content')
    <div class="mb-7">
        <a href="{{ route('client.routers.index') }}" class="text-sm text-gray-500 hover:text-gray-800 mb-2 inline-flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            {{ __('app.nav.router') }}
        </a>
        <h1 class="page-title">{{ __('app.router_create.title') }}</h1>
        <p class="page-subtitle max-w-2xl">{{ __('app.router_create.subtitle') }}</p>
    </div>

    <div class="grid lg:grid-cols-5 gap-6 items-start">
        <form method="POST" action="{{ route('client.routers.store') }}" class="card p-6 space-y-5 lg:col-span-3">
            @csrf
            <div>
                <label class="label">{{ __('app.router_create.ip_label') }}</label>
                <input name="api_host" value="{{ old('api_host') }}" required placeholder="{{ __('app.router_create.ip_placeholder') }}" class="input font-mono">
                <p class="hint">{{ __('app.router_create.ip_hint') }}</p>
            </div>
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">{{ __('app.router_create.api_user_label') }}</label>
                    <input name="api_user" value="{{ old('api_user', 'admin') }}" required class="input" autocomplete="off">
                </div>
                <div>
                    <label class="label">{{ __('app.router_create.api_pass_label') }}</label>
                    <input type="password" name="api_pass" required class="input" autocomplete="new-password">
                </div>
            </div>
            <div>
                <label class="label">{{ __('app.router_create.api_port_label') }}</label>
                <input type="number" name="api_port" value="{{ old('api_port', 8728) }}" required class="input w-40">
                <p class="hint">{{ __('app.router_create.api_port_hint') }} <code class="bg-gray-100 px-1 rounded">/ip service enable api</code></p>
            </div>
            <div class="rounded-xl bg-gray-50 border border-gray-200 px-4 py-3">
                <div class="text-sm font-medium text-gray-700">{{ __('app.router_create.identity_label') }}</div>
                <p class="hint !mt-0.5">{{ __('app.router_create.identity_hint') }}</p>
            </div>
            <button type="submit" class="btn-primary w-full">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2L4.5 13.5H12L11 22l8.5-11.5H12L13 2z" stroke-linejoin="round"/></svg>
                {{ __('app.router_create.connect') }}
            </button>
        </form>

        <aside class="card p-6 lg:col-span-2">
            <h2 class="font-semibold mb-4">{{ __('app.ui.routers.steps_title') }}</h2>
            <ol class="space-y-4">
                @foreach (['step_a', 'step_b', 'step_c', 'step_d'] as $i => $key)
                    <li class="flex gap-3">
                        <span class="w-6 h-6 rounded-full bg-brand-50 text-brand-700 text-xs font-bold flex items-center justify-center shrink-0">{{ $i + 1 }}</span>
                        <div class="text-sm text-gray-600 pt-0.5">
                            {{ __('app.ui.routers.'.$key) }}
                            @if ($key === 'step_b')
                                <code class="mt-1.5 block bg-gray-900 text-gray-100 text-xs px-3 py-2 rounded-lg">/ip service enable api</code>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        </aside>
    </div>
@endsection
