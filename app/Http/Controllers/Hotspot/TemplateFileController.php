<?php

namespace App\Http\Controllers\Hotspot;

use App\Http\Controllers\Controller;
use App\Models\HotspotTemplate;
use App\Services\HotspotTemplateRenderer;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves a client's hotspot template files to their MikroTik, which
 * downloads them with /tool fetch (see MikrotikConnector::applyLoginTemplate).
 * Public on purpose — the router has no session; the 40-char token in
 * the URL is what picks the client.
 */
class TemplateFileController extends Controller
{
    public function __invoke(HotspotTemplateRenderer $renderer, string $token, string $file)
    {
        $template = HotspotTemplate::where('token', $token)->firstOrFail();

        if ($file === 'md5.js') {
            return response($renderer->md5Script())->header('Content-Type', 'application/javascript');
        }

        if ($path = $template->assetPath($file)) {
            return Storage::disk('local')->response($path);
        }

        $page = basename($file, '.html');
        abort_unless(
            str_ends_with($file, '.html') && in_array($page, HotspotTemplateRenderer::PAGES, true),
            Response::HTTP_NOT_FOUND,
        );

        return response($renderer->render($template, $page))->header('Content-Type', 'text/html; charset=utf-8');
    }
}
