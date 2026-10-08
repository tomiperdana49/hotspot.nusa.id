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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class HotspotTemplateController extends Controller
{
    public function __construct(
        private readonly HotspotTemplateRenderer $renderer,
        private readonly MikrotikConnector $connector,
    ) {}

    public function edit()
    {
        $client = Auth::guard('client')->user()->client;
        $template = HotspotTemplate::forClient($client);
        $routers = Router::where('client_id', $client->id)->where('status', 'verified')->orderBy('name')->get();

        $fileSizes = $this->renderer->fileSizes($template);

        return view('client.hotspot-template.edit', compact('template', 'routers', 'fileSizes'));
    }

    /**
     * Free storage on each of the client's verified routers, fetched by
     * the settings page after it loads so a slow router doesn't hold it up.
     */
    public function storage(): JsonResponse
    {
        $clientId = Auth::guard('client')->user()->client_id;
        $routers = Router::where('client_id', $clientId)->where('status', 'verified')->get();

        return response()->json($routers->map(function (Router $router) {
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
        $template = HotspotTemplate::forClient($client);

        $data = $request->validate($this->rules() + [
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg,gif,webp', 'max:200'],
            'remove_logo' => ['nullable', 'boolean'],
            'background' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:500'],
            'remove_background' => ['nullable', 'boolean'],
        ]);

        $this->replaceImage($request, $template, 'logo', 'logo_path', 'hotspot-logos');
        $this->replaceImage($request, $template, 'background', 'background_path', 'hotspot-backgrounds');

        $template->fill(collect($data)->except(['logo', 'remove_logo', 'background', 'remove_background'])->all());
        $template->save();

        return redirect()->route('client.hotspot-template.edit')->with('status', __('app.hotspot_template.saved'));
    }

    /**
     * The template rendered with sample values, for the iframe on the
     * settings page. Valid query parameters override the saved values so
     * the preview follows the form before it is saved.
     */
    public function preview(Request $request, string $file)
    {
        $template = HotspotTemplate::forClient(Auth::guard('client')->user()->client);

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

        $template = HotspotTemplate::where('client_id', $router->client_id)->first();

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

    private function authorizeRouter(Router $router): void
    {
        abort_if($router->client_id !== Auth::guard('client')->user()->client_id, Response::HTTP_FORBIDDEN);
    }
}
