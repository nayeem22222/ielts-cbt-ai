<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
});

it('allows super admin to manage users', function (): void {
    $superAdmin = createUserWithRole(UserRole::SuperAdmin, ['email' => 'admin@example.com']);

    $this->actingAs($superAdmin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Users');

    $this->actingAs($superAdmin)
        ->post(route('admin.users.store'), [
            'name' => 'Teacher One',
            'email' => 'teacher@example.com',
            'phone' => '01700000000',
            'role' => UserRole::Teacher->value,
            'status' => 'active',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertRedirect(route('admin.users.index'));

    $teacher = User::query()->where('email', 'teacher@example.com')->first();

    expect($teacher)->not->toBeNull();
    expect($teacher->hasRole(UserRole::Teacher))->toBeTrue();
});

it('prevents admin from assigning super admin role', function (): void {
    $admin = createUserWithRole(UserRole::Admin, ['email' => 'ops@example.com']);

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Blocked Super Admin',
            'email' => 'blocked@example.com',
            'role' => UserRole::SuperAdmin->value,
            'status' => 'active',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertSessionHasErrors('role');
});

it('prevents admin from deleting own account', function (): void {
    $admin = createUserWithRole(UserRole::Admin, ['email' => 'ops@example.com']);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertForbidden();
});

it('prevents admin from deleting super admin accounts', function (): void {
    $admin = createUserWithRole(UserRole::Admin, ['email' => 'ops@example.com']);
    $superAdmin = createUserWithRole(UserRole::SuperAdmin, ['email' => 'root@example.com']);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $superAdmin))
        ->assertForbidden();
});

it('allows super admin to delete other users', function (): void {
    $superAdmin = createUserWithRole(UserRole::SuperAdmin, ['email' => 'admin@example.com']);
    $teacher = createUserWithRole(UserRole::Teacher, ['email' => 'teacher@example.com']);

    $this->actingAs($superAdmin)
        ->delete(route('admin.users.destroy', $teacher))
        ->assertRedirect(route('admin.users.index'));

    expect(User::withTrashed()->find($teacher->id)?->trashed())->toBeTrue();
});

it('searches users in admin directory', function (): void {
    $superAdmin = createUserWithRole(UserRole::SuperAdmin, ['email' => 'admin@example.com']);
    createUserWithRole(UserRole::Student, ['name' => 'Searchable Student', 'email' => 'findme@example.com']);
    createUserWithRole(UserRole::Teacher, ['name' => 'Hidden Teacher', 'email' => 'hidden@example.com']);

    $this->actingAs($superAdmin)
        ->get(route('admin.users.index', ['search' => 'findme']))
        ->assertOk()
        ->assertSee('Searchable Student')
        ->assertDontSee('Hidden Teacher');
});
