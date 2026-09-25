@extends('layouts.client')

@section('title', __('app.nav.device_online'))

@section('content')
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
        <div>
            <h1 class="page-title flex items-center gap-3">
                {{ __('app.nav.device_online') }}
                <span class="badge bg-green-50 text-green-700 ring-1 ring-green-200 text-sm"><span class="dot bg-green-500 animate-pulse"></span>{{ __('app.ui.devices.total', ['n' => count($devices)]) }}</span>
            </h1>
            <p class="page-subtitle">{{ __('app.device_index.subtitle') }}</p>
        </div>
        <a href="{{ route('client.devices.index') }}" class="btn-secondary self-start sm:self-auto">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 11a8 8 0 10-2.3 5.7M20 4v7h-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            {{ __('app.ui.devices.refresh') }}
        </a>
    </div>

    <div class="info-box mb-4">
        <svg class="w-5 h-5 shrink-0 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01" stroke-linecap="round"/></svg>
        <p>{{ __('app.ui.devices.hint') }}</p>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto px-5 pt-5">
            <table id="devicesTable" class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <th class="px-5 py-3">{{ __('app.user_index.col_username') }}</th>
                        <th class="px-5 py-3">{{ __('app.ui.devices.device_name') }}</th>
                        <th class="px-5 py-3">{{ __('app.nav.router') }}</th>
                        <th class="px-5 py-3">{{ __('app.user_index.modal_ip') }}</th>
                        <th class="px-5 py-3">{{ __('app.user_index.modal_mac') }}</th>
                        <th class="px-5 py-3">{{ __('app.user_index.modal_limiter') }}</th>
                        <th class="px-5 py-3">{{ __('app.device_index.col_start') }}</th>
                        <th class="px-5 py-3">{{ __('app.user_index.modal_uptime') }}</th>
                        <th class="px-5 py-3">{{ __('app.user_index.modal_volume') }}</th>
                        <th class="px-5 py-3">{{ __('app.user_index.col_expiry') }}</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($devices as $device)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3.5 font-mono font-medium">{{ $device['username'] }}</td>
                            <td class="px-5 py-3.5 font-medium text-gray-800">{{ $device['device_name'] }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ $device['router_name'] }}</td>
                            <td class="px-5 py-3.5 font-mono text-xs text-gray-500">{{ $device['ip_address'] }}</td>
                            <td class="px-5 py-3.5 font-mono text-xs text-gray-500">{{ $device['mac_address'] }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ $device['limiter'] }}</td>
                            <td class="px-5 py-3.5 text-gray-500">{{ $device['start_time'] }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ $device['uptime'] }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ $device['volume'] }}</td>
                            <td class="px-5 py-3.5 text-gray-500">{{ $device['expires_at'] }}</td>
                            <td class="px-5 py-3.5 text-right">
                                @if ($device['hotspot_user_id'])
                                    <button
                                        type="button"
                                        class="kill-btn btn-sm-danger"
                                        data-user-id="{{ $device['hotspot_user_id'] }}"
                                        data-radacct-id="{{ $device['radacct_id'] }}"
                                    >
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18.36 5.64a9 9 0 11-12.73 0M12 3v7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                        {{ __('app.device_index.disconnect') }}
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const i18n = {
            disconnectConfirm: @json(__('app.device_index.disconnect_confirm')),
            disconnecting: @json(__('app.user_index.disconnecting')),
            success: @json(__('app.user_index.success')),
            failed: @json(__('app.user_index.failed')),
            disconnectError: @json(__('app.user_index.disconnect_error')),
        };

        $(function () {
            $('#devicesTable').DataTable({
                order: [],
                pageLength: 25,
                language: {
                    search: '',
                    searchPlaceholder: '{{ __('app.device_index.search_placeholder') }}',
                    emptyTable: '{{ __('app.device_index.empty_table') }}',
                    zeroRecords: '{{ __('app.device_index.zero_records') }}',
                    lengthMenu: '{{ __('app.datatable.length_menu') }}',
                    info: '{{ __('app.device_index.info') }}',
                    infoEmpty: '{{ __('app.device_index.info_empty') }}',
                    infoFiltered: '{{ __('app.device_index.info_filtered') }}',
                    paginate: { previous: '{{ __('app.datatable.paginate_previous') }}', next: '{{ __('app.datatable.paginate_next') }}' },
                },
                columnDefs: [{ orderable: false, searchable: false, targets: -1 }],
            });

            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.kill-btn');
                if (!btn) return;
                if (!confirm(i18n.disconnectConfirm)) return;

                btn.disabled = true;
                const original = btn.innerHTML;
                btn.textContent = i18n.disconnecting;

                fetch(`/client/users/${btn.dataset.userId}/sessions/${btn.dataset.radacctId}/kill`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        Accept: 'application/json',
                    },
                })
                    .then((r) => r.json().then((data) => ({ ok: r.ok, data })))
                    .then(({ ok, data }) => {
                        alert(data.message || (ok ? i18n.success : i18n.failed));
                        if (ok) {
                            btn.closest('tr').remove();
                        } else {
                            btn.disabled = false;
                            btn.innerHTML = original;
                        }
                    })
                    .catch(() => {
                        alert(i18n.disconnectError);
                        btn.disabled = false;
                        btn.innerHTML = original;
                    });
            });
        });
    </script>
@endsection
