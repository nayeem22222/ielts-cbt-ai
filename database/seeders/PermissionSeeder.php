<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Auth\Permission as PermissionEnum;
use App\Enums\Auth\UserRole;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PermissionEnum::cases() as $permission) {
            Permission::query()->updateOrCreate(
                ['name' => $permission->value, 'guard_name' => 'web'],
                [
                    'group' => $permission->group(),
                    'description' => $permission->label(),
                ]
            );
        }

        foreach (UserRole::cases() as $role) {
            $roleModel = Role::findBySlug($role);

            if ($roleModel === null) {
                continue;
            }

            $roleModel->syncPermissions(PermissionEnum::forRole($role));
        }
    }
}
