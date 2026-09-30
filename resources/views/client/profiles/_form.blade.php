@php $p = $profile ?? null; @endphp

<div class="divide-y divide-gray-100">
    {{-- Basic --}}
    <section class="p-6 grid md:grid-cols-3 gap-4 md:gap-6">
        <div>
            <h2 class="section-title">{{ __('app.ui.profile_form.section_basic') }}</h2>
            <p class="section-desc">{{ __('app.ui.profile_form.section_basic_desc') }}</p>
        </div>
        <div class="md:col-span-2">
            <label class="label">{{ __('app.profile_form.name_label') }}</label>
            <input name="name" value="{{ old('name', $p?->name) }}" required class="input" data-pf="name">
        </div>
    </section>

    {{-- Speed --}}
    <section class="p-6 grid md:grid-cols-3 gap-4 md:gap-6">
        <div>
            <h2 class="section-title">{{ __('app.ui.profile_form.section_speed') }}</h2>
            <p class="section-desc">{{ __('app.ui.profile_form.section_speed_desc') }}</p>
        </div>
        <div class="md:col-span-2 grid sm:grid-cols-2 gap-4">
            <div>
                <label class="label flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    {{ __('app.profile_form.rate_up_label') }}
                </label>
                <input name="rate_up" placeholder="1M" value="{{ old('rate_up', $p?->rate_up) }}" class="input font-mono" data-pf="up">
            </div>
            <div>
                <label class="label flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M19 12l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    {{ __('app.profile_form.rate_down_label') }}
                </label>
                <input name="rate_down" placeholder="2M" value="{{ old('rate_down', $p?->rate_down) }}" class="input font-mono" data-pf="down">
            </div>
            <div class="sm:col-span-2">
                <span class="label">{{ __('app.profile_form.bandwidth_mode_label') }}</span>
                @php $bw = old('bandwidth_mode', $p?->bandwidth_mode ?? 'per_device'); @endphp
                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach (['per_device' => ['bw_per_device', 'bw_per_device_desc'], 'shared' => ['bw_shared', 'bw_shared_desc']] as $val => [$labelKey, $descKey])
                        <label class="relative flex gap-3 rounded-xl border border-gray-200 p-3.5 cursor-pointer hover:bg-gray-50 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/60 has-[:checked]:ring-1 has-[:checked]:ring-brand-600">
                            <input type="radio" name="bandwidth_mode" value="{{ $val }}" @checked($bw === $val) class="mt-0.5 border-gray-300 text-brand-600 focus:ring-brand-500" data-pf="bw">
                            <span>
                                <span class="block text-sm font-medium text-gray-900">{{ __('app.profile_form.'.$labelKey) }}</span>
                                <span class="block text-xs text-gray-500 mt-0.5">{{ __('app.ui.profile_form.'.$descKey) }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Validity --}}
    <section class="p-6 grid md:grid-cols-3 gap-4 md:gap-6">
        <div>
            <h2 class="section-title">{{ __('app.ui.profile_form.section_validity') }}</h2>
            <p class="section-desc">{{ __('app.ui.profile_form.section_validity_desc') }}</p>
        </div>
        <div class="md:col-span-2 space-y-4">
            <div>
                <label class="label">{{ __('app.profile_form.validity_value_label') }}</label>
                <div class="flex gap-2">
                    <input type="number" name="validity_value" min="1" value="{{ old('validity_value', $p?->validity_value) }}" class="input w-32 shrink-0" data-pf="vv">
                    <select name="validity_unit" class="select" data-pf="vu" aria-label="{{ __('app.profile_form.validity_unit_label') }}">
                        <option value="">— {{ __('app.profile_form.validity_unit_label') }} —</option>
                        @foreach (['hour' => __('app.profile_form.unit_hour'), 'day' => __('app.profile_form.unit_day'), 'month' => __('app.profile_form.unit_month')] as $val => $unitLabel)
                            <option value="{{ $val }}" @selected(old('validity_unit', $p?->validity_unit) === $val)>{{ $unitLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <span class="label">{{ __('app.profile_form.validity_mode_label') }}</span>
                @php $mode = old('validity_mode', $p?->validity_mode ?? 'from_first_login'); @endphp
                <div class="grid sm:grid-cols-2 gap-3">
                    @foreach (['from_first_login' => ['mode_first_login', 'mode_first_login_desc'], 'from_create' => ['mode_from_create', 'mode_from_create_desc']] as $val => [$labelKey, $descKey])
                        <label class="relative flex gap-3 rounded-xl border border-gray-200 p-3.5 cursor-pointer hover:bg-gray-50 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/60 has-[:checked]:ring-1 has-[:checked]:ring-brand-600">
                            <input type="radio" name="validity_mode" value="{{ $val }}" @checked($mode === $val) class="mt-0.5 border-gray-300 text-brand-600 focus:ring-brand-500">
                            <span>
                                <span class="block text-sm font-medium text-gray-900">{{ __('app.profile_form.'.$labelKey) }}</span>
                                <span class="block text-xs text-gray-500 mt-0.5">{{ __('app.ui.profile_form.'.$descKey) }}</span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Limits --}}
    <section class="p-6 grid md:grid-cols-3 gap-4 md:gap-6">
        <div>
            <h2 class="section-title">{{ __('app.ui.profile_form.section_limit') }}</h2>
            <p class="section-desc">{{ __('app.ui.profile_form.section_limit_desc') }}</p>
        </div>
        <div class="md:col-span-2 grid sm:grid-cols-2 gap-4">
            <div>
                <label class="label">{{ __('app.profile_form.simultaneous_use_label') }}</label>
                <input type="number" name="simultaneous_use" value="{{ old('simultaneous_use', $p?->simultaneous_use ?? 1) }}" min="1" required class="input" data-pf="su">
                <p class="hint">{{ __('app.ui.profile_form.simultaneous_hint') }}</p>
            </div>
            <div>
                <label class="label">{{ __('app.profile_form.mikrotik_group_label') }}</label>
                <input name="mikrotik_group" value="{{ old('mikrotik_group', $p?->mikrotik_group ?? \App\Services\MikrotikConnector::RADIUS_NUSA_PROFILE) }}" required class="input font-mono">
                <p class="hint">{{ __('app.profile_form.mikrotik_group_hint') }} <code class="bg-gray-100 px-1 rounded">IP → Hotspot → User Profiles</code>. {{ __('app.profile_form.mikrotik_group_hint2', ['profile' => \App\Services\MikrotikConnector::RADIUS_NUSA_PROFILE]) }}</p>
            </div>
            <div>
                <label class="label">{{ __('app.profile_form.session_timeout_label') }}</label>
                <input type="number" name="session_timeout" min="0" placeholder="{{ __('app.ui.no_limit') }}" value="{{ old('session_timeout', $p?->session_timeout) }}" class="input">
                <p class="hint">{{ __('app.ui.profile_form.session_timeout_hint') }}</p>
            </div>
            <div>
                <label class="label">{{ __('app.profile_form.idle_timeout_label') }}</label>
                <input type="number" name="idle_timeout" min="0" placeholder="{{ __('app.ui.no_limit') }}" value="{{ old('idle_timeout', $p?->idle_timeout) }}" class="input">
                <p class="hint">{{ __('app.ui.profile_form.idle_timeout_hint') }}</p>
            </div>
        </div>
    </section>

    {{-- Live summary --}}
    <section class="p-6 bg-gray-50/60">
        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">{{ __('app.ui.profile_form.preview') }}</div>
        <div class="flex flex-wrap items-center gap-2 text-sm">
            <span id="pfName" class="font-semibold text-gray-900"></span>
            <span class="badge bg-white border border-gray-200 text-gray-700">↑ <span id="pfUp"></span> · ↓ <span id="pfDown"></span></span>
            <span class="badge bg-white border border-gray-200 text-gray-700" id="pfBw"></span>
            <span class="badge bg-white border border-gray-200 text-gray-700" id="pfValidity"></span>
            <span class="badge bg-white border border-gray-200 text-gray-700" id="pfDevices"></span>
        </div>
    </section>
</div>

<script>
    (function () {
        const t = {
            unlimited: @json(__('app.ui.unlimited')),
            devices: @json(__('app.ui.profiles.devices_count')),
            units: {{ \Illuminate\Support\Js::from(['hour' => __('app.profile_form.unit_hour'), 'day' => __('app.profile_form.unit_day'), 'month' => __('app.profile_form.unit_month')]) }},
            bw: {{ \Illuminate\Support\Js::from(['per_device' => __('app.profile_form.bw_per_device'), 'shared' => __('app.profile_form.bw_shared')]) }},
        };
        const f = (k) => document.querySelector(`[data-pf="${k}"]`);
        function render() {
            document.getElementById('pfName').textContent = f('name').value || '—';
            document.getElementById('pfUp').textContent = f('up').value || t.unlimited;
            document.getElementById('pfDown').textContent = f('down').value || t.unlimited;
            document.getElementById('pfBw').textContent = '⇄ ' + t.bw[(document.querySelector('[data-pf="bw"]:checked') || {}).value || 'per_device'];
            const vv = f('vv').value, vu = f('vu').value;
            document.getElementById('pfValidity').textContent = '⏱ ' + (vv && vu ? `${vv} ${t.units[vu]}` : t.unlimited);
            document.getElementById('pfDevices').textContent = '📱 ' + t.devices.replace(':n', f('su').value || 1);
        }
        document.querySelectorAll('[data-pf]').forEach((el) => el.addEventListener('input', render));
        render();
    })();
</script>
