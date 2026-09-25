@extends('layouts.admin')

@section('title', __('app.admin_clients_create.title'))

@section('content')
    <div class="mb-7">
        <h1 class="text-2xl font-bold tracking-tight">{{ __('app.admin_clients_create.title') }}</h1>
        <p class="text-sm text-gray-500 mt-1">{{ __('app.admin_clients_create.subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('admin.clients.store') }}" class="max-w-xl space-y-6">
        @csrf

        <div class="card p-6">
            <h2 class="font-semibold text-sm mb-4 pb-4 border-b border-gray-100">{{ __('app.admin_clients_create.data_client') }}</h2>
            <div class="space-y-4">
                <div>
                    <label class="label">{{ __('app.admin_clients_create.business_name') }}</label>
                    <input name="name" value="{{ old('name') }}" required class="input">
                </div>
                <div>
                    <label class="label">{{ __('app.admin_clients_create.email') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" required class="input">
                </div>
                <div>
                    <label class="label">{{ __('app.admin_clients_create.phone') }}</label>
                    <input name="phone" value="{{ old('phone') }}" class="input">
                </div>
                <div>
                    <label class="label">{{ __('app.admin_clients_create.address') }}</label>
                    <textarea name="address" rows="2" class="input">{{ old('address') }}</textarea>
                </div>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="font-semibold text-sm mb-4 pb-4 border-b border-gray-100">{{ __('app.admin_clients_create.owner_account') }}</h2>
            <div class="space-y-4">
                <div>
                    <label class="label">{{ __('app.admin_clients_create.owner_name') }}</label>
                    <input name="owner_name" value="{{ old('owner_name') }}" required class="input">
                </div>
                <div>
                    <label class="label">{{ __('app.admin_clients_create.owner_email') }}</label>
                    <input type="email" name="owner_email" value="{{ old('owner_email') }}" required class="input">
                </div>
                <div>
                    <label class="label">{{ __('app.admin_clients_create.owner_password') }}</label>
                    <input type="password" name="owner_password" required minlength="8" class="input">
                </div>
            </div>
        </div>

        <button type="submit" class="btn-primary">{{ __('app.admin_clients_create.save') }}</button>
    </form>
@endsection
