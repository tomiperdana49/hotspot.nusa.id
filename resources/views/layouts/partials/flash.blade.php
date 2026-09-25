@if (session('status'))
    <div class="flash mb-5 flex items-start gap-3 rounded-xl bg-green-50 text-green-800 border border-green-200 px-4 py-3 text-sm">
        <svg class="w-5 h-5 shrink-0 text-green-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.5 2.5L16 9.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <div class="flex-1 pt-0.5 break-words">{{ session('status') }}</div>
        <button type="button" class="flash-close text-green-700/60 hover:text-green-900" aria-label="{{ __('app.ui.close') }}">&times;</button>
    </div>
@endif
@if (session('connect_error'))
    <div class="flash mb-5 flex items-start gap-3 rounded-xl bg-red-50 text-red-700 border border-red-200 px-4 py-3 text-sm">
        <svg class="w-5 h-5 shrink-0 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01" stroke-linecap="round"/></svg>
        <div class="flex-1 pt-0.5 break-words">{{ session('connect_error') }}</div>
        <button type="button" class="flash-close text-red-700/60 hover:text-red-900" aria-label="{{ __('app.ui.close') }}">&times;</button>
    </div>
@endif
@if ($errors->any())
    <div class="flash mb-5 flex items-start gap-3 rounded-xl bg-red-50 text-red-700 border border-red-200 px-4 py-3 text-sm">
        <svg class="w-5 h-5 shrink-0 text-red-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01" stroke-linecap="round"/></svg>
        <ul class="flex-1 pt-0.5 space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="flash-close text-red-700/60 hover:text-red-900" aria-label="{{ __('app.ui.close') }}">&times;</button>
    </div>
@endif
