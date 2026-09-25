<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HotspotUser extends Model
{
    protected $table = 'hotspot_users';

    protected $fillable = [
        'client_id', 'profile_id', 'batch_id', 'username', 'password',
        'status', 'first_login_at', 'expires_at', 'used_bytes', 'used_seconds', 'note',
    ];

    protected function casts(): array
    {
        return [
            'first_login_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(UserBatch::class, 'batch_id');
    }
}
