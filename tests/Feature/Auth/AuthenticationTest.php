<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Auth\UserStatus;
use App\Models\User;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    seedRbac();
});

it('registers a student and redirects to email verification', function (): void {
    $response = $this->post(route('register.store'), [
        'name' => 'Student One',
        'email' => 'student@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('verification.notice'));
    $this->assertAuthenticated();

    $user = User::query()->where('email', 'student@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasRole(UserRole::Student))->toBeTrue()
        ->and($user->studentProfile)->not->toBeNull();
});

it('logs in users by role and redirects correctly', function (UserRole $role, string $routeName): void {
    $user = createUserWithRole($role, ['email' => "{$role->value}@example.com"]);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route($routeName));
    $this->assertAuthenticatedAs($user);
})->with([
    'student' => [UserRole::Student, 'student.dashboard'],
    'teacher' => [UserRole::Teacher, 'teacher.dashboard'],
    'admin' => [UserRole::Admin, 'admin.dashboard'],
    'super admin' => [UserRole::SuperAdmin, 'admin.dashboard'],
]);

it('rejects invalid login credentials', function (): void {
    createUserWithRole(UserRole::Student, ['email' => 'student@example.com']);

    $response = $this->from(route('login'))->post(route('login.store'), [
        'email' => 'student@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('blocks inactive users from logging in', function (): void {
    $user = createUserWithRole(UserRole::Student, [
        'email' => 'inactive@example.com',
        'status' => UserStatus::Inactive->value,
    ]);

    $response = $this->from(route('login'))->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('login'));
    $response->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('logs out authenticated users', function (): void {
    $user = createUserWithRole(UserRole::Student);

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});

it('prevents guests from accessing protected dashboards', function (): void {
    $this->get(route('student.dashboard'))->assertRedirect(route('login'));
    $this->get(route('teacher.dashboard'))->assertRedirect(route('login'));
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

it('prevents students from accessing admin routes', function (): void {
    $student = createUserWithRole(UserRole::Student);

    $this->actingAs($student)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

it('redirects authenticated users away from login and register pages', function (): void {
    $student = createUserWithRole(UserRole::Student);

    $this->actingAs($student)
        ->get(route('login'))
        ->assertRedirect(route('student.dashboard'));

    $this->actingAs($student)
        ->get(route('register'))
        ->assertRedirect(route('student.dashboard'));
});
