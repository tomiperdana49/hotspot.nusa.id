<?php

namespace App\Services;

use App\Models\HotspotTemplate;
use Illuminate\Support\Facades\Storage;

/**
 * Builds the MikroTik hotspot HTML directory (login.html, status.html,
 * …) from a client's HotspotTemplate. The output still holds MikroTik's
 * own $(var) / $(if …) placeholders — the router fills those in when it
 * serves each page. preview() fills them with sample values instead, so
 * the client panel can show the page without a router.
 */
class HotspotTemplateRenderer
{
    /**
     * Pages the router serves from html-directory, plus md5.js which
     * login.html needs for http-chap.
     */
    public const PAGES = ['login', 'alogin', 'status', 'logout', 'error', 'redirect', 'rlogin'];

    public const PREVIEW_PAGES = ['login', 'alogin', 'status', 'logout'];

    /**
     * Every file a router needs for this template, by name.
     *
     * @return array<int, string>
     */
    public function fileNames(HotspotTemplate $template): array
    {
        $files = array_map(fn (string $page) => "{$page}.html", self::PAGES);
        $files[] = 'md5.js';

        foreach ([$template->logoFileName(), $template->backgroundFileName()] as $image) {
            if ($image) {
                $files[] = $image;
            }
        }

        return $files;
    }

    /**
     * Size in bytes of each file fileNames() lists, as the router will store it.
     *
     * @return array<string, int>
     */
    public function fileSizes(HotspotTemplate $template): array
    {
        $sizes = [];

        foreach ($this->fileNames($template) as $file) {
            $sizes[$file] = match (true) {
                $file === 'md5.js' => strlen($this->md5Script()),
                $template->assetPath($file) !== null => Storage::disk('local')->size($template->assetPath($file)),
                default => strlen($this->render($template, basename($file, '.html'))),
            };
        }

        return $sizes;
    }

    public function render(HotspotTemplate $template, string $page): string
    {
        return view("hotspot-pages.{$page}", [
            't' => $template,
            'lang' => $template->page_lang,
            'brand' => $template->primary_color,
            'brandDark' => $this->shade($template->primary_color, 0.72),
            'text' => fn (?string $value) => $this->text($value),
        ])->render();
    }

    public function md5Script(): string
    {
        return file_get_contents(resource_path('hotspot/md5.js'));
    }

    public function preview(HotspotTemplate $template, string $page): string
    {
        $vars = [
            'link-login-only' => '#', 'link-login' => '#', 'link-logout' => '#',
            'link-orig' => '#', 'link-redirect' => '#', 'link-status' => '#',
            'chap-id' => '', 'chap-challenge' => '', 'error' => '', 'popup' => 'true',
            'username' => 'demo123', 'ip' => '10.5.50.23', 'mac' => 'AA:BB:CC:DD:EE:FF',
            'uptime' => '1h12m30s', 'session-time-left' => '22h47m30s',
            'bytes-in-nice' => '84.3 MiB', 'bytes-out-nice' => '1.2 GiB',
            'refresh-timeout' => '', 'login-by' => 'http-chap', 'identity' => 'MikroTik',
        ];

        // A meta refresh would navigate the preview iframe away.
        $html = preg_replace('/<meta http-equiv="refresh"[^>]*>/i', '', $this->render($template, $page));

        return $this->fill($html, $vars);
    }

    /**
     * Escape client-entered text for the page, and also turn "$" into
     * an entity so the router never reads it as a $(…) placeholder.
     */
    private function text(?string $value): string
    {
        return str_replace('$', '&#36;', e((string) $value));
    }

    /**
     * Minimal stand-in for the router's template engine: $(var),
     * $(if var), $(if var == 'x'), $(if var != 'x'), $(elif …),
     * $(else) and $(endif), nested.
     *
     * @param  array<string, string>  $vars
     */
    private function fill(string $html, array $vars): string
    {
        $parts = preg_split('/\$\(([^)]*)\)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
        $out = '';
        // Each frame: [active, branchTaken]. Root is always active.
        $stack = [[true, true]];

        foreach ($parts as $i => $part) {
            [$active] = end($stack);

            if ($i % 2 === 0) {
                if ($active) {
                    $out .= $part;
                }

                continue;
            }

            $part = trim($part);
            $parentActive = count($stack) > 1 ? $stack[count($stack) - 2][0] : true;

            if (str_starts_with($part, 'if ')) {
                $cond = $this->condition(substr($part, 3), $vars);
                $stack[] = [$active && $cond, $cond];
            } elseif (str_starts_with($part, 'elif ') && count($stack) > 1) {
                [, $taken] = array_pop($stack);
                $cond = ! $taken && $this->condition(substr($part, 5), $vars);
                $stack[] = [$parentActive && $cond, $taken || $cond];
            } elseif ($part === 'else' && count($stack) > 1) {
                [, $taken] = array_pop($stack);
                $stack[] = [$parentActive && ! $taken, true];
            } elseif ($part === 'endif' && count($stack) > 1) {
                array_pop($stack);
            } elseif ($active) {
                $out .= e($vars[$part] ?? '');
            }
        }

        return $out;
    }

    /**
     * @param  array<string, string>  $vars
     */
    private function condition(string $expr, array $vars): bool
    {
        if (preg_match('/^\s*([\w-]+)\s*(==|!=)\s*[\'"]?([^\'"]*)[\'"]?\s*$/', $expr, $m)) {
            $equal = (string) ($vars[$m[1]] ?? '') === $m[3];

            return $m[2] === '==' ? $equal : ! $equal;
        }

        return ($vars[trim($expr)] ?? '') !== '';
    }

    /**
     * Darken a #rrggbb color by multiplying each channel with $factor.
     */
    private function shade(string $hex, float $factor): string
    {
        $rgb = array_map(fn ($c) => (int) round(hexdec($c) * $factor), str_split(ltrim($hex, '#'), 2));

        return vsprintf('#%02x%02x%02x', $rgb);
    }
}
