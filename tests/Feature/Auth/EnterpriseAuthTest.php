<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    seedRbac();
});

it('registers a student and requires email verification', function (): void {
    Notification::fake();

    $response = $this->post(route('register.store'), [
        'name' => 'Enterprise Student',
        'email' => 'enterprise-student@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect(route('verification.notice'));
    $this->assertAuthenticated();

    $user = User::query()->where('email', 'enterprise-student@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->hasVerifiedEmail())->toBeFalse();

    Notification::assertSentTo($user, VerifyEmail::class);
});

it('blocks unverified users from dashboards', function (): void {
    $student = User::factory()->unverified()->create();
    $student->assignRole(UserRole::Student);
    $student->studentProfile()->create([]);

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertRedirect(route('verification.notice'));
});

it('verifies email using signed link', function (): void {
    $student = User::factory()->unverified()->create();
    $student->assignRole(UserRole::Student);
    $student->studentProfile()->create([]);

    $url = URL::temporarySignedRoute(
        'verification.verify',
        now()->addHour(),
        ['id' => $student->id, 'hash' => sha1($student->email)]
    );

    $this->actingAs($student)
        ->get($url)
        ->assertRedirect(route('student.dashboard'));

    expect($student->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('resends verification notification', function (): void {
    Notification::fake();

    $student = User::factory()->unverified()->create();
    $student->assignRole(UserRole::Student);

    $this->actingAs($student)
        ->post(route('verification.send'))
        ->assertRedirect();

    Notification::assertSentTo($student, VerifyEmail::class);
});

it('sends password reset link and resets password', function (): void {
    $student = createUserWithRole(UserRole::Student, ['email' => 'reset-student@example.com']);

    $this->post(route('password.email'), ['email' => $student->email])
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->assertDatabaseHas('password_reset_tokens', ['email' => $student->email]);

    $token = Password::createToken($student);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $student->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertRedirect(route('login'));

    expect(Hash::check('newpassword123', $student->fresh()->password))->toBeTrue();
});

it('persists remember me token on login', function (): void {
    $student = createUserWithRole(UserRole::Student, ['email' => 'remember@example.com']);
    $student->forceFill(['remember_token' => null])->save();

    $this->post(route('login.store'), [
        'email' => $student->email,
        'password' => 'password',
        'remember' => '1',
    ])->assertRedirect(route('student.dashboard'));

    expect($student->fresh()->remember_token)->not->toBeNull();
});

it('logs successful and failed authentication attempts', function (): void {
    $student = createUserWithRole(UserRole::Student, ['email' => 'audit@example.com']);

    $this->post(route('login.store'), [
        'email' => $student->email,
        'password' => 'password',
    ]);

    $this->assertDatabaseHas('login_logs', [
        'user_id' => $student->id,
        'email' => $student->email,
        'status' => 'success',
    ]);

    $this->post(route('logout'));

    $this->from(route('login'))->post(route('login.store'), [
        'email' => $student->email,
        'password' => 'wrong-password',
    ]);

    $this->assertDatabaseHas('login_logs', [
        'email' => $student->email,
        'status' => 'failed',
    ]);
});

it('confirms password for secure actions', function (): void {
    $student = createUserWithRole(UserRole::Student);

    $this->actingAs($student)
        ->post(route('password.confirm.store'), ['password' => 'password'])
        ->assertRedirect(route('student.dashboard'));

    expect(session('auth.password_confirmed_at'))->not->toBeNull();
});

it('manages active sessions in the database', function (): void {
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'sessions@example.com',
        'email_verified_at' => now(),
    ]);

    $loginResponse = $this->post(route('login.store'), [
        'email' => $student->email,
        'password' => 'password',
    ]);

    $loginResponse->assertRedirect();

    $sessionCookie = config('session.cookie');
    $sessionId = $loginResponse->getCookie($sessionCookie)?->getValue();

    expect($sessionId)->not->toBeNull();

    $this->withCookie($sessionCookie, $sessionId);

    DB::table('sessions')->insert([
        'id' => 'other-session-id',
        'user_id' => $student->id,
        'ip_address' => '10.0.0.2',
        'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
        'payload' => base64_encode(serialize([])),
        'last_activity' => now()->subMinutes(5)->timestamp,
    ]);

    $this->get(route('account.sessions.index'))
        ->assertOk()
        ->assertSee('Current session')
        ->assertSee('Google Chrome')
        ->assertSee('Windows');

    $this->delete(route('account.sessions.destroy-others'))
        ->assertRedirect();

    expect(DB::table('sessions')->where('id', 'other-session-id')->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('id', $sessionId)->exists())->toBeTrue();
});
