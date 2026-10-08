<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class HotspotTemplate extends Model
{
    public const THEMES = ['modern', 'minimal', 'dark'];

    public const LOGIN_MODES = ['userpass', 'voucher'];

    public const PAGE_LANGS = ['id', 'en'];

    protected $table = 'hotspot_templates';

    protected $fillable = [
        'client_id', 'token', 'theme', 'page_lang', 'title', 'welcome_text',
        'primary_color', 'login_mode', 'redirect_url', 'logo_path', 'background_path', 'contact', 'footer_text',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * The client's saved template, or an unsaved one with defaults so
     * the settings page and preview work before the first save.
     */
    public static function forClient(Client $client): self
    {
        return self::firstOrNew(['client_id' => $client->id], [
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
