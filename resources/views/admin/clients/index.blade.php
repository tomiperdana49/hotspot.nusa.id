@extends('layouts.admin')

@section('title', __('app.nav.clients'))

@section('content')
    <div class="flex justify-between items-center mb-7">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ __('app.nav.clients') }}</h1>
            <p class="text-sm text-gray-500 mt-1">{{ __('app.admin_clients_index.subtitle') }}</p>
        </div>
        <a href="{{ route('admin.clients.create') }}" class="btn-primary">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
            {{ __('app.admin_clients_index.new_client') }}
        </a>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto px-5 pt-5">
            <table id="clientsTable" class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <th class="px-5 py-3">{{ __('app.admin_clients_index.col_code') }}</th>
                        <th class="px-5 py-3">{{ __('app.profile_index.col_name') }}</th>
                        <th class="px-5 py-3">{{ __('app.user_index.col_status') }}</th>
                        <th class="px-5 py-3">{{ __('app.nav.router') }}</th>
                        <th class="px-5 py-3">{{ __('app.nav.user') }}</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($clients as $client)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3.5 font-mono text-xs text-gray-500">{{ $client->code }}</td>
                            <td class="px-5 py-3.5">
                                <a href="{{ route('admin.clients.show', $client) }}" class="font-medium hover:underline">{{ $client->name }}</a>
                                <div class="text-xs text-gray-400">{{ $client->email }}</div>
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
                            <td class="px-5 py-3.5 text-gray-600">{{ $client->routers_count }} / {{ $client->max_routers ?? '∞' }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ $client->hotspot_users_count }} / {{ $client->max_users ?? '∞' }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                    <a href="{{ route('admin.clients.show', $client) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 text-xs font-medium text-gray-700 hover:bg-gray-50">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z" stroke-linejoin="round"/><circle cx="12" cy="12" r="3"/></svg>
                                        {{ __('app.router_index.detail') }}
                                    </a>
                                    <a href="{{ route('admin.clients.edit', $client) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-brand-600 text-white text-xs font-medium hover:bg-brand-700">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" stroke-linejoin="round"/><path d="M12 20h9" stroke-linecap="round"/></svg>
                                        {{ __('app.user_index.edit') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        $(function () {
            $('#clientsTable').DataTable({
                order: [],
                pageLength: 25,
                language: {
                    search: '',
                    searchPlaceholder: '{{ __('app.admin_clients_index.search_placeholder') }}',
                    emptyTable: '{{ __('app.admin_clients_index.empty_table') }}',
                    zeroRecords: '{{ __('app.admin_clients_index.zero_records') }}',
                    lengthMenu: '{{ __('app.datatable.length_menu') }}',
                    info: '{{ __('app.admin_clients_index.info') }}',
                    infoEmpty: '{{ __('app.admin_clients_index.info_empty') }}',
                    infoFiltered: '{{ __('app.admin_clients_index.info_filtered') }}',
                    paginate: { previous: '{{ __('app.datatable.paginate_previous') }}', next: '{{ __('app.datatable.paginate_next') }}' },
                },
                columnDefs: [{ orderable: false, targets: -1 }],
            });
        });
    </script>
@endsection
