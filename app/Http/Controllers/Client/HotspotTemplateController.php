<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\HotspotTemplate;
use App\Models\Router;
use App\Services\HotspotTemplateRenderer;
use App\Services\MikrotikConnector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * The client's hotspot login page: one default design, plus an optional
 * design of its own per router. The editor works on one "scope" at a
 * time: "default", or a router id. A router without a design of its own
 * shows the default, and saving on its tab gives it one.
 */
class HotspotTemplateController extends Controller
{
    public function __construct(
        private readonly HotspotTemplateRenderer $renderer,
        private readonly MikrotikConnector $connector,
    ) {}

    public function edit(Request $request)
    {
        $client = Auth::guard('client')->user()->client;
        $routers = $this->verifiedRouters();
        $router = $this->scopeRouter((string) $request->query('router', 'default'), $routers);

        $default = HotspotTemplate::forClient($client);
        $own = $router ? HotspotTemplate::ownFor($router) : null;
        $template = $own ?? $default;
        $ownRouterIds = HotspotTemplate::where('client_id', $client->id)->whereNotNull('router_id')->pluck('router_id')->all();

        $fileSizes = $this->renderer->fileSizes($template);
        $scope = $router?->id ?? 'default';

        // What each router shows right now, for the apply list.
        $effective = $routers->mapWithKeys(fn (Router $r) => [$r->id => in_array($r->id, $ownRouterIds, true) ? HotspotTemplate::ownFor($r) : ($default->exists ? $default : null)]);

        return view('client.hotspot-template.edit', compact(
            'template', 'routers', 'fileSizes', 'router', 'own', 'default', 'ownRouterIds', 'scope', 'effective',
        ));
    }

    /**
     * Free storage on each of the client's verified routers, fetched by
     * the settings page after it loads so a slow router doesn't hold it up.
     */
    public function storage(): JsonResponse
    {
        return response()->json($this->verifiedRouters()->map(function (Router $router) {
            $storage = $this->connector->storage($router);

            return [
                'id' => $router->id,
                'free' => $storage['free'] ?? null,
                'free_human' => $storage ? Number::fileSize($storage['free'], 1) : null,
                'total_human' => $storage ? Number::fileSize($storage['total'], 1) : null,
                'used_percent' => $storage && $storage['total'] ? round(100 - $storage['free'] / $storage['total'] * 100) : null,
            ];
        })->values());
    }

    public function update(Request $request): RedirectResponse
    {
        $client = Auth::guard('client')->user()->client;
        $router = $this->scopeRouter((string) $request->query('router', 'default'), $this->verifiedRouters());

        $data = $request->validate($this->rules() + [
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,webp', 'max:200'],
            'remove_logo' => ['nullable', 'boolean'],
            'background' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:500'],
            'remove_background' => ['nullable', 'boolean'],
        ]);

        // Saving on a router tab that still shows the default starts that
        // router's own design from a copy of the default.
        $template = $router
            ? (HotspotTemplate::ownFor($router) ?? HotspotTemplate::forClient($client)->copyFor($router))
            : HotspotTemplate::forClient($client);

        $this->replaceImage($request, $template, 'logo', 'logo_path', 'hotspot-logos');
        $this->replaceImage($request, $template, 'background', 'background_path', 'hotspot-backgrounds');

        $template->fill(collect($data)->except(['logo', 'remove_logo', 'background', 'remove_background'])->all());
        $template->save();

        return $this->backTo($router)->with('status', $router
            ? __('app.hotspot_template.saved_router', ['router' => $router->name])
            : __('app.hotspot_template.saved'));
    }

    /**
     * Give $router its own design, copied from the default or from another
     * router's design (?from=default|{router id}). Replaces a design the
     * router already had.
     */
    public function copy(Request $request, Router $router): RedirectResponse
    {
        $this->authorizeRouter($router);
        $routers = $this->verifiedRouters();
        $from = (string) $request->input('from', 'default');
        abort_if($from === (string) $router->id, Response::HTTP_UNPROCESSABLE_ENTITY);

        $source = $from === 'default'
            ? HotspotTemplate::forClient(Auth::guard('client')->user()->client)
            : HotspotTemplate::ownFor($this->scopeRouter($from, $routers) ?? abort(Response::HTTP_NOT_FOUND))
                ?? abort(Response::HTTP_NOT_FOUND);

        HotspotTemplate::ownFor($router)?->deleteWithFiles();
        $source->copyFor($router)->save();

        $sourceName = $from === 'default' ? __('app.hotspot_template.default_tab') : $routers->firstWhere('id', (int) $from)->name;

        return $this->backTo($router)->with('status', __('app.hotspot_template.copied', ['from' => $sourceName, 'router' => $router->name]));
    }

    /**
     * Drop the router's own design so it shows the default again. The
     * router keeps its current page until the template is applied again.
     */
    public function reset(Router $router): RedirectResponse
    {
        $this->authorizeRouter($router);

        HotspotTemplate::ownFor($router)?->deleteWithFiles();

        return $this->backTo($router)->with('status', __('app.hotspot_template.reset_done', ['router' => $router->name]));
    }

    /**
     * The template rendered with sample values, for the iframe on the
     * settings page. Valid query parameters override the saved values so
     * the preview follows the form before it is saved. The scope sits in
     * the path so the page's relative logo/background URLs keep it.
     */
    public function preview(Request $request, string $scope, string $file)
    {
        $client = Auth::guard('client')->user()->client;
        $router = $this->scopeRouter($scope, $this->verifiedRouters());
        $template = ($router ? HotspotTemplate::ownFor($router) : null) ?? HotspotTemplate::forClient($client);

        if ($path = $template->assetPath($file)) {
            return Storage::disk('local')->response($path);
        }

        abort_unless(in_array($file, HotspotTemplateRenderer::PREVIEW_PAGES, true), Response::HTTP_NOT_FOUND);

        foreach ($this->rules() as $field => $rules) {
            if ($request->has($field) && Validator::make($request->only($field), [$field => $rules])->passes()) {
                $template->{$field} = $request->input($field);
            }
        }

        return response($this->renderer->preview($template, $file))
            ->header('Content-Type', 'text/html; charset=utf-8');
    }

    public function apply(Router $router): RedirectResponse
    {
        $this->authorizeRouter($router);

        $template = HotspotTemplate::effectiveFor($router);

        if (! $template) {
            return back()->withErrors(['template' => __('app.hotspot_template.save_first')]);
        }

        $result = $this->connector->applyLoginTemplate(
            $router,
            $this->renderer->fileNames($template),
            url("/hs-template/{$template->token}"),
            array_sum($this->renderer->fileSizes($template)),
        );

        return back()->with($result['ok'] ? 'status' : 'connect_error', $result['message']);
    }

    public function restore(Router $router): RedirectResponse
    {
        $this->authorizeRouter($router);

        $result = $this->connector->restoreLoginTemplate($router);

        return back()->with($result['ok'] ? 'status' : 'connect_error', $result['message']);
    }

    /**
     * Store a newly uploaded image in place of the old one, or drop it
     * when its "remove_" box is ticked.
     */
    private function replaceImage(Request $request, HotspotTemplate $template, string $input, string $column, string $folder): void
    {
        if (! $request->hasFile($input) && ! $request->boolean("remove_{$input}")) {
            return;
        }

        if ($template->{$column}) {
            Storage::disk('local')->delete($template->{$column});
        }

        $template->{$column} = $request->hasFile($input)
            ? $request->file($input)->store($folder, 'local')
            : null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function rules(): array
    {
        return [
            'theme' => ['required', Rule::in(HotspotTemplate::THEMES)],
            'page_lang' => ['required', Rule::in(HotspotTemplate::PAGE_LANGS)],
            'title' => ['required', 'string', 'max:60'],
            'welcome_text' => ['nullable', 'string', 'max:255'],
            'primary_color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'login_mode' => ['required', Rule::in(HotspotTemplate::LOGIN_MODES)],
            'contact' => ['nullable', 'string', 'max:100'],
            'footer_text' => ['nullable', 'string', 'max:255'],
            'redirect_url' => ['nullable', 'url:http,https', 'max:255'],
        ];
    }

    /**
     * @return Collection<int, Router>
     */
    private function verifiedRouters(): Collection
    {
        return Router::where('client_id', Auth::guard('client')->user()->client_id)
            ->where('status', 'verified')
            ->orderBy('name')
            ->get();
    }

    /**
     * The router a scope names, or null for "default". A router that isn't
     * one of the client's verified routers is a 404, never the default.
     *
     * @param  Collection<int, Router>  $routers
     */
    private function scopeRouter(string $scope, Collection $routers): ?Router
    {
        if ($scope === 'default') {
            return null;
        }

        return $routers->firstWhere('id', (int) $scope) ?? abort(Response::HTTP_NOT_FOUND);
    }

    private function backTo(?Router $router): RedirectResponse
    {
        return redirect()->route('client.hotspot-template.edit', $router ? ['router' => $router->id] : []);
    }

    private function authorizeRouter(Router $router): void
    {
        abort_if($router->client_id !== Auth::guard('client')->user()->client_id, Response::HTTP_FORBIDDEN);
    }
}
