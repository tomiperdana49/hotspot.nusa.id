<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Profile extends Model
{
    protected $table = 'profiles';

    protected $fillable = [
        'client_id', 'name', 'group_name', 'rate_down', 'rate_up', 'bandwidth_mode', 'burst_config',
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

    public function hotspotUsers(): HasMany
    {
        return $this->hasMany(HotspotUser::class);
    }
}
