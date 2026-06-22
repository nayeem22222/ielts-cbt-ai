<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function (): void {
    seedRbac();
});

describe('auth flow', function (): void {
    it('1. registers a student account', function (): void {
        $response = $this->post(route('register.store'), [
            'name' => 'New Student',
            'email' => 'newstudent@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('verification.notice'));
        $this->assertAuthenticated();

        $user = User::query()->where('email', 'newstudent@example.com')->first();
        expect($user)->not->toBeNull()
            ->and($user->hasRole(UserRole::Student))->toBeTrue();
    });

    it('2. logs in a student and redirects to student dashboard', function (): void {
        $student = createUserWithRole(UserRole::Student, ['email' => 'student-login@example.com']);

        $this->post(route('login.store'), [
            'email' => $student->email,
            'password' => 'password',
        ])->assertRedirect(route('student.dashboard'));

        $this->assertAuthenticatedAs($student);
    });

    it('3. logs in an admin and redirects to admin dashboard', function (): void {
        $admin = createUserWithRole(UserRole::Admin, ['email' => 'admin-login@example.com']);

        $this->post(route('login.store'), [
            'email' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    });

    it('4. redirects each role to the correct dashboard after login', function (UserRole $role, string $routeName): void {
        $user = createUserWithRole($role, ['email' => "{$role->value}-redirect@example.com"]);

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route($routeName));
    })->with([
        'student' => [UserRole::Student, 'student.dashboard'],
        'teacher' => [UserRole::Teacher, 'teacher.dashboard'],
        'admin' => [UserRole::Admin, 'admin.dashboard'],
        'super_admin' => [UserRole::SuperAdmin, 'admin.dashboard'],
    ]);

    it('5. prevents students from accessing admin dashboard', function (): void {
        $student = createUserWithRole(UserRole::Student);

        $this->actingAs($student)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($student)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    });

    it('6. prevents teachers from accessing admin dashboard', function (): void {
        $teacher = createUserWithRole(UserRole::Teacher);

        $this->actingAs($teacher)
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs($teacher)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    });
});

describe('admin user management', function (): void {
    beforeEach(function (): void {
        $this->admin = createUserWithRole(UserRole::Admin, ['email' => 'ops-admin@example.com']);
    });

    it('7. allows admin to create, edit, update, and delete a student', function (): void {
        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Managed Student',
                'email' => 'managed-student@example.com',
                'role' => UserRole::Student->value,
                'status' => 'active',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin.users.index'));

        $student = User::query()->where('email', 'managed-student@example.com')->first();
        expect($student)->not->toBeNull();

        $this->actingAs($this->admin)
            ->get(route('admin.users.edit', $student))
            ->assertOk();

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $student), [
                'name' => 'Updated Student',
                'email' => 'managed-student@example.com',
                'role' => UserRole::Student->value,
                'status' => 'active',
                'password' => 'newpassword123',
                'password_confirmation' => 'newpassword123',
            ])
            ->assertRedirect(route('admin.users.index'));

        $student->refresh();
        expect($student->name)->toBe('Updated Student')
            ->and(Hash::check('newpassword123', $student->password))->toBeTrue();

        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $student))
            ->assertRedirect(route('admin.users.index'));

        expect(User::withTrashed()->find($student->id)?->trashed())->toBeTrue();
    });

    it('8. allows admin to create, edit, update, and delete a teacher', function (): void {
        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Managed Teacher',
                'email' => 'managed-teacher@example.com',
                'role' => UserRole::Teacher->value,
                'status' => 'active',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin.users.index'));

        $teacher = User::query()->where('email', 'managed-teacher@example.com')->first();

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $teacher), [
                'name' => 'Updated Teacher',
                'email' => 'managed-teacher@example.com',
                'role' => UserRole::Teacher->value,
                'status' => 'inactive',
            ])
            ->assertRedirect(route('admin.users.index'));

        expect($teacher->fresh()->name)->toBe('Updated Teacher')
            ->and($teacher->fresh()->status)->toBe('inactive');

        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $teacher))
            ->assertRedirect(route('admin.users.index'));

        expect(User::withTrashed()->find($teacher->id)?->trashed())->toBeTrue();
    });

    it('9. allows admin to create, edit, update, and delete another admin', function (): void {
        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Other Admin',
                'email' => 'other-admin@example.com',
                'role' => UserRole::Admin->value,
                'status' => 'active',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertRedirect(route('admin.users.index'));

        $otherAdmin = User::query()->where('email', 'other-admin@example.com')->first();

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $otherAdmin), [
                'name' => 'Renamed Admin',
                'email' => 'other-admin@example.com',
                'role' => UserRole::Admin->value,
                'status' => 'active',
            ])
            ->assertRedirect(route('admin.users.index'));

        expect($otherAdmin->fresh()->name)->toBe('Renamed Admin');

        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $otherAdmin))
            ->assertRedirect(route('admin.users.index'));

        expect(User::withTrashed()->find($otherAdmin->id)?->trashed())->toBeTrue();
    });

    it('10. prevents admin from deleting own account', function (): void {
        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $this->admin))
            ->assertForbidden();

        expect(User::query()->find($this->admin->id))->not->toBeNull();
    });

    it('11. prevents normal admin from managing super_admin accounts', function (): void {
        $superAdmin = createUserWithRole(UserRole::SuperAdmin, ['email' => 'root@example.com']);

        $this->actingAs($this->admin)
            ->post(route('admin.users.store'), [
                'name' => 'Illegal Super Admin',
                'email' => 'illegal-super@example.com',
                'role' => UserRole::SuperAdmin->value,
                'status' => 'active',
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ])
            ->assertSessionHasErrors('role');

        $this->actingAs($this->admin)
            ->get(route('admin.users.edit', $superAdmin))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->put(route('admin.users.update', $superAdmin), [
                'name' => 'Hacked Super Admin',
                'email' => $superAdmin->email,
                'role' => UserRole::SuperAdmin->value,
                'status' => 'inactive',
            ])
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $superAdmin))
            ->assertForbidden();

        expect($superAdmin->fresh()->name)->not->toBe('Hacked Super Admin');
    });
});
