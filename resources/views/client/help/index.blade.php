@extends('layouts.client')

@section('title', __('app.help.title'))

@section('content')
    @php
        $sections = [
            'start' => __('app.help.toc.start'),
            'router' => __('app.help.toc.router'),
            'profile' => __('app.help.toc.profile'),
            'user' => __('app.help.toc.user'),
            'template' => __('app.help.toc.template'),
            'devices' => __('app.help.toc.devices'),
            'history' => __('app.help.toc.history'),
            'faq' => __('app.help.toc.faq'),
        ];
    @endphp

    <div class="mb-7">
        <h1 class="page-title">{{ __('app.help.title') }}</h1>
        <p class="page-subtitle">{{ __('app.help.subtitle') }}</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-4 items-start">
        <aside class="lg:sticky lg:top-8 space-y-3">
            <div class="relative">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5" stroke-linecap="round"/></svg>
                <input type="search" id="helpSearch" class="input !pl-9" placeholder="{{ __('app.help.search') }}">
            </div>
            <nav class="card p-2 hidden lg:block" id="helpToc">
                @foreach ($sections as $id => $label)
                    <a href="#{{ $id }}" data-toc="{{ $id }}" class="block rounded-lg px-3 py-2 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900">{{ $label }}</a>
                @endforeach
            </nav>
        </aside>

        <div class="lg:col-span-3 space-y-6" id="helpBody">
            @include('client.help._'.(app()->getLocale() === 'en' ? 'en' : 'id'))
            <p id="helpEmpty" class="hidden card p-6 text-sm text-gray-500">{{ __('app.help.no_result') }}</p>
        </div>
    </div>

    <script>
        (function () {
            const sections = [...document.querySelectorAll('[data-help-section]')];
            const empty = document.getElementById('helpEmpty');

            // Search: keep sections (and open FAQ answers) whose text matches.
            document.getElementById('helpSearch').addEventListener('input', (e) => {
                const q = e.target.value.trim().toLowerCase();
                let shown = 0;
                sections.forEach((section) => {
                    const hit = ! q || section.textContent.toLowerCase().includes(q);
                    section.classList.toggle('hidden', ! hit);
                    shown += hit ? 1 : 0;
                    section.querySelectorAll('details').forEach((d) => {
                        d.open = q !== '' && d.textContent.toLowerCase().includes(q);
                    });
                });
                empty.classList.toggle('hidden', shown > 0);
            });

            // Highlight the section currently in view.
            const links = document.querySelectorAll('[data-toc]');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (! entry.isIntersecting) return;
                    links.forEach((a) => {
                        const on = a.dataset.toc === entry.target.id;
                        a.classList.toggle('bg-brand-50', on);
                        a.classList.toggle('text-brand-700', on);
                        a.classList.toggle('font-semibold', on);
                    });
                });
            }, { rootMargin: '-20% 0px -70% 0px' });
            sections.forEach((s) => observer.observe(s));
        })();
    </script>
@endsection
