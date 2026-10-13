{{-- Router dropdown for list pages; keeps the page's other query params. --}}
@if ($routers->count() > 1 || $selectedRouter)
    <form method="GET" class="flex items-center gap-2">
        @foreach (request()->except(['router', 'page']) as $name => $value)
            @if (is_string($value))
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endif
        @endforeach
        <label for="routerFilter" class="text-sm text-gray-500 whitespace-nowrap">{{ __('app.router_filter.label') }}</label>
        <select id="routerFilter" name="router" class="select !w-auto" onchange="this.form.submit()">
            <option value="">{{ __('app.router_filter.all') }}</option>
            @foreach ($routers as $router)
                <option value="{{ $router->id }}" @selected($selectedRouter?->id === $router->id)>{{ $router->name }}</option>
            @endforeach
        </select>
    </form>
@endif
