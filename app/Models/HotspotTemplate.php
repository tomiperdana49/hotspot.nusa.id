<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HotspotTemplate extends Model
{
    public const THEMES = ['modern', 'minimal', 'dark'];

    public const LOGIN_MODES = ['userpass', 'voucher'];

    public const PAGE_LANGS = ['id', 'en'];

    protected $table = 'hotspot_templates';

    protected $fillable = [
        'client_id', 'router_id', 'token', 'theme', 'page_lang', 'title', 'welcome_text',
        'primary_color', 'login_mode', 'redirect_url', 'hold_popup', 'logo_path', 'background_path', 'contact', 'footer_text',
    ];

    protected function casts(): array
    {
        return ['hold_popup' => 'boolean'];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    /**
     * The client's default template (no router_id), or an unsaved one with
     * defaults so the settings page and preview work before the first save.
     */
    public static function forClient(Client $client): self
    {
        return self::firstOrNew(['client_id' => $client->id, 'router_id' => null], [
            'token' => Str::random(40),
            'theme' => 'modern',
            'page_lang' => 'id',
            'title' => $client->name,
            'welcome_text' => null,
            'primary_color' => '#059669',
            'login_mode' => 'userpass',
        ]);
    }

    /**
     * The router's own template, or null when it uses the default.
     */
    public static function ownFor(Router $router): ?self
    {
        return self::where('client_id', $router->client_id)->where('router_id', $router->id)->first();
    }

    /**
     * The template a router shows: its own, else the client's saved
     * default; null when neither has been saved yet.
     */
    public static function effectiveFor(Router $router): ?self
    {
        return self::ownFor($router)
            ?? self::where('client_id', $router->client_id)->whereNull('router_id')->first();
    }

    /**
     * An unsaved copy of this template for $router, with its own token and
     * its own copies of the images, so editing or removing them later
     * never touches the template it came from.
     */
    public function copyFor(Router $router): self
    {
        $copy = $this->replicate(['router_id', 'token', 'logo_path', 'background_path']);
        $copy->router_id = $router->id;
        $copy->token = Str::random(40);

        foreach (['logo_path' => 'hotspot-logos', 'background_path' => 'hotspot-backgrounds'] as $column => $folder) {
            if ($this->{$column} && Storage::disk('local')->exists($this->{$column})) {
                $path = $folder.'/'.Str::random(40).'.'.pathinfo($this->{$column}, PATHINFO_EXTENSION);
                Storage::disk('local')->copy($this->{$column}, $path);
                $copy->{$column} = $path;
            }
        }

        return $copy;
    }

    /**
     * Delete the template together with its uploaded images.
     */
    public function deleteWithFiles(): void
    {
        foreach (['logo_path', 'background_path'] as $column) {
            if ($this->{$column}) {
                Storage::disk('local')->delete($this->{$column});
            }
        }

        $this->delete();
    }

    /**
     * File name the logo gets next to login.html on the router.
     */
    public function logoFileName(): ?string
    {
        return $this->logo_path ? 'logo.'.pathinfo($this->logo_path, PATHINFO_EXTENSION) : null;
    }

    /**
     * File name the background image gets next to login.html on the router.
     */
    public function backgroundFileName(): ?string
    {
        return $this->background_path ? 'bg.'.pathinfo($this->background_path, PATHINFO_EXTENSION) : null;
    }

    /**
     * Storage path of an uploaded image by its file name on the router
     * (logo.png, bg.jpg), or null when $file isn't one of them.
     */
    public function assetPath(string $file): ?string
    {
        return match ($file) {
            $this->logoFileName() => $this->logo_path,
            $this->backgroundFileName() => $this->background_path,
            default => null,
        };
    }
}
