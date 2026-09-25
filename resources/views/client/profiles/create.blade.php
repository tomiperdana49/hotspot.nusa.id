@extends('layouts.client')

@section('title', __('app.profile_index.new_profile'))

@section('content')
    <div class="mb-7">
        <a href="{{ route('client.profiles.index') }}" class="text-sm text-gray-500 hover:text-gray-800 mb-2 inline-flex items-center gap-1">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            {{ __('app.nav.profile') }}
        </a>
        <h1 class="page-title">{{ __('app.profile_index.new_profile') }}</h1>
        <p class="page-subtitle max-w-2xl">{{ __('app.ui.profiles.hint') }}</p>
    </div>

    <form method="POST" action="{{ route('client.profiles.store') }}" class="card max-w-4xl">
        @csrf
        @include('client.profiles._form')
        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-end gap-3">
            <a href="{{ route('client.profiles.index') }}" class="btn-secondary">{{ __('app.ui.profile_form.cancel') }}</a>
            <button type="submit" class="btn-primary">{{ __('app.profile_form.save') }}</button>
        </div>
    </form>
@endsection
