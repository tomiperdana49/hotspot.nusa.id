{{-- Shared <head> assets for the client & admin panels. Expects $brand = [50, 100, 500, 600, 700] hex colors. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui'] },
                colors: { brand: { 50:'{{ $brand[0] }}',100:'{{ $brand[1] }}',500:'{{ $brand[2] }}',600:'{{ $brand[3] }}',700:'{{ $brand[4] }}' } },
            },
        },
    };
</script>
<style type="text/tailwindcss">
    @layer base { body { font-feature-settings: "cv02","cv03","cv04","cv11"; } }
    @layer components {
        .input, .select { @apply w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 shadow-sm transition focus:border-brand-600 focus:ring-2 focus:ring-brand-100 focus:outline-none disabled:bg-gray-50 disabled:text-gray-500; }
        .label { @apply block text-sm font-medium text-gray-700 mb-1.5; }
        /* Mark labels whose field is required with a red asterisk. */
        .label:has(+ :is(input, select, textarea)[required])::after { content: ' *'; @apply text-red-600; }
        .hint { @apply text-xs text-gray-500 mt-1.5 leading-relaxed; }
        .btn { @apply inline-flex items-center justify-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold transition disabled:opacity-50 disabled:cursor-not-allowed; }
        .btn-primary { @apply btn bg-brand-600 text-white hover:bg-brand-700 shadow-sm; }
        .btn-secondary { @apply btn bg-white border border-gray-300 text-gray-700 hover:bg-gray-50; }
        .btn-sm { @apply inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium transition; }
        .btn-sm-edit { @apply btn-sm border border-gray-300 text-gray-700 bg-white hover:bg-gray-50; }
        .btn-sm-danger { @apply btn-sm border border-red-200 text-red-600 bg-white hover:bg-red-50; }
        .btn-danger-ghost { @apply text-red-600 hover:text-red-700 text-sm font-medium; }
        .card { @apply bg-white rounded-2xl border border-gray-200/80 shadow-sm; }
        .card-header { @apply px-6 py-4 border-b border-gray-100; }
        .badge { @apply inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium whitespace-nowrap; }
        .dot { @apply w-1.5 h-1.5 rounded-full; }
        .page-title { @apply text-2xl font-bold tracking-tight text-gray-900; }
        .page-subtitle { @apply text-sm text-gray-500 mt-1; }
        .info-box { @apply flex gap-3 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900; }
        .nav-link { @apply flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition; }
        .nav-link-active { @apply flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-semibold bg-brand-50 text-brand-700; }
        .section-title { @apply text-sm font-semibold text-gray-900; }
        .section-desc { @apply text-xs text-gray-500 mt-0.5; }
    }
</style>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
<style>
    .dataTables_wrapper { font-size: 0.875rem; }
    .dataTables_wrapper .dt-top, .dataTables_wrapper .dt-bottom { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 0.75rem; }
    .dataTables_wrapper .dt-top { margin-bottom: 1rem; }
    .dataTables_wrapper .dt-top > *, .dataTables_wrapper .dt-bottom > * { float: none !important; margin: 0 !important; padding: 0 !important; }
    .dataTables_wrapper .dt-bottom { padding: 1rem 0; }
    .dataTables_wrapper .dt-scroll { overflow-x: auto; margin: 0 -1.25rem; padding: 0 1.25rem; }
    .dataTables_wrapper .dataTables_filter { float: right; margin-bottom: 1rem; }
    .dataTables_wrapper .dataTables_filter input {
        border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.5rem 0.75rem 0.5rem 2.25rem;
        font-size: 0.875rem; margin-left: 0; outline: none; min-width: 240px;
        background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2'%3E%3Ccircle cx='11' cy='11' r='7'/%3E%3Cpath d='M20 20l-3.5-3.5' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat 0.75rem center / 1rem;
    }
    .dataTables_wrapper .dataTables_filter input:focus { border-color: {{ $brand[3] }}; box-shadow: 0 0 0 2px {{ $brand[1] }}; }
    .dataTables_wrapper .dataTables_length { margin-bottom: 1rem; color: #6b7280; }
    .dataTables_wrapper .dataTables_length select {
        border: 1px solid #d1d5db; border-radius: 0.5rem; padding: 0.25rem 1.75rem 0.25rem 0.5rem; font-size: 0.875rem;
    }
    .dataTables_wrapper .dataTables_info { font-size: 0.75rem; color: #6b7280; padding: 1rem 0; }
    .dataTables_wrapper .dataTables_paginate { padding: 0.75rem 0; }
    .dataTables_wrapper .dataTables_paginate .paginate_button {
        padding: 0.375rem 0.75rem; margin-left: 0.25rem; border-radius: 0.5rem;
        border: 1px solid #d1d5db !important; font-size: 0.8rem; cursor: pointer; background: #fff !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button:hover { background: #f9fafb !important; color: #111827 !important; }
    .dataTables_wrapper .dataTables_paginate .paginate_button.current,
    .dataTables_wrapper .dataTables_paginate .paginate_button.current:hover {
        background: {{ $brand[3] }} !important; color: #fff !important; border-color: {{ $brand[3] }} !important;
    }
    .dataTables_wrapper .dataTables_paginate .paginate_button.disabled { color: #9ca3af !important; cursor: default; }
    table.dataTable { border-collapse: collapse !important; }
    table.dataTable thead th { position: relative; border-bottom: 1px solid #e5e7eb !important; }
    table.dataTable.no-footer { border-bottom: 1px solid #f3f4f6 !important; }
    table.dataTable tbody td.dataTables_empty { padding: 2.5rem 1rem; color: #9ca3af; text-align: center; }
    @media (max-width: 640px) {
        .dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_length { float: none; text-align: left; }
        .dataTables_wrapper .dataTables_filter input { min-width: 0; width: 100%; }
        .dataTables_wrapper .dataTables_filter label { display: block; }
    }
</style>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
    $.extend(true, $.fn.dataTable.defaults, {
        dom: '<"dt-top"lf><"dt-scroll"t><"dt-bottom"ip>',
    });
</script>
