<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserBatch extends Model
{
    protected $table = 'user_batches';

    protected $fillable = [
        'client_id', 'profile_id', 'name', 'qty', 'prefix', 'code_length',
        'charset', 'same_password', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'same_password' => 'boolean',
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

    public function hotspotUsers(): HasMany
    {
        return $this->hasMany(HotspotUser::class, 'batch_id');
    }
}
