@extends('layouts.client')

@section('title', __('app.nav.user'))

@section('content')
    @php
        $currentStatus = request('status');
        $statusMeta = [
            'active' => ['badge' => 'bg-green-50 text-green-700', 'dot' => 'bg-green-500'],
            'used' => ['badge' => 'bg-blue-50 text-blue-700', 'dot' => 'bg-blue-500'],
            'expired' => ['badge' => 'bg-gray-100 text-gray-600', 'dot' => 'bg-gray-400'],
            'disabled' => ['badge' => 'bg-red-50 text-red-700', 'dot' => 'bg-red-500'],
        ];
    @endphp

    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-4 mb-6">
        <div>
            <h1 class="page-title">{{ __('app.nav.user') }}</h1>
            <p class="page-subtitle">{{ __('app.user_index.subtitle') }}</p>
        </div>
        <a href="{{ route('client.users.create') }}" class="btn-primary self-start sm:self-auto">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
            {{ __('app.client_dashboard.create_user') }}
        </a>
    </div>

    {{-- Status filter tabs --}}
    <div class="flex flex-wrap gap-2 mb-4">
        <a href="{{ route('client.users.index') }}" class="inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-medium border transition {{ ! $currentStatus ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
            {{ __('app.ui.all') }}
        </a>
        @foreach ($statusMeta as $key => $meta)
            <a href="{{ route('client.users.index', ['status' => $key]) }}" title="{{ __('app.ui.user_status.'.$key.'_desc') }}" class="inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-medium border transition {{ $currentStatus === $key ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                <span class="dot {{ $meta['dot'] }}"></span>
                {{ __('app.ui.user_status.'.$key) }}
                <span class="hidden md:inline text-xs {{ $currentStatus === $key ? 'text-white/60' : 'text-gray-400' }}">— {{ __('app.ui.user_status.'.$key.'_desc') }}</span>
            </a>
        @endforeach
    </div>

    <div class="info-box mb-4">
        <svg class="w-5 h-5 shrink-0 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01" stroke-linecap="round"/></svg>
        <p>{{ __('app.ui.users.online_hint') }}</p>
    </div>

    {{-- Selection toolbar (visible when rows are selected) --}}
    <div id="selectionBar" class="hidden mb-4 items-center justify-between gap-3 rounded-xl bg-gray-900 text-white px-4 py-2.5 text-sm">
        <div><span id="selectedCount" class="font-semibold">0</span> {{ __('app.user_index.selected_suffix') }}</div>
        <div class="flex items-center gap-2">
            <button type="button" id="clearSelectionBtn" class="px-3 py-1.5 rounded-lg text-white/80 hover:bg-white/10">{{ __('app.ui.users.clear_selection') }}</button>
            <button type="button" id="bulkDeleteBtn" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-red-600 hover:bg-red-500 font-medium" disabled>
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18" stroke-linecap="round"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ __('app.user_index.bulk_delete') }}
            </button>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto px-5 pt-5">
            <table id="usersTable" class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                        <th class="px-5 py-3 w-8"><input type="checkbox" id="checkAll" title="{{ __('app.ui.users.select_all') }}" class="rounded border-gray-300 text-brand-600 focus:ring-brand-500"></th>
                        <th class="px-5 py-3">{{ __('app.user_index.col_username') }}</th>
                        <th class="px-5 py-3">{{ __('app.user_index.col_password') }}</th>
                        <th class="px-5 py-3">{{ __('app.user_index.col_profile') }}</th>
                        <th class="px-5 py-3">{{ __('app.user_index.col_speed') }}</th>
                        <th class="px-5 py-3">{{ __('app.user_index.col_shared') }}</th>
                        <th class="px-5 py-3">{{ __('app.user_index.col_status') }}</th>
                        <th class="px-5 py-3" title="{{ __('app.ui.users.online_title') }}">{{ __('app.user_index.col_online') }}</th>
                        <th class="px-5 py-3">{{ __('app.user_index.col_expiry') }}</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-5 py-3.5"><input type="checkbox" class="row-check rounded border-gray-300 text-brand-600 focus:ring-brand-500" value="{{ $user->id }}"></td>
                            <td class="px-5 py-3.5">
                                <div class="font-mono font-medium">{{ $user->username }}</div>
                                @if ($user->note)
                                    <div class="text-xs text-gray-400 truncate max-w-[14rem]">{{ $user->note }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 font-mono text-gray-500">{{ $user->password }}</td>
                            <td class="px-5 py-3.5">{{ $user->profile->name ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ $user->profile->rate_up ?? '-' }} / {{ $user->profile->rate_down ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-gray-600">{{ $user->profile->simultaneous_use ?? '-' }}</td>
                            <td class="px-5 py-3.5">
                                @php $meta = $statusMeta[$user->status] ?? $statusMeta['expired']; @endphp
                                <span class="badge {{ $meta['badge'] }}" title="{{ __('app.ui.user_status.'.$user->status.'_desc') }}"><span class="dot {{ $meta['dot'] }}"></span>{{ __('app.ui.user_status.'.$user->status) }}</span>
                            </td>
                            <td class="px-5 py-3.5">
                                @php $onlineCount = $onlineCounts[$user->username] ?? 0; @endphp
                                <button
                                    type="button"
                                    title="{{ __('app.ui.users.online_title') }}"
                                    class="session-btn inline-flex items-center gap-1.5 font-mono text-xs font-semibold px-2.5 py-1 rounded-full {{ $onlineCount > 0 ? 'bg-green-50 text-green-700 ring-1 ring-green-200 hover:bg-green-100' : 'bg-gray-100 text-gray-500 hover:bg-gray-200' }}"
                                    data-user-id="{{ $user->id }}"
                                ><span class="dot {{ $onlineCount > 0 ? 'bg-green-500' : 'bg-gray-300' }}"></span>{{ $onlineCount }}/{{ $user->profile->simultaneous_use ?? '-' }}</button>
                            </td>
                            <td class="px-5 py-3.5 text-gray-500">{{ $user->expires_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2 whitespace-nowrap">
                                    <a href="{{ route('client.users.edit', $user) }}" class="btn-sm-edit">
                                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16.5 3.5a2.121 2.121 0 013 3L7 19l-4 1 1-4L16.5 3.5z" stroke-linejoin="round"/><path d="M12 20h9" stroke-linecap="round"/></svg>
                                        {{ __('app.user_index.edit') }}
                                    </a>
                                    <form method="POST" action="{{ route('client.users.destroy', $user) }}" onsubmit="return confirm('{{ __('app.user_index.confirm_delete', ['username' => $user->username]) }}')">
                                        @csrf @method('DELETE')
                                        <button class="btn-sm-danger">
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18" stroke-linecap="round"/><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            {{ __('app.user_index.delete') }}
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

    <form id="bulkDeleteForm" method="POST" action="{{ route('client.users.bulk-destroy') }}" class="hidden">
        @csrf
        <div id="bulkDeleteInputs"></div>
    </form>

    <div id="sessionModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 p-4">
        <div class="bg-white rounded-2xl shadow-xl max-w-5xl w-full max-h-[85vh] overflow-hidden flex flex-col">
            <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between shrink-0">
                <h3 class="font-semibold">{{ __('app.user_index.active_sessions') }}</h3>
                <button type="button" id="sessionModalClose" class="p-1.5 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-700" aria-label="{{ __('app.ui.close') }}">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/></svg>
                </button>
            </div>
            <div class="overflow-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">
                            <th class="px-4 py-2.5">{{ __('app.user_index.col_username') }}</th>
                            <th class="px-4 py-2.5">{{ __('app.ui.devices.device_name') }}</th>
                            <th class="px-4 py-2.5">{{ __('app.user_index.modal_ip') }}</th>
                            <th class="px-4 py-2.5">{{ __('app.user_index.modal_mac') }}</th>
                            <th class="px-4 py-2.5">{{ __('app.user_index.modal_limiter') }}</th>
                            <th class="px-4 py-2.5">{{ __('app.user_index.modal_start') }}</th>
                            <th class="px-4 py-2.5">{{ __('app.user_index.modal_expires') }}</th>
                            <th class="px-4 py-2.5">{{ __('app.user_index.modal_uptime') }}</th>
                            <th class="px-4 py-2.5">{{ __('app.user_index.modal_volume') }}</th>
                            <th class="px-4 py-2.5">{{ __('app.user_index.modal_action') }}</th>
                        </tr>
                    </thead>
                    <tbody id="sessionModalBody" class="divide-y divide-gray-100"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const i18n = {
            loading: @json(__('app.user_index.loading')),
            noActiveSessions: @json(__('app.user_index.no_active_sessions')),
            loadSessionsError: @json(__('app.user_index.load_sessions_error')),
            killSession: @json(__('app.user_index.kill_session')),
            disconnecting: @json(__('app.user_index.disconnecting')),
            disconnectConfirm: @json(__('app.user_index.disconnect_confirm')),
            success: @json(__('app.user_index.success')),
            failed: @json(__('app.user_index.failed')),
            disconnectError: @json(__('app.user_index.disconnect_error')),
            confirmBulkDelete: @json(__('app.user_index.confirm_bulk_delete')),
        };

        $(function () {
            const table = $('#usersTable').DataTable({
                order: [],
                pageLength: 25,
                language: {
                    search: '',
                    searchPlaceholder: '{{ __('app.user_index.search_placeholder') }}',
                    emptyTable: '{{ __('app.user_index.empty_table') }}',
                    zeroRecords: '{{ __('app.user_index.zero_records') }}',
                    lengthMenu: '{{ __('app.datatable.length_menu') }}',
                    info: '{{ __('app.user_index.info') }}',
                    infoEmpty: '{{ __('app.user_index.info_empty') }}',
                    infoFiltered: '{{ __('app.user_index.info_filtered') }}',
                    paginate: { previous: '{{ __('app.datatable.paginate_previous') }}', next: '{{ __('app.datatable.paginate_next') }}' },
                },
                columnDefs: [
                    { orderable: false, searchable: false, targets: [0, 7, -1] },
                ],
            });

            const bulkBtn = document.getElementById('bulkDeleteBtn');
            const selectionBar = document.getElementById('selectionBar');
            const selectedCountEl = document.getElementById('selectedCount');
            const checkAll = document.getElementById('checkAll');

            function updateToolbar() {
                const checked = table.$('.row-check:checked').length;
                selectedCountEl.textContent = checked;
                bulkBtn.disabled = checked === 0;
                selectionBar.classList.toggle('hidden', checked === 0);
                selectionBar.classList.toggle('flex', checked > 0);

                const visibleRows = table.rows({ search: 'applied' }).nodes();
                const visibleChecked = $(visibleRows).find('.row-check:checked').length;
                const visibleTotal = visibleRows.length;
                checkAll.checked = visibleTotal > 0 && visibleChecked === visibleTotal;
                checkAll.indeterminate = visibleChecked > 0 && visibleChecked < visibleTotal;
            }

            checkAll.addEventListener('change', () => {
                const rows = table.rows({ search: 'applied' }).nodes();
                $(rows).find('.row-check').prop('checked', checkAll.checked);
                updateToolbar();
            });

            document.querySelector('#usersTable tbody').addEventListener('change', (e) => {
                if (e.target.classList.contains('row-check')) {
                    updateToolbar();
                }
            });

            table.on('search.dt draw.dt', updateToolbar);

            document.getElementById('clearSelectionBtn').addEventListener('click', () => {
                table.$('.row-check').prop('checked', false);
                updateToolbar();
            });

            bulkBtn.addEventListener('click', () => {
                const ids = table.$('.row-check:checked').map(function () { return this.value; }).get();
                if (!ids.length) return;
                if (!confirm(i18n.confirmBulkDelete.replace(':count', ids.length))) return;

                const container = document.getElementById('bulkDeleteInputs');
                container.innerHTML = '';
                ids.forEach((id) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    container.appendChild(input);
                });
                document.getElementById('bulkDeleteForm').submit();
            });
        });
    </script>

    <script>
        (function () {
            const modal = document.getElementById('sessionModal');
            const body = document.getElementById('sessionModalBody');
            let currentUserId = null;

            function escapeHtml(str) {
                const div = document.createElement('div');
                div.textContent = str ?? '';
                return div.innerHTML;
            }

            function openModal(userId) {
                currentUserId = userId;
                body.innerHTML = `<tr><td colspan="10" class="px-4 py-6 text-center text-gray-400">${escapeHtml(i18n.loading)}</td></tr>`;
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                loadSessions(userId);
            }

            function closeModal() {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                currentUserId = null;
            }

            function loadSessions(userId) {
                fetch(`/client/users/${userId}/sessions`, { headers: { Accept: 'application/json' } })
                    .then((r) => r.json())
                    .then((data) => {
                        if (!data.sessions || !data.sessions.length) {
                            body.innerHTML = `<tr><td colspan="10" class="px-4 py-6 text-center text-gray-400">${escapeHtml(i18n.noActiveSessions)}</td></tr>`;
                            return;
                        }
                        body.innerHTML = data.sessions.map((s) => `
                            <tr>
                                <td class="px-4 py-2.5 font-mono">${escapeHtml(s.username)}</td>
                                <td class="px-4 py-2.5 font-medium">${escapeHtml(s.device_name)}</td>
                                <td class="px-4 py-2.5">${escapeHtml(s.ip_address)}</td>
                                <td class="px-4 py-2.5 font-mono text-xs">${escapeHtml(s.mac_address)}</td>
                                <td class="px-4 py-2.5">${escapeHtml(s.limiter)}</td>
                                <td class="px-4 py-2.5">${escapeHtml(s.start_time)}</td>
                                <td class="px-4 py-2.5">${escapeHtml(s.expires_at)}</td>
                                <td class="px-4 py-2.5">${escapeHtml(s.uptime)}</td>
                                <td class="px-4 py-2.5">${escapeHtml(s.volume)}</td>
                                <td class="px-4 py-2.5">
                                    <button type="button" class="kill-btn btn-sm-danger" data-id="${s.id}">${escapeHtml(i18n.killSession)}</button>
                                </td>
                            </tr>
                        `).join('');
                    })
                    .catch(() => {
                        body.innerHTML = `<tr><td colspan="10" class="px-4 py-6 text-center text-red-500">${escapeHtml(i18n.loadSessionsError)}</td></tr>`;
                    });
            }

            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.session-btn');
                if (btn) openModal(btn.dataset.userId);
            });

            document.getElementById('sessionModalClose').addEventListener('click', closeModal);
            modal.addEventListener('click', (e) => {
                if (e.target === modal) closeModal();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && ! modal.classList.contains('hidden')) closeModal();
            });

            body.addEventListener('click', (e) => {
                const btn = e.target.closest('.kill-btn');
                if (!btn || !currentUserId) return;
                if (!confirm(i18n.disconnectConfirm)) return;

                btn.disabled = true;
                btn.textContent = i18n.disconnecting;

                fetch(`/client/users/${currentUserId}/sessions/${btn.dataset.id}/kill`, {
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
                            loadSessions(currentUserId);
                        } else {
                            btn.disabled = false;
                            btn.textContent = i18n.killSession;
                        }
                    })
                    .catch(() => {
                        alert(i18n.disconnectError);
                        btn.disabled = false;
                        btn.textContent = i18n.killSession;
                    });
            });
        })();
    </script>
@endsection
