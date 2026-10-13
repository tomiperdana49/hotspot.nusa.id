@extends('layouts.client')

@section('title', __('app.nav.login_test'))

@section('content')
    <div class="mb-7">
        <h1 class="page-title">{{ __('app.nav.login_test') }}</h1>
        <p class="page-subtitle">{{ __('app.login_test.subtitle') }}</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-5 items-start">
        <form method="GET" action="{{ route('client.login-test') }}" class="card p-6 space-y-4 lg:col-span-2">
            <div>
                <label class="label" for="ltUsername">{{ __('app.login_test.voucher') }}</label>
                <input id="ltUsername" name="username" value="{{ $username }}" list="ltUsernames" required autocomplete="off" class="input font-mono" placeholder="{{ __('app.login_test.voucher_placeholder') }}">
                <datalist id="ltUsernames">
                    @foreach ($usernames as $name)
                        <option value="{{ $name }}"></option>
                    @endforeach
                </datalist>
            </div>
            <div>
                <label class="label" for="ltRouter">{{ __('app.login_test.router') }}</label>
                <select id="ltRouter" name="router" required class="select">
                    @foreach ($routers as $r)
                        <option value="{{ $r->id }}" @selected($router?->id === $r->id)>{{ $r->name }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="btn-primary w-full" @disabled($routers->isEmpty())>{{ __('app.login_test.run') }}</button>
            @if ($routers->isEmpty())
                <p class="text-sm text-gray-500">{{ __('app.hotspot_template.no_router') }}</p>
            @endif
            <p class="text-xs text-gray-400">{{ __('app.login_test.note') }}</p>
        </form>

        <div class="lg:col-span-3 space-y-4">
            @if ($checks === null)
                <div class="card p-6 text-sm text-gray-500">{{ __('app.login_test.empty') }}</div>
            @else
                @php
                    $failed = collect($checks)->search(fn ($c) => ! $c['ok']);
                    $accepted = $failed === false;
                @endphp
                <div class="card p-6 flex items-start gap-4 {{ $accepted ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                    <span class="w-10 h-10 rounded-full flex items-center justify-center shrink-0 {{ $accepted ? 'bg-green-600' : 'bg-red-600' }} text-white">
                        @if ($accepted)
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M5 12l5 5 9-10" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @else
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/></svg>
                        @endif
                    </span>
                    <div>
                        <div class="text-lg font-bold {{ $accepted ? 'text-green-800' : 'text-red-800' }}">{{ $accepted ? __('app.login_test.accepted') : __('app.login_test.rejected') }}</div>
                        <p class="text-sm text-gray-700 mt-0.5">
                            {{ $accepted
                                ? __('app.login_test.accepted_desc', ['username' => $username, 'router' => $router->name])
                                : __('app.login_test.rejected_desc', ['reason' => $checks[$failed]['label']]) }}
                        </p>
                    </div>
                </div>
                <div class="card">
                    <div class="card-header"><h2 class="section-title">{{ __('app.login_test.steps') }}</h2></div>
                    <ol class="divide-y divide-gray-100">
                        @foreach ($checks as $i => $check)
                            @php $skipped = $failed !== false && $i > $failed; @endphp
                            <li class="px-6 py-3.5 flex items-start gap-3 {{ $skipped ? 'opacity-50' : '' }}">
                                <span class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 text-xs font-bold {{ $skipped ? 'bg-gray-100 text-gray-400' : ($check['ok'] ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700') }}">
                                    {{ $skipped ? '–' : ($check['ok'] ? '✓' : '✕') }}
                                </span>
                                <div class="min-w-0">
                                    <div class="text-sm font-medium text-gray-900">{{ $check['label'] }}</div>
                                    <div class="text-xs text-gray-500 break-words">{{ $check['detail'] }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            @endif
        </div>
    </div>
@endsection
