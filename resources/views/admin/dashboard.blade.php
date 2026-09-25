@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-7">
        <h1 class="text-2xl font-bold tracking-tight">{{ __('app.admin_dashboard.title') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ __('app.admin_dashboard.subtitle') }}</p>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-8">
        <div class="card p-5">
            <div class="text-xs font-medium text-gray-500 mb-2">{{ __('app.admin_dashboard.total_clients') }}</div>
            <div class="text-3xl font-bold tracking-tight">{{ $stats['clients_total'] }}</div>
        </div>
        <div class="card p-5">
            <div class="text-xs font-medium text-gray-500 mb-2">{{ __('app.admin_dashboard.active_clients') }}</div>
            <div class="text-3xl font-bold tracking-tight text-green-600">{{ $stats['clients_active'] }}</div>
        </div>
        <div class="card p-5">
            <div class="text-xs font-medium text-gray-500 mb-2">{{ __('app.admin_dashboard.routers_verified') }}</div>
            <div class="text-3xl font-bold tracking-tight">{{ $stats['routers_verified'] }}</div>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100 flex justify-between items-center">
            <span class="font-semibold text-sm">{{ __('app.admin_dashboard.recent_clients') }}</span>
            <a href="{{ route('admin.clients.index') }}" class="text-sm text-gray-500 hover:text-gray-900 font-medium">{{ __('app.admin_dashboard.view_all') }} &rarr;</a>
        </div>
        <table class="w-full text-sm">
            <tbody class="divide-y divide-gray-100">
                @forelse ($recentClients as $client)
                    <tr class="hover:bg-gray-50">
                        <td class="px-5 py-3.5 font-mono text-xs text-gray-500">{{ $client->code }}</td>
                        <td class="px-5 py-3.5">
                            <div class="font-medium">{{ $client->name }}</div>
                            @if ($client->address)
                                <div class="text-xs text-gray-400">{{ $client->address }}</div>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            @if ($client->status === 'active')
                                <span class="badge bg-green-50 text-green-700"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>{{ $client->status }}</span>
                            @else
                                <span class="badge bg-red-50 text-red-700"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>{{ $client->status }}</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td class="px-5 py-8 text-center text-gray-400" colspan="3">{{ __('app.admin_dashboard.no_clients') }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
