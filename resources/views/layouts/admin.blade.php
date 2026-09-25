<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Nusa Hotspot</title>
    @include('layouts.partials.head', ['brand' => ['#eef2ff', '#e0e7ff', '#6366f1', '#4f46e5', '#4338ca']])
    @livewireStyles
</head>
<body class="bg-gray-50 font-sans text-gray-900 antialiased">
    @php
        $navItems = [
            ['route' => 'admin.dashboard', 'match' => 'admin.dashboard', 'label' => __('app.nav.dashboard'), 'desc' => __('app.admin_dashboard.subtitle'),
             'icon' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>'],
            ['route' => 'admin.clients.index', 'match' => 'admin.clients.*', 'label' => __('app.nav.clients'), 'desc' => __('app.admin_clients_index.subtitle'),
             'icon' => '<path d="M4 20c0-3.3 2.7-6 6-6s6 2.7 6 6" stroke-linecap="round"/><circle cx="10" cy="8" r="3.5"/><path d="M15.5 14.2c2.6.5 4.5 2.8 4.5 5.8" stroke-linecap="round"/><circle cx="16.5" cy="7.5" r="2.5"/>'],
        ];
        $clientName = __('app.nav.superadmin');
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
                <form method="POST" action="{{ route('admin.logout') }}">
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
