@extends('layouts.client')

@section('title', __('app.hotspot_template.title'))

@section('content')
    <div class="mb-7">
        <h1 class="page-title">{{ __('app.hotspot_template.title') }}</h1>
        <p class="page-subtitle">{{ __('app.hotspot_template.subtitle') }}</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-5 items-start">
        <div class="lg:col-span-3 space-y-6">
            <form id="tplForm" method="POST" action="{{ route('client.hotspot-template.update') }}" enctype="multipart/form-data" class="card">
                @csrf
                @method('PUT')
                <div class="divide-y divide-gray-100">
                    <section class="p-6 grid md:grid-cols-3 gap-4 md:gap-6">
                        <div>
                            <h2 class="section-title">{{ __('app.hotspot_template.section_design') }}</h2>
                            <p class="section-desc">{{ __('app.hotspot_template.section_design_desc') }}</p>
                        </div>
                        <div class="md:col-span-2 space-y-4">
                            @php $theme = old('theme', $template->theme); @endphp
                            <div class="grid grid-cols-3 gap-3">
                                @foreach ([
                                    'modern' => 'background: linear-gradient(140deg, var(--sw), #064e3b)',
                                    'minimal' => 'background: #f3f4f6',
                                    'dark' => 'background: #0b1120',
                                ] as $value => $bg)
                                    <label class="cursor-pointer rounded-xl border border-gray-200 p-2 hover:bg-gray-50 has-[:checked]:border-brand-600 has-[:checked]:ring-1 has-[:checked]:ring-brand-600">
                                        <input type="radio" name="theme" value="{{ $value }}" @checked($theme === $value) class="sr-only">
                                        <span class="block h-16 rounded-lg relative" style="--sw: #10b981; {{ $bg }}">
                                            <span class="absolute inset-x-4 top-3 bottom-3 rounded {{ $value === 'dark' ? 'bg-gray-800' : 'bg-white' }} {{ $value === 'minimal' ? 'border-t-4 border-brand-600' : '' }}"></span>
                                        </span>
                                        <span class="block text-center text-xs font-medium mt-2">{{ __('app.hotspot_template.themes.'.$value) }}</span>
                                    </label>
                                @endforeach
                            </div>
                            <div>
                                <label class="label">{{ __('app.hotspot_template.color') }}</label>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <input type="color" name="primary_color" id="primaryColor" value="{{ old('primary_color', $template->primary_color) }}" class="h-10 w-14 cursor-pointer rounded-lg border border-gray-300 bg-white p-1">
                                    @foreach (['#059669', '#2563eb', '#7c3aed', '#db2777', '#dc2626', '#ea580c', '#0f172a'] as $swatch)
                                        <button type="button" data-swatch="{{ $swatch }}" class="w-7 h-7 rounded-full border-2 border-white ring-1 ring-gray-200" style="background: {{ $swatch }}" aria-label="{{ $swatch }}"></button>
                                    @endforeach
                                </div>
                            </div>
                            <div>
                                <label class="label">{{ __('app.hotspot_template.background') }} <span class="font-normal text-gray-400">({{ __('app.ui.optional') }})</span></label>
                                @if ($template->background_path)
                                    <div class="flex items-center gap-3 mb-2">
                                        <img src="{{ route('client.hotspot-template.preview', $template->backgroundFileName()) }}" alt="" class="h-14 w-24 object-cover rounded-lg border border-gray-200">
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                                            <input type="checkbox" name="remove_background" value="1" class="rounded border-gray-300">
                                            {{ __('app.hotspot_template.remove_background') }}
                                        </label>
                                    </div>
                                @endif
                                <input type="file" name="background" data-max-kb="500" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200">
                                <p class="hint">{{ __('app.hotspot_template.background_hint') }}</p>
                                <p class="hint !text-red-600 font-medium hidden" data-too-big="background"></p>
                            </div>
                            <div>
                                <label class="label">{{ __('app.hotspot_template.page_lang') }}</label>
                                <select name="page_lang" class="select">
                                    <option value="id" @selected(old('page_lang', $template->page_lang) === 'id')>Bahasa Indonesia</option>
                                    <option value="en" @selected(old('page_lang', $template->page_lang) === 'en')>English</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="p-6 grid md:grid-cols-3 gap-4 md:gap-6">
                        <div>
                            <h2 class="section-title">{{ __('app.hotspot_template.section_identity') }}</h2>
                            <p class="section-desc">{{ __('app.hotspot_template.section_identity_desc') }}</p>
                        </div>
                        <div class="md:col-span-2 space-y-4">
                            <div>
                                <label class="label">{{ __('app.hotspot_template.hotspot_name') }}</label>
                                <input name="title" value="{{ old('title', $template->title) }}" required maxlength="60" class="input">
                            </div>
                            <div>
                                <label class="label">{{ __('app.hotspot_template.welcome_text') }} <span class="font-normal text-gray-400">({{ __('app.ui.optional') }})</span></label>
                                <textarea name="welcome_text" rows="2" maxlength="255" class="input" placeholder="{{ __('app.hotspot_page.login_lead') }}">{{ old('welcome_text', $template->welcome_text) }}</textarea>
                            </div>
                            <div>
                                <label class="label">{{ __('app.hotspot_template.logo') }} <span class="font-normal text-gray-400">({{ __('app.ui.optional') }})</span></label>
                                @if ($template->logo_path)
                                    <div class="flex items-center gap-3 mb-2">
                                        <img src="{{ route('client.hotspot-template.preview', $template->logoFileName()) }}" alt="" class="h-10 max-w-[120px] object-contain rounded border border-gray-200 bg-gray-50 p-1">
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-600">
                                            <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300">
                                            {{ __('app.hotspot_template.remove_logo') }}
                                        </label>
                                    </div>
                                @endif
                                <input type="file" name="logo" data-max-kb="200" accept="image/png,image/jpeg,image/gif,image/webp" class="block w-full text-sm text-gray-600 file:mr-3 file:rounded-lg file:border-0 file:bg-gray-100 file:px-3 file:py-2 file:text-sm file:font-medium hover:file:bg-gray-200">
                                <p class="hint">{{ __('app.hotspot_template.logo_hint') }}</p>
                                <p class="hint !text-red-600 font-medium hidden" data-too-big="logo"></p>
                            </div>
                        </div>
                    </section>

                    <section class="p-6 grid md:grid-cols-3 gap-4 md:gap-6">
                        <div>
                            <h2 class="section-title">{{ __('app.hotspot_template.section_login') }}</h2>
                            <p class="section-desc">{{ __('app.hotspot_template.section_login_desc') }}</p>
                        </div>
                        <div class="md:col-span-2 space-y-4">
                            @php $mode = old('login_mode', $template->login_mode); @endphp
                            <div class="grid sm:grid-cols-2 gap-3">
                                @foreach (['userpass', 'voucher'] as $value)
                                    <label class="flex gap-3 rounded-xl border border-gray-200 p-3.5 cursor-pointer hover:bg-gray-50 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/60 has-[:checked]:ring-1 has-[:checked]:ring-brand-600">
                                        <input type="radio" name="login_mode" value="{{ $value }}" @checked($mode === $value) class="mt-0.5 border-gray-300 text-brand-600 focus:ring-brand-500">
                                        <span>
                                            <span class="block text-sm font-medium text-gray-900">{{ __('app.hotspot_template.modes.'.$value) }}</span>
                                            <span class="block text-xs text-gray-500 mt-0.5">{{ __('app.hotspot_template.modes.'.$value.'_desc') }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <div>
                                <label class="label">{{ __('app.hotspot_template.redirect_url') }} <span class="font-normal text-gray-400">({{ __('app.ui.optional') }})</span></label>
                                <input type="url" name="redirect_url" value="{{ old('redirect_url', $template->redirect_url) }}" maxlength="255" class="input" placeholder="https://nusa.id">
                                <p class="hint">{{ __('app.hotspot_template.redirect_url_hint') }}</p>
                            </div>
                            <div>
                                <label class="label">{{ __('app.hotspot_template.contact') }} <span class="font-normal text-gray-400">({{ __('app.ui.optional') }})</span></label>
                                <input name="contact" value="{{ old('contact', $template->contact) }}" maxlength="100" class="input" placeholder="WA 0812-3456-7890">
                            </div>
                            <div>
                                <label class="label">{{ __('app.hotspot_template.footer') }} <span class="font-normal text-gray-400">({{ __('app.ui.optional') }})</span></label>
                                <input name="footer_text" value="{{ old('footer_text', $template->footer_text) }}" maxlength="255" class="input" placeholder="© {{ date('Y') }} {{ $template->title }}">
                            </div>
                        </div>
                    </section>
                </div>
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end">
                    <button type="submit" class="btn-primary">{{ __('app.hotspot_template.save') }}</button>
                </div>
            </form>

            <div class="card">
                <div class="card-header flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                    <div>
                        <h2 class="section-title">{{ __('app.hotspot_template.apply_title') }}</h2>
                        <p class="section-desc">{{ __('app.hotspot_template.apply_desc') }}</p>
                    </div>
                    <details class="shrink-0 text-right">
                        <summary class="cursor-pointer list-none text-xs text-gray-500">
                            {{ __('app.hotspot_template.template_size') }}
                            <span class="font-semibold text-gray-900">{{ \Illuminate\Support\Number::fileSize(array_sum($fileSizes), 1) }}</span>
                            <span class="text-gray-400">· {{ __('app.hotspot_template.files_count', ['count' => count($fileSizes)]) }}</span>
                        </summary>
                        <dl class="mt-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-left min-w-[200px]">
                            @foreach ($fileSizes as $file => $bytes)
                                <div class="flex justify-between gap-4 py-0.5"><dt class="font-mono text-gray-600">{{ $file }}</dt><dd class="text-gray-900">{{ \Illuminate\Support\Number::fileSize($bytes, 1) }}</dd></div>
                            @endforeach
                        </dl>
                    </details>
                </div>
                @if (! $template->exists)
                    <p class="px-6 py-5 text-sm text-gray-500">{{ __('app.hotspot_template.save_first') }}</p>
                @elseif ($routers->isEmpty())
                    <p class="px-6 py-5 text-sm text-gray-500">{{ __('app.hotspot_template.no_router') }}</p>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach ($routers as $router)
                            @php
                                $applied = $router->login_template_applied_at;
                                $stale = $applied && $template->updated_at && $template->updated_at->gt($applied);
                            @endphp
                            <li class="px-6 py-4 flex flex-col sm:flex-row sm:items-center gap-3 justify-between">
                                <div class="min-w-0">
                                    <div class="text-sm font-medium truncate">{{ $router->name }}</div>
                                    <div class="text-xs text-gray-500 font-mono">{{ $router->api_host }}</div>
                                    <div class="mt-1.5 text-xs text-gray-500" data-storage="{{ $router->id }}">{{ __('app.hotspot_template.storage_checking') }}</div>
                                    <div class="mt-1">
                                        @if (! $applied)
                                            <span class="badge bg-gray-100 text-gray-600">{{ __('app.hotspot_template.status_default') }}</span>
                                        @elseif ($stale)
                                            <span class="badge bg-amber-50 text-amber-700"><span class="dot bg-amber-500"></span>{{ __('app.hotspot_template.status_stale') }}</span>
                                        @else
                                            <span class="badge bg-green-50 text-green-700"><span class="dot bg-green-500"></span>{{ __('app.hotspot_template.status_applied', ['time' => $applied->format('d M Y H:i')]) }}</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    @if ($applied)
                                        <form method="POST" action="{{ route('client.hotspot-template.restore', $router) }}" data-busy>
                                            @csrf
                                            <button type="submit" class="btn-sm-edit">{{ __('app.hotspot_template.restore') }}</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('client.hotspot-template.apply', $router) }}" data-busy>
                                        @csrf
                                        <button type="submit" class="btn-sm bg-brand-600 text-white hover:bg-brand-700">{{ $applied ? __('app.hotspot_template.reapply') : __('app.hotspot_template.apply') }}</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <p class="px-6 py-3 border-t border-gray-100 text-xs text-gray-500">{{ __('app.hotspot_template.apply_note') }}</p>
                @endif
            </div>
        </div>

        <div class="lg:col-span-2 lg:sticky lg:top-8">
            <div class="card p-4">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <h2 class="section-title">{{ __('app.hotspot_template.preview') }}</h2>
                    <div class="flex rounded-lg border border-gray-200 overflow-hidden text-xs font-medium" id="previewTabs">
                        @foreach (\App\Services\HotspotTemplateRenderer::PREVIEW_PAGES as $page)
                            <button type="button" data-page="{{ $page }}" class="px-3 py-1.5 {{ $loop->first ? 'bg-brand-600 text-white' : 'text-gray-500 hover:bg-gray-50' }}">{{ __('app.hotspot_template.pages.'.$page) }}</button>
                        @endforeach
                    </div>
                </div>
                <div class="mx-auto max-w-[360px] rounded-[2rem] border-[10px] border-gray-900 overflow-hidden bg-gray-900">
                    <iframe id="previewFrame" sandbox="allow-same-origin" title="{{ __('app.hotspot_template.preview') }}" src="{{ route('client.hotspot-template.preview', 'login') }}" class="block w-full h-[600px] bg-white"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const form = document.getElementById('tplForm');
            const frame = document.getElementById('previewFrame');
            const base = @json(url('/client/hotspot-template/preview'));
            const skip = ['_token', '_method', 'logo', 'remove_logo', 'background', 'remove_background'];
            let page = 'login';
            let timer;

            const refresh = () => {
                const params = new URLSearchParams();
                for (const [key, value] of new FormData(form)) {
                    if (! skip.includes(key)) params.append(key, value);
                }
                frame.src = base + '/' + page + '?' + params;
            };
            const later = () => { clearTimeout(timer); timer = setTimeout(refresh, 350); };

            // Show a just-picked logo/background in the preview before it is
            // saved: the iframe is same-origin, so patch its page after each load.
            const picked = {};
            const tooBig = @json(__('app.hotspot_template.file_too_big'));
            const showPicked = () => {
                const doc = frame.contentDocument;
                if (! doc || ! doc.body) return;
                if (picked.background) {
                    doc.body.classList.add('has-bg');
                    doc.body.style.background = 'linear-gradient(rgba(0,0,0,.35), rgba(0,0,0,.35)), url("' + picked.background + '") center / cover no-repeat';
                    const footer = doc.querySelector('.footer');
                    if (footer) { footer.style.color = '#fff'; footer.style.textShadow = '0 1px 3px rgba(0,0,0,.6)'; }
                }
                if (picked.logo) {
                    const old = doc.querySelector('.logo, .mark');
                    if (old) {
                        const img = doc.createElement('img');
                        img.className = 'logo';
                        img.src = picked.logo;
                        old.replaceWith(img);
                    }
                }
            };
            frame.addEventListener('load', showPicked);
            form.querySelectorAll('input[type=file][data-max-kb]').forEach((input) => input.addEventListener('change', () => {
                const file = input.files[0];
                const warn = form.querySelector('[data-too-big="' + input.name + '"]');
                const maxKb = Number(input.dataset.maxKb);
                warn.classList.add('hidden');
                if (picked[input.name]) URL.revokeObjectURL(picked[input.name]);
                delete picked[input.name];
                if (file && file.size > maxKb * 1024) {
                    warn.textContent = tooBig.replace(':size', Math.round(file.size / 1024) + ' KB').replace(':max', maxKb + ' KB');
                    warn.classList.remove('hidden');
                    input.value = '';
                } else if (file) {
                    picked[input.name] = URL.createObjectURL(file);
                }
                refresh();
            }));

            form.addEventListener('input', later);
            form.addEventListener('change', later);

            document.querySelectorAll('[data-swatch]').forEach((btn) => btn.addEventListener('click', () => {
                document.getElementById('primaryColor').value = btn.dataset.swatch;
                refresh();
            }));

            document.querySelectorAll('#previewTabs [data-page]').forEach((btn) => btn.addEventListener('click', () => {
                page = btn.dataset.page;
                document.querySelectorAll('#previewTabs [data-page]').forEach((b) => {
                    const on = b === btn;
                    b.classList.toggle('bg-brand-600', on);
                    b.classList.toggle('text-white', on);
                    b.classList.toggle('text-gray-500', ! on);
                });
                refresh();
            }));

            const needed = {{ array_sum($fileSizes) }};
            const storageText = {{ \Illuminate\Support\Js::from([
                'label' => __('app.hotspot_template.storage_label'),
                'unknown' => __('app.hotspot_template.storage_unknown'),
                'low' => __('app.hotspot_template.storage_low'),
            ]) }};
            fetch(@json(route('client.hotspot-template.storage')), { headers: { Accept: 'application/json' } })
                .then((r) => r.json())
                .then((rows) => rows.forEach((row) => {
                    const el = document.querySelector('[data-storage="' + row.id + '"]');
                    if (! el) return;
                    if (row.free === null) { el.textContent = storageText.unknown; return; }
                    const low = row.free < needed;
                    el.innerHTML = '';
                    const text = document.createElement('div');
                    text.className = low ? 'text-red-600 font-medium' : '';
                    text.textContent = storageText.label.replace(':free', row.free_human).replace(':total', row.total_human) + (low ? ' — ' + storageText.low : '');
                    const bar = document.createElement('div');
                    bar.className = 'mt-1 h-1.5 w-40 rounded-full bg-gray-200 overflow-hidden';
                    const fill = document.createElement('div');
                    fill.className = 'h-full ' + (low ? 'bg-red-500' : row.used_percent > 85 ? 'bg-amber-500' : 'bg-brand-600');
                    fill.style.width = (row.used_percent ?? 0) + '%';
                    bar.appendChild(fill);
                    el.append(text, bar);
                }))
                .catch(() => document.querySelectorAll('[data-storage]').forEach((el) => { el.textContent = storageText.unknown; }));

            // Applying waits on the router downloading every file — lock the
            // button so a second click doesn't start another run.
            document.querySelectorAll('form[data-busy]').forEach((f) => f.addEventListener('submit', () => {
                f.querySelector('button').disabled = true;
                f.querySelector('button').textContent = @json(__('app.hotspot_template.working'));
            }));
        })();
    </script>
@endsection
