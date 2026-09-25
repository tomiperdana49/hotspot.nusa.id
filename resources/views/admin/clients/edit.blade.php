@extends('layouts.admin')

@section('title', __('app.admin_clients_edit.title'))

@section('content')
    <div class="mb-7">
        <h1 class="text-2xl font-bold tracking-tight">{{ __('app.admin_clients_edit.title') }}</h1>
        <p class="text-sm text-gray-500 mt-1 font-mono">{{ $client->code }}</p>
    </div>

    <form method="POST" action="{{ route('admin.clients.update', $client) }}" class="max-w-xl card p-6 space-y-4">
        @csrf
        @method('PUT')

        <div>
            <label class="label">{{ __('app.admin_clients_create.business_name') }}</label>
            <input name="name" value="{{ old('name', $client->name) }}" required class="input">
        </div>
        <div>
            <label class="label">{{ __('app.admin_clients_create.email') }}</label>
            <input type="email" name="email" value="{{ old('email', $client->email) }}" required class="input">
        </div>
        <div>
            <label class="label">{{ __('app.admin_clients_create.phone') }}</label>
            <input name="phone" value="{{ old('phone', $client->phone) }}" class="input">
        </div>
        <div>
            <label class="label">{{ __('app.admin_clients_create.address') }}</label>
            <textarea name="address" rows="2" class="input">{{ old('address', $client->address) }}</textarea>
        </div>
        <div>
            <label class="label">{{ __('app.user_index.col_status') }}</label>
            <select name="status" class="select">
                @foreach (['active', 'suspended', 'expired'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $client->status) === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="label">{{ __('app.admin_clients_edit.max_routers') }}</label>
                <input type="number" name="max_routers" value="{{ old('max_routers', $client->max_routers) }}" min="1" placeholder="{{ __('app.admin_clients_edit.unlimited') }}" class="input">
            </div>
            <div>
                <label class="label">{{ __('app.admin_clients_edit.max_users') }}</label>
                <input type="number" name="max_users" value="{{ old('max_users', $client->max_users) }}" min="1" placeholder="{{ __('app.admin_clients_edit.unlimited') }}" class="input">
            </div>
        </div>
        <div>
            <label class="label">{{ __('app.admin_clients_edit.valid_until') }}</label>
            <input type="date" name="expired_at" value="{{ old('expired_at', $client->expired_at?->format('Y-m-d')) }}" class="input">
        </div>

        <button type="submit" class="btn-primary mt-2">{{ __('app.profile_form.save_changes') }}</button>
    </form>
@endsection
