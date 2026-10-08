<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — Nusa Hotspot</title>
    @include('layouts.partials.head', ['brand' => ['#ecfdf5', '#d1fae5', '#10b981', '#059669', '#047857']])
    @livewireStyles
</head>
<body class="bg-gray-50 font-sans text-gray-900 antialiased">
    @php
        $navItems = [
            ['route' => 'client.dashboard', 'match' => 'client.dashboard', 'label' => __('app.nav.dashboard'), 'desc' => __('app.ui.nav_desc.dashboard'),
             'icon' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>'],
            ['route' => 'client.routers.index', 'match' => 'client.routers.*', 'label' => __('app.nav.router'), 'desc' => __('app.ui.nav_desc.router'),
             'icon' => '<path d="M2.5 9.5a14 14 0 0119 0" stroke-linecap="round"/><path d="M5.8 13a9.5 9.5 0 0112.4 0" stroke-linecap="round"/><path d="M9 16.3a5 5 0 016 0" stroke-linecap="round"/><circle cx="12" cy="19.5" r="1.2" fill="currentColor" stroke="none"/>'],
            ['route' => 'client.profiles.index', 'match' => 'client.profiles.*', 'label' => __('app.nav.profile'), 'desc' => __('app.ui.nav_desc.profile'),
             'icon' => '<path d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z" stroke-linejoin="round"/><path d="M12 12l8-4.5M12 12v9M12 12L4 7.5" stroke-linejoin="round"/>'],
            ['route' => 'client.hotspot-template.edit', 'match' => 'client.hotspot-template.*', 'label' => __('app.nav.hotspot_template'), 'desc' => __('app.ui.nav_desc.hotspot_template'),
             'icon' => '<rect x="3.5" y="3.5" width="17" height="17" rx="2.5"/><path d="M3.5 8.5h17" stroke-linecap="round"/><rect x="8" y="12" width="8" height="2.2" rx="1.1"/><rect x="8" y="15.8" width="8" height="2.2" rx="1.1"/>'],
            ['route' => 'client.users.index', 'match' => 'client.users.*', 'label' => __('app.nav.user'), 'desc' => __('app.ui.nav_desc.user'),
             'icon' => '<path d="M4 8a2 2 0 012-2h12a2 2 0 012 2v1.5a1.8 1.8 0 000 3.5V14.5a1.8 1.8 0 000 3.5V19a2 2 0 01-2 2H6a2 2 0 01-2-2v-1a1.8 1.8 0 000-3.5V11.5a1.8 1.8 0 000-3.5V8z" stroke-linejoin="round"/><path d="M10 6v14" stroke-dasharray="2 2"/>'],
            ['route' => 'client.devices.index', 'match' => 'client.devices.*', 'label' => __('app.nav.device_online'), 'desc' => __('app.ui.nav_desc.device'),
             'icon' => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4" stroke-linecap="round"/>'],
            ['route' => 'client.history.index', 'match' => 'client.history.*', 'label' => __('app.nav.history'), 'desc' => __('app.ui.nav_desc.history'),
             'icon' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2" stroke-linecap="round" stroke-linejoin="round"/>'],
        ];
        $clientName = auth('client')->check() ? auth('client')->user()->client->name : null;
    @endphp

    {{-- Mobile top bar --}}
    <header class="lg:hidden sticky top-0 z-30 h-14 bg-white border-b border-gray-200 flex items-center justify-between px-4">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-brand-600 text-white flex items-center justify-center text-xs font-bold">NH</div>
            <span class="text-sm font-semibold">Nusa Hotspot</span>
        </div>
        <button type="button" id="sidebarOpen" class="p-2 -mr-2 rounded-lg text-gray-600 hover:bg-gray-100" aria-label="{{ __('app.ui.menu') }}">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16" stroke-linecap="round"/></svg>
        </button>
    </header>

    <div id="sidebarBackdrop" class="fixed inset-0 z-40 bg-gray-900/40 hidden lg:hidden"></div>

    <div class="flex min-h-screen">
        <aside id="sidebar" class="fixed inset-y-0 left-0 z-50 w-72 bg-white border-r border-gray-200 flex flex-col -translate-x-full transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:w-64 lg:shrink-0">
            <div class="h-16 flex items-center gap-2.5 px-5 border-b border-gray-100">
                <div class="w-9 h-9 rounded-xl bg-brand-600 text-white flex items-center justify-center text-xs font-bold shadow-sm">NH</div>
                <div class="min-w-0 flex-1">
                    <div class="text-sm font-semibold leading-tight">Nusa Hotspot</div>
                    @if ($clientName)
                        <div class="text-xs text-gray-500 leading-tight truncate">{{ $clientName }}</div>
                    @endif
                </div>
                <button type="button" id="sidebarClose" class="lg:hidden p-1.5 rounded-lg text-gray-400 hover:bg-gray-100" aria-label="{{ __('app.ui.close') }}">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6l12 12M18 6L6 18" stroke-linecap="round"/></svg>
                </button>
            </div>
            <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1">
                @foreach ($navItems as $item)
                    @php $active = request()->routeIs($item['match']); @endphp
                    <a href="{{ route($item['route']) }}" class="{{ $active ? 'nav-link-active' : 'nav-link' }}">
                        <svg class="w-5 h-5 shrink-0 {{ $active ? 'text-brand-600' : 'text-gray-400' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">{!! $item['icon'] !!}</svg>
                        <span class="min-w-0">
                            <span class="block leading-tight">{{ $item['label'] }}</span>
                            <span class="block text-[11px] font-normal leading-tight mt-0.5 {{ $active ? 'text-brand-600/80' : 'text-gray-400' }}">{{ $item['desc'] }}</span>
                        </span>
                    </a>
                @endforeach
            </nav>
            <div class="p-3 border-t border-gray-100 space-y-2">
                <div class="flex items-center justify-between px-1">
                    <span class="text-xs text-gray-500">{{ __('app.ui.language') }}</span>
                    <div class="flex rounded-lg border border-gray-200 overflow-hidden text-xs font-medium">
                        <a href="{{ route('locale.switch', 'id') }}" class="px-3 py-1 {{ app()->getLocale() === 'id' ? 'bg-brand-600 text-white' : 'text-gray-500 hover:bg-gray-50' }}">ID</a>
                        <a href="{{ route('locale.switch', 'en') }}" class="px-3 py-1 {{ app()->getLocale() === 'en' ? 'bg-brand-600 text-white' : 'text-gray-500 hover:bg-gray-50' }}">EN</a>
                    </div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="nav-link w-full text-red-600 hover:bg-red-50 hover:text-red-700">
                        <svg class="w-5 h-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H6a2 2 0 01-2-2V5a2 2 0 012-2h3" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 17l5-5-5-5M21 12H9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        {{ __('app.nav.logout') }}
                    </button>
                </form>
            </div>
        </aside>
        <main class="flex-1 min-w-0">
            <div class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-10 py-6 lg:py-8">
                @if (session('impersonator_admin_id'))
                    <div class="mb-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        <p>{{ __('app.impersonate.banner', ['client' => Auth::guard('client')->user()->client?->name, 'email' => Auth::guard('client')->user()->email]) }}</p>
                        <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                            @csrf
                            <button class="btn-secondary !py-1.5">{{ __('app.impersonate.leave') }}</button>
                        </form>
                    </div>
                @endif
                @include('layouts.partials.flash')
                @yield('content')
            </div>
        </main>
    </div>

    <script>
        (function () {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            const open = () => { sidebar.classList.remove('-translate-x-full'); backdrop.classList.remove('hidden'); };
            const close = () => { sidebar.classList.add('-translate-x-full'); backdrop.classList.add('hidden'); };
            document.getElementById('sidebarOpen').addEventListener('click', open);
            document.getElementById('sidebarClose').addEventListener('click', close);
            backdrop.addEventListener('click', close);
            document.addEventListener('click', (e) => {
                const btn = e.target.closest('.flash-close');
                if (btn) btn.closest('.flash').remove();
            });
        })();
    </script>
    @livewireScripts
</body>
</html>
