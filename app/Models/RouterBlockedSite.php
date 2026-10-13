<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RouterBlockedSite extends Model
{
    protected $table = 'router_blocked_sites';

    protected $fillable = ['router_id', 'domain'];

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class);
    }

    /**
     * Bare host name from whatever the client typed ("https://www.TikTok.com/foo",
     * "*.tiktok.com", "tiktok.com:443"), in ASCII form; null when it isn't a
     * domain. A leading "www." is dropped since subdomains are blocked anyway.
     */
    public static function normalizeDomain(string $input): ?string
    {
        $host = strtolower(trim($input));
        $host = preg_replace('#^[a-z][a-z0-9+.-]*://#', '', $host);
        $host = preg_replace('#[/?\#].*$#', '', $host);
        $host = preg_replace('#:\d+$#', '', $host);
        $host = preg_replace('#^(\*\.|www\.)#', '', $host);
        $host = rtrim($host, '.');

        if ($host === '') {
            return null;
        }

        if (preg_match('/[^\x20-\x7e]/', $host)) {
            $host = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46) ?: '';
        }

        $valid = strlen($host) <= 253
            && preg_match('/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9-]{2,63}$/', $host)
            && ! filter_var($host, FILTER_VALIDATE_IP);

        return $valid ? $host : null;
    }
}
