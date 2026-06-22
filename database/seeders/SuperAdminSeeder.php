<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Auth\UserRole;
use App\Enums\Auth\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => 'password',
                'status' => UserStatus::Active->value,
                'email_verified_at' => now(),
            ]
        );

        $user->assignRole(UserRole::SuperAdmin);
    }
}
