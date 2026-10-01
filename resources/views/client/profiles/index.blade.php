@extends('layouts.client')

@section('title', __('app.nav.profile'))

@section('content')
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
        <div>
            <h1 class="page-title">{{ __('app.nav.profile') }}</h1>
            <p class="page-subtitle">{{ __('app.profile_index.subtitle') }}</p>
        </div>
        <a href="{{ route('client.profiles.create') }}" class="btn-primary self-start sm:self-auto">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
            {{ __('app.profile_index.new_profile') }}
        </a>
    </div>

    <div class="info-box mb-4">
        <svg class="w-5 h-5 shrink-0 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01" stroke-linecap="round"/></svg>
        <p>{{ __('app.ui.profiles.hint') }}</p>
    </div>

    @php
        $units = ['hour' => __('app.profile_form.unit_hour'), 'day' => __('app.profile_form.unit_day'), 'month' => __('app.profile_form.unit_month')];
    @endphp

    <div class="card overflow-hidden">
        <div class="overflow-x-auto px-5 pt-5">
            <table id="profilesTable" class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <th class="px-5 py-3">{{ __('app.profile_index.col_name') }}</th>
                        <th class="px-5 py-3">{{ __('app.profile_index.col_speed') }}</th>
                        <th class="px-5 py-3">{{ __('app.profile_form.bandwidth_mode_label') }}</th>
                        <th class="px-5 py-3">{{ __('app.profile_index.col_duration') }}</th>
                        <th class="px-5 py-3">{{ __('app.ui.profiles.col_devices') }}</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($profiles as $profile)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3.5">
                                <div class="font-medium">{{ $profile->name }}</div>
                                <div class="text-xs text-gray-400 font-mono">{{ $profile->group_name }}</div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3 font-mono text-xs text-gray-700 whitespace-nowrap">
                                    <span title="{{ __('app.profile_form.rate_up_label') }}">↑ {{ $profile->rate_up ?: __('app.ui.unlimited') }}</span>
                                    <span title="{{ __('app.profile_form.rate_down_label') }}">↓ {{ $profile->rate_down ?: __('app.ui.unlimited') }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                @if ($profile->bandwidth_mode === 'shared')
                                    <span class="badge bg-amber-50 text-amber-700" title="{{ __('app.ui.profile_form.bw_shared_desc') }}">{{ __('app.profile_form.bw_shared') }}</span>
                                @else
                                    <span class="badge bg-sky-50 text-sky-700" title="{{ __('app.ui.profile_form.bw_per_device_desc') }}">{{ __('app.profile_form.bw_per_device') }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-gray-700">
                                @if ($profile->validity_value && $profile->validity_unit)
                                    {{ $profile->validity_value }} {{ $units[$profile->validity_unit] ?? $profile->validity_unit }}
                                    <div class="text-xs text-gray-400">{{ $profile->validity_mode === 'from_create' ? __('app.profile_form.mode_from_create') : __('app.profile_form.mode_first_login') }}</div>
                                @else
                                    <span class="text-gray-400">{{ __('app.ui.unlimited') }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-gray-700">{{ __('app.ui.profiles.devices_count', ['n' => $profile->simultaneous_use ?? 1]) }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                    <a href="{{ route('client.profiles.edit', $profile) }}" class="btn-sm-edit">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" stroke-linejoin="round"/><path d="M12 20h9" stroke-linecap="round"/></svg>
                                        {{ __('app.profile_index.edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('client.profiles.destroy', $profile) }}" onsubmit="return confirm('{{ __('app.profile_index.confirm_delete') }}')">
                                        @csrf @method('DELETE')
                                        <button class="btn-sm-danger">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18" stroke-linecap="round"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            {{ __('app.profile_index.delete') }}
                                        </button>
                                    </form>
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
            $('#profilesTable').DataTable({
                order: [],
                pageLength: 25,
                language: {
                    search: '',
                    searchPlaceholder: '{{ __('app.profile_index.search_placeholder') }}',
                    emptyTable: '{{ __('app.profile_index.empty_table') }}',
                    zeroRecords: '{{ __('app.profile_index.zero_records') }}',
                    lengthMenu: '{{ __('app.datatable.length_menu') }}',
                    info: '{{ __('app.profile_index.info') }}',
                    infoEmpty: '{{ __('app.profile_index.info_empty') }}',
                    infoFiltered: '{{ __('app.profile_index.info_filtered') }}',
                    paginate: { previous: '{{ __('app.datatable.paginate_previous') }}', next: '{{ __('app.datatable.paginate_next') }}' },
                },
                columnDefs: [{ orderable: false, targets: -1 }],
            });
        });
    </script>
@endsection
