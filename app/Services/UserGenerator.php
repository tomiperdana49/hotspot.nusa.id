<?php

namespace App\Services;

use App\Models\HotspotUser;
use App\Models\Profile;
use App\Models\Radcheck;
use App\Models\Radusergroup;
use App\Models\UserBatch;
use Illuminate\Support\Str;

class UserGenerator
{
    /**
     * Create a single hotspot user and mirror it into radcheck/radusergroup.
     */
    public function generateOne(
        Profile $profile,
        ?string $username = null,
        ?string $password = null,
        ?int $batchId = null,
        ?string $note = null,
    ): HotspotUser {
        $username = $username ?: $this->uniqueUsername('', 8, 'alnum');
        $password = $password ?: $this->randomCode(8, 'alnum');

        $expiresAt = null;
        if ($profile->validity_value && $profile->validity_unit && $profile->validity_mode === 'from_create') {
            $expiresAt = now()->add($this->intervalUnit($profile->validity_unit), $profile->validity_value);
        }

        $user = HotspotUser::create([
            'client_id' => $profile->client_id,
            'profile_id' => $profile->id,
            'batch_id' => $batchId,
            'username' => $username,
            'password' => $password,
            'status' => 'active',
            'expires_at' => $expiresAt,
            'note' => $note,
        ]);

        Radcheck::create([
            'username' => $username,
            'attribute' => 'Cleartext-Password',
            'op' => ':=',
            'value' => $password,
        ]);

        Radusergroup::create([
            'username' => $username,
            'groupname' => $profile->group_name,
            'priority' => 1,
        ]);

        return $user;
    }

    /**
     * Generate a batch of users per the guide's user_batches settings.
     *
     * @return array<int, HotspotUser>
     */
    public function generateBatch(UserBatch $batch): array
    {
        $profile = $batch->profile;
        $sharedPassword = $batch->same_password ? $this->randomCode($batch->code_length, $batch->charset) : null;

        $users = [];
        for ($i = 0; $i < $batch->qty; $i++) {
            $username = $this->uniqueUsername($batch->prefix ?? '', $batch->code_length, $batch->charset);
            $password = $batch->password_as_username
                ? $username
                : $sharedPassword ?? $this->randomCode($batch->code_length, $batch->charset);

            $users[] = $this->generateOne($profile, $username, $password, $batch->id);
        }

        return $users;
    }

    private function uniqueUsername(string $prefix, int $length, string $charset): string
    {
        do {
            $username = $prefix.$this->randomCode($length, $charset);
        } while (HotspotUser::where('username', $username)->exists());

        return $username;
    }

    private function randomCode(int $length, string $charset): string
    {
        $pool = match ($charset) {
            'numeric' => '0123456789',
            'lower' => 'abcdefghijklmnopqrstuvwxyz',
            'upper' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            default => 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789',
        };

        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $pool[random_int(0, strlen($pool) - 1)];
        }

        return $code;
    }

    private function intervalUnit(string $unit): string
    {
        return match ($unit) {
            'hour' => 'hours',
            'day' => 'days',
            'month' => 'months',
            default => 'days',
        };
    }
}
