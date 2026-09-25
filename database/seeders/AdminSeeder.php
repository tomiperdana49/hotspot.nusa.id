<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('NUSA_INITIAL_ADMIN_EMAIL');
        $password = env('NUSA_INITIAL_ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command->error('Set NUSA_INITIAL_ADMIN_EMAIL and NUSA_INITIAL_ADMIN_PASSWORD before running this seeder.');

            return;
        }

        Admin::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Superadmin',
                'password' => Hash::make($password),
                'is_active' => true,
            ],
        );

        $this->command->info("Admin account ready: {$email}");
    }
}
