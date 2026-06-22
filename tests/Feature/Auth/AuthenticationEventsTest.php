<?php

declare(strict_types=1);

use App\Enums\Auth\AuthenticationEventType;
use App\Enums\Auth\UserRole;
use App\Models\AuthEventLog;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    seedRbac();
});

it('creates auth event logs table', function (): void {
    expect(Schema::hasTable('auth_event_logs'))->toBeTrue()
        ->and(Schema::hasColumn('auth_event_logs', 'event'))->toBeTrue()
        ->and(Schema::hasColumn('auth_event_logs', 'metadata'))->toBeTrue();
});

it('logs user registered events', function (): void {
    $this->post(route('register.store'), [
        'name' => 'Event Student',
        'email' => 'event-register@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect(route('verification.notice'));

    $user = User::query()->where('email', 'event-register@example.com')->firstOrFail();

    $this->assertDatabaseHas('auth_event_logs', [
        'user_id' => $user->id,
        'email' => 'event-register@example.com',
        'event' => AuthenticationEventType::UserRegistered->value,
    ]);
});

it('logs user logged in events', function (): void {
    $student = createUserWithRole(UserRole::Student, ['email' => 'event-login@example.com']);

    $this->post(route('login.store'), [
        'email' => $student->email,
        'password' => 'password',
    ])->assertRedirect();

    $this->assertDatabaseHas('auth_event_logs', [
        'user_id' => $student->id,
        'email' => $student->email,
        'event' => AuthenticationEventType::UserLoggedIn->value,
    ]);

    $this->assertDatabaseHas('login_logs', [
        'user_id' => $student->id,
        'status' => 'success',
    ]);
});

it('logs user logged out events', function (): void {
    $student = createUserWithRole(UserRole::Student, ['email' => 'event-logout@example.com']);

    $this->actingAs($student)->post(route('logout'));

    $this->assertDatabaseHas('auth_event_logs', [
        'user_id' => $student->id,
        'email' => $student->email,
        'event' => AuthenticationEventType::UserLoggedOut->value,
    ]);
});

it('logs password changed events from reset flow', function (): void {
    Notification::fake();

    $student = createUserWithRole(UserRole::Student, ['email' => 'event-reset@example.com']);

    $this->post(route('password.email'), ['email' => $student->email]);

    Notification::assertSentTo($student, ResetPassword::class, function (ResetPassword $notification) use ($student): bool {
        $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $student->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'));

        return true;
    });

    $this->assertDatabaseHas('auth_event_logs', [
        'user_id' => $student->id,
        'email' => $student->email,
        'event' => AuthenticationEventType::PasswordChanged->value,
    ]);

    $log = AuthEventLog::query()
        ->where('user_id', $student->id)
        ->where('event', AuthenticationEventType::PasswordChanged->value)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->metadata)->toBe(['source' => 'password_reset']);
});

it('logs password changed events from admin updates', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'event-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $student = createUserWithRole(UserRole::Student, ['email' => 'event-student@example.com']);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $student), [
            'name' => $student->name,
            'email' => $student->email,
            'phone' => '',
            'role' => UserRole::Student->value,
            'status' => 'active',
            'password' => 'new-admin-password',
            'password_confirmation' => 'new-admin-password',
        ])
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('auth_event_logs', [
        'user_id' => $student->id,
        'event' => AuthenticationEventType::PasswordChanged->value,
    ]);

    expect(AuthEventLog::query()
        ->where('user_id', $student->id)
        ->where('event', AuthenticationEventType::PasswordChanged->value)
        ->value('metadata'))->toBe(['source' => 'admin']);
});

it('logs failed login events', function (): void {
    $student = createUserWithRole(UserRole::Student, ['email' => 'event-failed@example.com']);

    $this->from(route('login'))->post(route('login.store'), [
        'email' => $student->email,
        'password' => 'wrong-password',
    ]);

    $this->assertDatabaseHas('auth_event_logs', [
        'email' => $student->email,
        'event' => AuthenticationEventType::LoginFailed->value,
    ]);

    $this->assertDatabaseHas('login_logs', [
        'email' => $student->email,
        'status' => 'failed',
    ]);
});

it('stores ip address and user agent on authentication events', function (): void {
    $student = createUserWithRole(UserRole::Student, ['email' => 'event-meta@example.com']);

    $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0')
        ->post(route('login.store'), [
            'email' => $student->email,
            'password' => 'password',
        ]);

    $log = AuthEventLog::query()
        ->where('event', AuthenticationEventType::UserLoggedIn->value)
        ->where('user_id', $student->id)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->ip_address)->not->toBeNull()
        ->and($log->user_agent)->toContain('Chrome');
});
