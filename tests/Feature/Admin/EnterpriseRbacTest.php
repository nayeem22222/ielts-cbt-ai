<?php

declare(strict_types=1);

use App\Enums\Auth\Permission;
use App\Enums\Auth\UserRole;
use App\Models\Permission as PermissionModel;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    seedRbac();
});

it('creates rbac tables with expected columns', function (): void {
    expect(Schema::hasTable('roles'))->toBeTrue()
        ->and(Schema::hasTable('permissions'))->toBeTrue()
        ->and(Schema::hasTable('role_user'))->toBeTrue()
        ->and(Schema::hasTable('permission_role'))->toBeTrue()
        ->and(Schema::hasTable('permission_user'))->toBeTrue();

    expect(Schema::hasColumn('permissions', 'group'))->toBeTrue()
        ->and(Schema::hasColumn('permission_user', 'permission_id'))->toBeTrue();
});

it('seeds default roles and permissions', function (): void {
    expect(Role::query()->count())->toBe(4)
        ->and(PermissionModel::query()->count())->toBe(count(Permission::cases()));

    $adminRole = Role::findBySlug(UserRole::Admin);

    expect($adminRole)->not->toBeNull()
        ->and($adminRole->hasPermission(Permission::UsersView))->toBeTrue()
        ->and($adminRole->hasPermission(Permission::RolesManagePermissions))->toBeFalse();
});

it('assigns roles to users', function (): void {
    $student = createUserWithRole(UserRole::Student, ['email' => 'student-rbac@example.com']);

    expect($student->hasRole(UserRole::Student))->toBeTrue()
        ->and($student->hasPermission(Permission::AccessStudentDashboard))->toBeTrue()
        ->and($student->hasPermission(Permission::UsersView))->toBeFalse();
});

it('assigns permissions to roles', function (): void {
    $teacherRole = Role::findBySlug(UserRole::Teacher);

    $teacherRole->givePermissionTo([Permission::UsersView]);

    expect($teacherRole->fresh()->hasPermission(Permission::UsersView))->toBeTrue();
});

it('assigns direct permissions to users', function (): void {
    $teacher = createUserWithRole(UserRole::Teacher, ['email' => 'teacher-rbac@example.com']);

    $teacher->givePermissionTo([Permission::UsersView]);

    expect($teacher->fresh()->hasPermission(Permission::UsersView))->toBeTrue()
        ->and($teacher->hasPermission(Permission::AccessTeacherDashboard))->toBeTrue();
});

it('blocks users without permission via middleware', function (): void {
    $teacher = createUserWithRole(UserRole::Teacher, [
        'email' => 'teacher-blocked@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($teacher)
        ->get(route('admin.roles.index'))
        ->assertForbidden();
});

it('allows admins with permission via middleware', function (): void {
    $admin = createUserWithRole(UserRole::Admin, [
        'email' => 'admin-rbac@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.roles.index'))
        ->assertOk()
        ->assertSee('Roles');
});

it('syncs role permissions from admin panel', function (): void {
    $superAdmin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'super-rbac@example.com',
        'email_verified_at' => now(),
    ]);

    $teacherRole = Role::findBySlug(UserRole::Teacher);

    $this->actingAs($superAdmin)
        ->put(route('admin.roles.permissions.sync', $teacherRole), [
            'permissions' => [
                Permission::AccessTeacherDashboard->value,
                Permission::UsersView->value,
            ],
        ])
        ->assertRedirect(route('admin.roles.edit', $teacherRole));

    expect($teacherRole->fresh()->hasPermission(Permission::UsersView))->toBeTrue()
        ->and($teacherRole->hasPermission(Permission::UsersCreate))->toBeFalse();
});

it('syncs direct user permissions from admin panel', function (): void {
    $superAdmin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'super-perms@example.com',
        'email_verified_at' => now(),
    ]);

    $teacher = createUserWithRole(UserRole::Teacher, ['email' => 'teacher-sync@example.com']);

    $this->actingAs($superAdmin)
        ->put(route('admin.users.permissions.sync', $teacher), [
            'permissions' => [Permission::UsersView->value],
        ])
        ->assertRedirect(route('admin.users.edit', $teacher));

    expect($teacher->fresh()->hasPermission(Permission::UsersView))->toBeTrue();
});

it('prevents non super admin from editing super admin role permissions', function (): void {
    $admin = createUserWithRole(UserRole::Admin, [
        'email' => 'admin-blocked-role@example.com',
        'email_verified_at' => now(),
    ]);

    $superAdminRole = Role::findBySlug(UserRole::SuperAdmin);

    $this->actingAs($admin)
        ->get(route('admin.roles.edit', $superAdminRole))
        ->assertForbidden();
});

it('grants super admin all permissions', function (): void {
    $superAdmin = createUserWithRole(UserRole::SuperAdmin, ['email' => 'all-perms@example.com']);

    foreach (Permission::cases() as $permission) {
        expect($superAdmin->hasPermission($permission))->toBeTrue();
    }
});

it('enforces user policy permissions for admin users', function (): void {
    $adminRole = Role::findBySlug(UserRole::Admin);
    $adminRole->syncPermissions([
        Permission::AccessAdminDashboard,
        Permission::UsersView,
    ]);

    $admin = createUserWithRole(UserRole::Admin, [
        'email' => 'admin-policy@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Blocked Create',
            'email' => 'blocked-create@example.com',
            'role' => UserRole::Student->value,
            'status' => 'active',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertForbidden();
});
