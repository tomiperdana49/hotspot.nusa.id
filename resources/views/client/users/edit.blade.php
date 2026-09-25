@extends('layouts.client')

@section('title', __('app.user_edit.title'))

@section('content')
    <div class="mb-7">
        <a href="{{ route('client.users.index') }}" class="text-sm text-gray-500 hover:text-gray-800 mb-2 inline-flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            {{ __('app.nav.user') }}
        </a>
        <h1 class="page-title">{{ __('app.user_edit.title') }}</h1>
        <p class="page-subtitle font-mono">{{ $user->username }}</p>
    </div>

    <form method="POST" action="{{ route('client.users.update', $user) }}" class="card max-w-3xl">
        @csrf
        @method('PUT')

        <div class="p-6 grid sm:grid-cols-2 gap-5">
            <div>
                <label class="label">{{ __('app.user_index.col_username') }}</label>
                <input name="username" value="{{ old('username', $user->username) }}" required class="input font-mono">
            </div>
            <div>
                <label class="label">{{ __('app.user_index.col_password') }}</label>
                <input name="password" value="{{ old('password', $user->password) }}" required class="input font-mono">
            </div>
            <div>
                <label class="label">{{ __('app.user_index.col_profile') }}</label>
                <select name="profile_id" class="select" required>
                    @foreach ($profiles as $profile)
                        <option value="{{ $profile->id }}" @selected(old('profile_id', $user->profile_id) == $profile->id)>{{ $profile->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ __('app.user_index.col_status') }}</label>
                <select name="status" class="select" required>
                    @foreach (['active', 'used', 'expired', 'disabled'] as $val)
                        <option value="{{ $val }}" @selected(old('status', $user->status) === $val)>{{ __('app.ui.user_status.'.$val) }} — {{ __('app.ui.user_status.'.$val.'_desc') }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">{{ __('app.user_index.col_expiry') }}</label>
                <input type="datetime-local" name="expires_at" value="{{ old('expires_at', $user->expires_at?->format('Y-m-d\TH:i')) }}" class="input">
                <p class="hint">{{ __('app.ui.user_edit.expiry_hint') }}</p>
            </div>
            <div>
                <label class="label">{{ __('app.user_edit.note_label') }} <span class="font-normal text-gray-400">({{ __('app.ui.optional') }})</span></label>
                <input name="note" value="{{ old('note', $user->note) }}" class="input">
            </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/60 rounded-b-2xl flex items-center justify-end gap-3">
            <a href="{{ route('client.users.index') }}" class="btn-secondary">{{ __('app.ui.profile_form.cancel') }}</a>
            <button type="submit" class="btn-primary">{{ __('app.profile_form.save_changes') }}</button>
        </div>
    </form>
@endsection
