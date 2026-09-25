<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceName extends Model
{
    protected $table = 'device_names';

    protected $fillable = ['router_id', 'mac_address', 'name', 'last_seen_at'];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * RADIUS reports Calling-Station-Id as "76-F7-C0-5D-D1-F2" while the
     * MikroTik API returns "76:F7:C0:5D:D1:F2" — normalise both to the
     * colon form so they can be matched.
     */
    public static function normalizeMac(?string $mac): string
    {
        return strtoupper(str_replace('-', ':', trim((string) $mac)));
    }
}
