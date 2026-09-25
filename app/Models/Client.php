<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $table = 'clients';

    protected $fillable = [
        'code', 'name', 'email', 'phone', 'address', 'status',
        'max_routers', 'max_users', 'expired_at',
    ];

    protected function casts(): array
    {
        return [
            'expired_at' => 'datetime',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(ClientUser::class);
    }

    public function routers(): HasMany
    {
        return $this->hasMany(Router::class);
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(Profile::class);
    }

    public function hotspotUsers(): HasMany
    {
        return $this->hasMany(HotspotUser::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active'
            && ($this->expired_at === null || $this->expired_at->isFuture());
    }
}
