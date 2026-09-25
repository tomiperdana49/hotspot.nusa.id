@extends('layouts.client')

@section('title', __('app.router_index.title'))

@section('content')
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
        <div>
            <h1 class="page-title">{{ __('app.router_index.title') }}</h1>
            <p class="page-subtitle">{{ __('app.router_index.subtitle') }}</p>
        </div>
        <a href="{{ route('client.routers.create') }}" class="btn-primary self-start sm:self-auto">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
            {{ __('app.router_index.add_router') }}
        </a>
    </div>

    @if ($routers->isEmpty())
        <div class="card p-10 text-center">
            <div class="w-14 h-14 mx-auto rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center mb-4">
                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M2.5 9.5a14 14 0 0119 0" stroke-linecap="round"/><path d="M5.8 13a9.5 9.5 0 0112.4 0" stroke-linecap="round"/><path d="M9 16.3a5 5 0 016 0" stroke-linecap="round"/><circle cx="12" cy="19.5" r="1.2" fill="currentColor" stroke="none"/></svg>
            </div>
            <h2 class="font-semibold text-gray-900">{{ __('app.ui.routers.empty_title') }}</h2>
            <p class="text-sm text-gray-500 mt-1 mb-5 max-w-sm mx-auto">{{ __('app.ui.routers.empty_desc') }}</p>
            <a href="{{ route('client.routers.create') }}" class="btn-primary">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                {{ __('app.router_index.add_router') }}
            </a>
        </div>
    @else
    <div class="card overflow-hidden">
        <div class="overflow-x-auto px-5 pt-5">
            <table id="routersTable" class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <th class="px-5 py-3">{{ __('app.router_index.col_identity') }}</th>
                        <th class="px-5 py-3">{{ __('app.router_index.col_ip') }}</th>
                        <th class="px-5 py-3">{{ __('app.router_index.col_status') }}</th>
                        <th class="px-5 py-3">{{ __('app.router_index.col_live') }}</th>
                        <th class="px-5 py-3">{{ __('app.router_index.col_last_seen') }}</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($routers as $router)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3.5 font-medium">{{ $router->name }}</td>
                            <td class="px-5 py-3.5 font-mono text-xs text-gray-500">{{ $router->nas_ip ?? '-' }}</td>
                            <td class="px-5 py-3.5">
                                @php $colors = ['pending' => 'bg-amber-50 text-amber-700', 'verified' => 'bg-green-50 text-green-700', 'disabled' => 'bg-red-50 text-red-700']; @endphp
                                @php $dots = ['pending' => 'bg-amber-500', 'verified' => 'bg-green-500', 'disabled' => 'bg-red-500']; @endphp
                                <span class="badge {{ $colors[$router->status] }}"><span class="dot {{ $dots[$router->status] }}"></span>{{ __('app.ui.router_status.'.$router->status) }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                @if ($router->status === 'verified')
                                    <span class="badge {{ $router->is_online ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $router->is_online ? 'bg-green-500' : 'bg-red-500' }}"></span>{{ $router->is_online ? __('app.router_index.online') : __('app.router_index.offline') }}
                                    </span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-gray-500">{{ $router->last_seen_at?->diffForHumans() ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('client.routers.show', $router) }}" class="btn-sm-edit">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12z" stroke-linejoin="round"/><circle cx="12" cy="12" r="3"/></svg>
                                    {{ __('app.router_index.detail') }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        $(function () {
            $('#routersTable').DataTable({
                order: [],
                pageLength: 25,
                language: {
                    search: '',
                    searchPlaceholder: '{{ __('app.router_index.search_placeholder') }}',
                    emptyTable: '{{ __('app.router_index.empty_table') }}',
                    zeroRecords: '{{ __('app.router_index.zero_records') }}',
                    lengthMenu: '{{ __('app.datatable.length_menu') }}',
                    info: '{{ __('app.router_index.info') }}',
                    infoEmpty: '{{ __('app.router_index.info_empty') }}',
                    infoFiltered: '{{ __('app.router_index.info_filtered') }}',
                    paginate: { previous: '{{ __('app.datatable.paginate_previous') }}', next: '{{ __('app.datatable.paginate_next') }}' },
                },
                columnDefs: [{ orderable: false, targets: -1 }],
            });
        });
    </script>
    @endif
@endsection
