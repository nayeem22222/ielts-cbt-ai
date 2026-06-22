<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Auth\UserRole;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'student',
                'slug' => UserRole::Student->value,
                'label' => UserRole::Student->label(),
                'description' => 'Student account for IELTS practice and courses.',
            ],
            [
                'name' => 'teacher',
                'slug' => UserRole::Teacher->value,
                'label' => UserRole::Teacher->label(),
                'description' => 'Teacher account for classes, evaluations, and analytics.',
            ],
            [
                'name' => 'admin',
                'slug' => UserRole::Admin->value,
                'label' => UserRole::Admin->label(),
                'description' => 'Administrator account for platform operations.',
            ],
            [
                'name' => 'super_admin',
                'slug' => UserRole::SuperAdmin->value,
                'label' => UserRole::SuperAdmin->label(),
                'description' => 'Super administrator with full system access.',
            ],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(
                ['slug' => $role['slug']],
                [
                    'name' => $role['name'],
                    'label' => $role['label'],
                    'description' => $role['description'],
                    'guard_name' => 'web',
                ]
            );
        }
    }
}
