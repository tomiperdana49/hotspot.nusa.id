<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Router extends Model
{
    /**
     * Heartbeat interval is 5 minutes (see routers:heartbeat schedule);
     * allow one missed cycle before flagging the router offline.
     */
    private const ONLINE_THRESHOLD_MINUTES = 10;

    protected $table = 'routers';

    protected $fillable = [
        'client_id', 'name', 'nas_ip', 'nas_identifier', 'radius_secret',
        'pairing_code', 'status', 'board_serial', 'api_host', 'api_port',
        'api_user', 'api_pass', 'vpn_ip', 'vpn_pubkey', 'last_seen_at', 'verified_at',
    ];

    protected $hidden = ['radius_secret', 'api_pass'];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'verified_at' => 'datetime',
            'api_pass' => 'encrypted',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    protected function isOnline(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->status === 'verified'
                && $this->last_seen_at !== null
                && $this->last_seen_at->gt(now()->subMinutes(self::ONLINE_THRESHOLD_MINUTES)),
        );
    }
}
