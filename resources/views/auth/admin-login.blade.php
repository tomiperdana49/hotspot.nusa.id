<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('app.admin_login.page_title') }} — Nusa Hotspot</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Inter','ui-sans-serif','system-ui'] } } } };</script>
    <style type="text/tailwindcss">
        @layer components {
            .input { @apply w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 shadow-sm transition focus:border-gray-900 focus:ring-2 focus:ring-gray-200 focus:outline-none; }
            .label { @apply block text-sm font-medium text-gray-700 mb-1.5; }
            /* Mark labels whose field is required with a red asterisk. */
            .label:has(+ :is(input, select, textarea)[required])::after { content: ' *'; @apply text-red-600; }
        }
    </style>
</head>
<body class="font-sans antialiased min-h-screen flex items-center justify-center px-4 bg-gray-950 relative overflow-hidden">
    <div class="absolute inset-0 opacity-30" style="background-image:radial-gradient(circle at 20% 20%, #4338ca 0%, transparent 40%), radial-gradient(circle at 80% 80%, #4f46e5 0%, transparent 35%);"></div>
    <div class="absolute top-4 right-4 flex rounded-lg border border-white/20 overflow-hidden text-xs font-medium">
        <a href="{{ route('locale.switch', 'id') }}" class="px-3 py-1.5 {{ app()->getLocale() === 'id' ? 'bg-white text-gray-900' : 'text-white/70 hover:bg-white/10' }}">ID</a>
        <a href="{{ route('locale.switch', 'en') }}" class="px-3 py-1.5 {{ app()->getLocale() === 'en' ? 'bg-white text-gray-900' : 'text-white/70 hover:bg-white/10' }}">EN</a>
    </div>
    <div class="relative w-full max-w-sm">
        <div class="flex items-center gap-2.5 justify-center mb-6">
            <div class="w-9 h-9 rounded-lg bg-white text-gray-900 flex items-center justify-center text-sm font-bold">NH</div>
            <span class="text-white font-semibold">Nusa Hotspot</span>
        </div>
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h1 class="text-lg font-semibold text-gray-900 mb-1">{{ __('app.admin_login.heading') }}</h1>
            <p class="text-sm text-gray-500 mb-6">{{ __('app.admin_login.subtitle') }}</p>

            @if ($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 text-red-700 border border-red-200 px-4 py-3 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="label">{{ __('app.admin_clients_create.email') }}</label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus class="input">
                </div>
                <div>
                    <label class="label">{{ __('app.admin_clients_create.owner_password') }}</label>
                    <input type="password" name="password" required class="input">
                </div>
                <button type="submit" class="w-full bg-gray-900 text-white rounded-lg py-2.5 text-sm font-semibold hover:bg-gray-700 transition shadow-sm">
                    {{ __('app.client_login.submit') }}
                </button>
            </form>
        </div>
        <a href="{{ route('login') }}" class="block text-center text-xs text-gray-400 mt-6 hover:text-white transition">{{ __('app.admin_login.client_login_link') }} →</a>
    </div>
</body>
</html>
