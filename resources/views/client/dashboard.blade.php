@extends('layouts.client')

@section('title', 'Dashboard')

@section('content')
    @php
        $steps = [
            ['done' => $stats['routers_verified'] > 0, 'title' => __('app.ui.dashboard.step1_title'), 'desc' => __('app.ui.dashboard.step1_desc'), 'action' => __('app.ui.dashboard.step1_action'), 'url' => route('client.routers.create')],
            ['done' => $stats['profiles_total'] > 0, 'title' => __('app.ui.dashboard.step2_title'), 'desc' => __('app.ui.dashboard.step2_desc'), 'action' => __('app.ui.dashboard.step2_action'), 'url' => route('client.profiles.create')],
            ['done' => $stats['users_total'] > 0, 'title' => __('app.ui.dashboard.step3_title'), 'desc' => __('app.ui.dashboard.step3_desc'), 'action' => __('app.ui.dashboard.step3_action'), 'url' => route('client.users.create')],
        ];
        $doneCount = collect($steps)->where('done', true)->count();
        $nextStep = collect($steps)->search(fn ($s) => ! $s['done']);

        $cards = [
            ['label' => __('app.client_dashboard.routers_verified'), 'desc' => __('app.ui.dashboard.stat_routers_desc'), 'value' => $stats['routers_verified'], 'url' => route('client.routers.index'), 'tone' => 'emerald',
             'icon' => '<path d="M2.5 9.5a14 14 0 0119 0" stroke-linecap="round"/><path d="M5.8 13a9.5 9.5 0 0112.4 0" stroke-linecap="round"/><path d="M9 16.3a5 5 0 016 0" stroke-linecap="round"/><circle cx="12" cy="19.5" r="1.2" fill="currentColor" stroke="none"/>'],
            ['label' => __('app.client_dashboard.users_active'), 'desc' => __('app.ui.dashboard.stat_active_desc'), 'value' => $stats['users_active'], 'url' => route('client.users.index', ['status' => 'active']), 'tone' => 'sky',
             'icon' => '<path d="M4 8a2 2 0 012-2h12a2 2 0 012 2v1.5a1.8 1.8 0 000 3.5V14.5a1.8 1.8 0 000 3.5V19a2 2 0 01-2 2H6a2 2 0 01-2-2v-1a1.8 1.8 0 000-3.5V11.5a1.8 1.8 0 000-3.5V8z" stroke-linejoin="round"/>'],
            ['label' => __('app.client_dashboard.users_used'), 'desc' => __('app.ui.dashboard.stat_used_desc'), 'value' => $stats['users_used'], 'url' => route('client.users.index', ['status' => 'used']), 'tone' => 'violet',
             'icon' => '<path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/>'],
            ['label' => __('app.client_dashboard.devices_online'), 'desc' => __('app.ui.dashboard.stat_devices_desc'), 'value' => $stats['devices_online'], 'url' => route('client.devices.index'), 'tone' => 'amber',
             'icon' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4" stroke-linecap="round"/>'],
        ];
        $tones = [
            'emerald' => 'bg-emerald-50 text-emerald-600',
            'sky' => 'bg-sky-50 text-sky-600',
            'violet' => 'bg-violet-50 text-violet-600',
            'amber' => 'bg-amber-50 text-amber-600',
        ];
    @endphp

    <div class="mb-7">
        <h1 class="page-title">{{ __('app.client_dashboard.greeting', ['name' => $client->name]) }} 👋</h1>
        <p class="page-subtitle">
            {{ __('app.ui.dashboard.subtitle') }}
            <span class="text-gray-400">·</span>
            {{ __('app.client_dashboard.client_code') }}: <span class="font-mono text-gray-700 bg-gray-100 px-1.5 py-0.5 rounded">{{ $client->code }}</span>
        </p>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
        @foreach ($cards as $card)
            <a href="{{ $card['url'] }}" class="card p-5 group hover:border-brand-500/40 hover:shadow-md transition">
                <div class="flex items-start justify-between">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ $tones[$card['tone']] }}">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">{!! $card['icon'] !!}</svg>
                    </div>
                    <span class="text-xs font-medium text-gray-400 group-hover:text-brand-600 inline-flex items-center gap-0.5">
                        {{ __('app.ui.dashboard.view') }}
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                </div>
                <div class="mt-4 text-3xl font-bold tracking-tight">{{ number_format($card['value']) }}</div>
                <div class="mt-1 text-sm font-medium text-gray-700">{{ $card['label'] }}</div>
                <div class="text-xs text-gray-500">{{ $card['desc'] }}</div>
            </a>
        @endforeach
    </div>

    {{-- Getting started --}}
    <div class="card mb-8">
        <div class="card-header flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold">{{ __('app.ui.dashboard.start_title') }}</h2>
                <p class="text-sm text-gray-500">
                    {{ $doneCount === 3 ? __('app.ui.dashboard.all_done') : __('app.ui.dashboard.start_subtitle') }}
                </p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                <div class="w-32 h-2 rounded-full bg-gray-100 overflow-hidden">
                    <div class="h-full bg-brand-600 rounded-full" style="width: {{ round($doneCount / 3 * 100) }}%"></div>
                </div>
                <span class="text-xs font-medium text-gray-500">{{ __('app.ui.dashboard.progress', ['done' => $doneCount]) }}</span>
            </div>
        </div>
        <ol class="grid md:grid-cols-3 divide-y md:divide-y-0 md:divide-x divide-gray-100">
            @foreach ($steps as $i => $step)
                @php $isNext = $nextStep === $i; @endphp
                <li class="p-6 flex flex-col {{ $isNext ? 'bg-brand-50/50' : '' }}">
                    <div class="flex items-center gap-3 mb-3">
                        @if ($step['done'])
                            <span class="w-8 h-8 rounded-full bg-brand-600 text-white flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 6L9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </span>
                        @else
                            <span class="w-8 h-8 rounded-full border-2 {{ $isNext ? 'border-brand-600 text-brand-700' : 'border-gray-200 text-gray-400' }} flex items-center justify-center text-sm font-bold shrink-0">{{ $i + 1 }}</span>
                        @endif
                        <span class="text-xs font-semibold uppercase tracking-wide {{ $step['done'] ? 'text-brand-700' : 'text-gray-400' }}">
                            {{ $step['done'] ? __('app.ui.dashboard.done') : __('app.ui.dashboard.step', ['n' => $i + 1]) }}
                        </span>
                    </div>
                    <h3 class="font-semibold text-gray-900">{{ $step['title'] }}</h3>
                    <p class="text-sm text-gray-500 mt-1 flex-1">{{ $step['desc'] }}</p>
                    <div class="mt-4">
                        <a href="{{ $step['url'] }}" class="{{ $isNext ? 'btn-primary' : 'btn-secondary' }} !py-2">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                            {{ $step['action'] }}
                        </a>
                    </div>
                </li>
            @endforeach
        </ol>
    </div>
@endsection
