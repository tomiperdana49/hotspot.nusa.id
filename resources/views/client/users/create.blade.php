@extends('layouts.client')

@section('title', __('app.client_dashboard.create_user'))

@section('content')
    <div class="mb-7">
        <a href="{{ route('client.users.index') }}" class="text-sm text-gray-500 hover:text-gray-800 mb-2 inline-flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            {{ __('app.nav.user') }}
        </a>
        <h1 class="page-title">{{ __('app.client_dashboard.create_user') }}</h1>
        <p class="page-subtitle max-w-2xl">{{ __('app.ui.user_create.subtitle') }}</p>
    </div>

    @if ($profiles->isEmpty())
        <div class="card p-8 max-w-lg text-center">
            <div class="w-12 h-12 mx-auto rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-4">
                <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z" stroke-linejoin="round"/><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5" stroke-linejoin="round"/></svg>
            </div>
            <p class="text-sm text-gray-600 mb-5">
                {{ __('app.user_create.no_profile') }} {{ __('app.user_create.create_profile_first') }} {{ __('app.user_create.before_generate') }}
            </p>
            <a href="{{ route('client.profiles.create') }}" class="btn-primary">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14" stroke-linecap="round"/></svg>
                {{ __('app.ui.dashboard.step2_action') }}
            </a>
        </div>
    @else
        <div class="grid lg:grid-cols-5 gap-6 items-start">
            {{-- Single --}}
            <div class="card lg:col-span-2">
                <div class="card-header flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8" stroke-linecap="round"/></svg>
                    </div>
                    <div>
                        <h2 class="font-semibold">{{ __('app.user_create.single_user') }}</h2>
                        <p class="text-sm text-gray-500">{{ __('app.ui.user_create.single_desc') }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('client.users.store') }}" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="label">{{ __('app.user_index.col_profile') }}</label>
                        <select name="profile_id" required class="select">
                            @foreach ($profiles as $profile)
                                <option value="{{ $profile->id }}">{{ $profile->name }}@if ($profile->rate_down) — {{ $profile->rate_up ?? '-' }}/{{ $profile->rate_down }}@endif</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="label">{{ __('app.user_index.col_username') }}</label>
                        <input name="username" value="{{ old('username') }}" required maxlength="64" pattern="\S+" autocomplete="off" class="input font-mono">
                    </div>
                    <div>
                        <label class="label">{{ __('app.user_index.col_password') }}</label>
                        <input name="password" value="{{ old('password') }}" required maxlength="64" autocomplete="off" class="input font-mono">
                    </div>
                    <div>
                        <label class="label">{{ __('app.user_create.note_label') }}</label>
                        <input name="note" value="{{ old('note') }}" class="input" maxlength="190">
                    </div>
                    <button type="submit" class="btn-primary w-full">{{ __('app.user_create.generate_one') }}</button>
                </form>
            </div>

            {{-- Batch --}}
            <div class="card lg:col-span-3">
                <div class="card-header flex items-start gap-3">
                    <div class="w-10 h-10 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c0-3.6 2.9-6.5 6.5-6.5s6.5 2.9 6.5 6.5" stroke-linecap="round"/><path d="M15.5 4.8a3.5 3.5 0 010 6.4M18 14c2 .9 3.5 3.2 3.5 6" stroke-linecap="round"/></svg>
                    </div>
                    <div>
                        <h2 class="font-semibold">{{ __('app.user_create.batch_title') }}</h2>
                        <p class="text-sm text-gray-500">{{ __('app.ui.user_create.batch_desc') }}</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('client.users.batch') }}" class="p-6 space-y-4" id="batchForm">
                    @csrf
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="label">{{ __('app.user_index.col_profile') }}</label>
                            <select name="profile_id" required class="select">
                                @foreach ($profiles as $profile)
                                    <option value="{{ $profile->id }}">{{ $profile->name }}@if ($profile->rate_down) — {{ $profile->rate_up ?? '-' }}/{{ $profile->rate_down }}@endif</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">{{ __('app.user_create.batch_name_label') }}</label>
                            <input name="name" required placeholder="{{ __('app.user_create.batch_name_placeholder') }}" class="input">
                        </div>
                        <div>
                            <label class="label">{{ __('app.user_create.qty_label') }}</label>
                            <input type="number" name="qty" value="50" min="1" max="1000" required class="input">
                            <p class="hint">{{ __('app.ui.user_create.qty_hint') }}</p>
                        </div>
                        <div>
                            <label class="label">{{ __('app.user_create.code_length_label') }}</label>
                            <input type="number" name="code_length" value="8" min="4" max="20" required class="input" data-preview>
                            <p class="hint">{{ __('app.ui.user_create.code_length_hint') }}</p>
                        </div>
                        <div>
                            <label class="label">{{ __('app.user_create.prefix_label') }}</label>
                            <input name="prefix" class="input" data-preview>
                            <p class="hint">{{ __('app.ui.user_create.prefix_hint') }}</p>
                        </div>
                        <div>
                            <label class="label">{{ __('app.user_create.charset_label') }}</label>
                            <select name="charset" class="select" data-preview>
                                <option value="alnum">{{ __('app.user_create.charset_alnum') }}</option>
                                <option value="numeric">{{ __('app.user_create.charset_numeric') }}</option>
                                <option value="lower">{{ __('app.user_create.charset_lower') }}</option>
                                <option value="upper">{{ __('app.user_create.charset_upper') }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="rounded-xl bg-gray-50 border border-gray-200 px-4 py-3 flex items-center justify-between gap-3 text-sm">
                        <span class="text-gray-500">{{ __('app.ui.user_create.example') }}</span>
                        <code id="usernamePreview" class="font-mono font-semibold text-gray-900 truncate"></code>
                    </div>

                    <div>
                        <span class="label">{{ __('app.user_create.password_mode_label') }}</span>
                        <div class="grid sm:grid-cols-3 gap-3">
                            @foreach (['random', 'shared', 'username'] as $pwMode)
                                <label class="flex gap-2.5 rounded-xl border border-gray-200 p-3 cursor-pointer hover:bg-gray-50 has-[:checked]:border-brand-600 has-[:checked]:bg-brand-50/60 has-[:checked]:ring-1 has-[:checked]:ring-brand-600">
                                    <input type="radio" name="password_mode" value="{{ $pwMode }}" @checked(old('password_mode', 'random') === $pwMode) class="mt-0.5 border-gray-300 text-brand-600 focus:ring-brand-500">
                                    <span>
                                        <span class="block text-sm font-medium text-gray-900">{{ __('app.user_create.password_modes.'.$pwMode) }}</span>
                                        <span class="block text-xs text-gray-500 mt-0.5">{{ __('app.user_create.password_modes.'.$pwMode.'_desc') }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <button type="submit" class="btn-primary w-full">{{ __('app.user_create.generate_batch') }}</button>
                </form>
            </div>
        </div>

        <script>
            (function () {
                const form = document.getElementById('batchForm');
                const out = document.getElementById('usernamePreview');
                const sets = {
                    alnum: 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789',
                    numeric: '0123456789',
                    lower: 'abcdefghijklmnopqrstuvwxyz',
                    upper: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                };
                function render() {
                    const len = Math.min(20, Math.max(4, parseInt(form.code_length.value, 10) || 8));
                    const chars = sets[form.charset.value] || sets.alnum;
                    let code = '';
                    for (let i = 0; i < len; i++) code += chars[Math.floor(Math.random() * chars.length)];
                    out.textContent = (form.prefix.value || '') + code;
                }
                form.querySelectorAll('[data-preview]').forEach((el) => el.addEventListener('input', render));
                render();
            })();
        </script>
    @endif
@endsection
