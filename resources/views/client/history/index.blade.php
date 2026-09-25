@extends('layouts.client')

@section('title', __('app.history.title'))

@section('content')
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
        <div>
            <h1 class="page-title">{{ __('app.history.title') }}</h1>
            <p class="page-subtitle">{{ __('app.history.subtitle') }}</p>
        </div>
        <a href="{{ route('client.history.export', request()->query()) }}" class="btn-secondary self-start sm:self-auto">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4v11M7 10l5 5 5-5M5 20h14" stroke-linecap="round" stroke-linejoin="round"/></svg>
            {{ __('app.history.export') }}
        </a>
    </div>

    <div class="info-box mb-4">
        <svg class="w-5 h-5 shrink-0 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01" stroke-linecap="round"/></svg>
        <p>{{ __('app.history.retention', ['days' => \App\Http\Controllers\Client\HistoryController::RETENTION_DAYS]) }}</p>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('client.history.index') }}" class="card p-4 mb-4">
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-12 items-end">
            <div class="lg:col-span-2">
                <label class="label">{{ __('app.history.from') }}</label>
                <input type="date" name="from" value="{{ $filters['from'] }}" min="{{ today()->subDays(\App\Http\Controllers\Client\HistoryController::RETENTION_DAYS)->toDateString() }}" max="{{ today()->toDateString() }}" class="input">
            </div>
            <div class="lg:col-span-2">
                <label class="label">{{ __('app.history.to') }}</label>
                <input type="date" name="to" value="{{ $filters['to'] }}" min="{{ today()->subDays(\App\Http\Controllers\Client\HistoryController::RETENTION_DAYS)->toDateString() }}" max="{{ today()->toDateString() }}" class="input">
            </div>
            <div class="lg:col-span-2">
                <label class="label">{{ __('app.nav.router') }}</label>
                <select name="router" class="select">
                    <option value="">{{ __('app.history.all_routers') }}</option>
                    @foreach ($routers as $router)
                        <option value="{{ $router->id }}" @selected($filters['router'] === $router->id)>{{ $router->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2 lg:col-span-4">
                <label class="label">{{ __('app.history.search') }}</label>
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="{{ __('app.history.search_placeholder') }}" class="input">
            </div>
            <div class="sm:col-span-2 lg:col-span-2 flex gap-2">
                <button type="submit" class="btn-primary flex-1">{{ __('app.history.apply') }}</button>
                <a href="{{ route('client.history.index') }}" class="btn-secondary">{{ __('app.history.reset') }}</a>
            </div>
        </div>
    </form>

    <div class="card overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 text-sm text-gray-500">
            {{ __('app.history.total', ['n' => number_format($sessions->total())]) }}
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <th class="px-5 py-3 whitespace-nowrap">{{ __('app.history.col_start') }}</th>
                        <th class="px-5 py-3">{{ __('app.history.col_duration') }}</th>
                        <th class="px-5 py-3">{{ __('app.user_index.col_username') }}</th>
                        <th class="px-5 py-3">{{ __('app.ui.devices.device_name') }}</th>
                        <th class="px-5 py-3">{{ __('app.user_index.modal_ip') }} / MAC</th>
                        <th class="px-5 py-3">{{ __('app.nav.router') }}</th>
                        <th class="px-5 py-3">{{ __('app.history.col_data') }}</th>
                        <th class="px-5 py-3">{{ __('app.history.col_reason') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($sessions as $s)
                        <tr class="hover:bg-gray-50 align-top">
                            <td class="px-5 py-3 whitespace-nowrap">
                                <div class="text-gray-900">{{ $s['start'] }}</div>
                                <div class="text-xs text-gray-400">→ {{ $s['stop'] ?? __('app.history.still_online') }}</div>
                            </td>
                            <td class="px-5 py-3 whitespace-nowrap text-gray-700">{{ $s['duration'] }}</td>
                            <td class="px-5 py-3 font-mono font-medium">{{ $s['username'] }}</td>
                            <td class="px-5 py-3 font-medium text-gray-800">{{ $s['device_name'] }}</td>
                            <td class="px-5 py-3 whitespace-nowrap">
                                <div class="font-mono text-xs text-gray-700">{{ $s['ip'] }}</div>
                                <div class="font-mono text-xs text-gray-400">{{ $s['mac'] }}</div>
                            </td>
                            <td class="px-5 py-3 text-gray-600">{{ $s['router'] }}</td>
                            <td class="px-5 py-3 whitespace-nowrap font-mono text-xs text-gray-700">
                                <div title="{{ __('app.history.col_download') }}">↓ {{ $s['download'] }}</div>
                                <div title="{{ __('app.history.col_upload') }}" class="text-gray-400">↑ {{ $s['upload'] }}</div>
                            </td>
                            <td class="px-5 py-3">
                                @if ($s['online'])
                                    <span class="badge bg-green-50 text-green-700"><span class="dot bg-green-500 animate-pulse"></span>{{ __('app.history.still_online') }}</span>
                                @else
                                    <span class="text-gray-600">{{ $s['reason'] }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-gray-400">{{ __('app.history.empty') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($sessions->hasPages())
            <div class="px-5 py-4 border-t border-gray-100">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>
@endsection
