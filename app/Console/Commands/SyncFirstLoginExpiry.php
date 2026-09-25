<?php

namespace App\Console\Commands;

use App\Models\HotspotUser;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SyncFirstLoginExpiry extends Command
{
    protected $signature = 'users:sync-first-login';

    protected $description = 'Detect each user\'s first radacct login, mark them "used", and compute expires_at for profiles using validity_mode=from_first_login';

    public function handle(): int
    {
        $users = HotspotUser::with('profile')->whereNull('first_login_at')->get();

        $updated = 0;

        foreach ($users as $user) {
            $firstLogin = DB::table('radacct')
                ->where('username', $user->username)
                ->min('acctstarttime');

            if (! $firstLogin) {
                continue;
            }

            $firstLoginAt = Carbon::parse($firstLogin);
            $profile = $user->profile;

            $data = ['first_login_at' => $firstLoginAt];

            if ($user->status === 'active') {
                $data['status'] = 'used';
            }

            if ($profile && $profile->validity_mode === 'from_first_login' && $profile->validity_value && $profile->validity_unit) {
                $data['expires_at'] = $firstLoginAt->copy()->add($this->intervalUnit($profile->validity_unit), $profile->validity_value);
            }

            $user->update($data);

            $updated++;
        }

        $this->info("Sync first-login: {$updated}/{$users->count()} user diperbarui.");

        return self::SUCCESS;
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
