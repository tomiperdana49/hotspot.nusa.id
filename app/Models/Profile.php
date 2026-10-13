<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profile extends Model
{
    protected $table = 'profiles';

    protected $fillable = [
        'client_id', 'name', 'group_name', 'rate_down', 'rate_up', 'bandwidth_mode', 'connection_mode', 'router_scope', 'burst_config',
        'session_timeout', 'idle_timeout', 'validity_value',
        'validity_unit', 'validity_mode', 'simultaneous_use', 'mikrotik_group',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * The routers this profile's vouchers may log in on when router_scope
     * is "selected"; ignored when it is "all".
     */
    public function routers(): BelongsToMany
    {
        return $this->belongsToMany(Router::class, 'profile_router');
    }

    /**
     * Where this profile's vouchers can log in, for labels: "All routers",
     * the ticked router names, or "No router" once those were all deleted.
     */
    protected function routerLabel(): Attribute
    {
        return Attribute::make(get: fn () => match (true) {
            $this->router_scope === 'all' => __('app.router_filter.all'),
            $this->routers->isEmpty() => __('app.profile_index.no_routers'),
            default => $this->routers->pluck('name')->join(', '),
        });
    }

    public function hotspotUsers(): HasMany
    {
        return $this->hasMany(HotspotUser::class);
    }
}
