<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Models\UserDevice;
use App\Support\UserAgentParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    seedRbac();
});

it('creates user devices table with tracking columns', function (): void {
    expect(Schema::hasTable('user_devices'))->toBeTrue()
        ->and(Schema::hasColumn('user_devices', 'browser'))->toBeTrue()
        ->and(Schema::hasColumn('user_devices', 'os'))->toBeTrue()
        ->and(Schema::hasColumn('user_devices', 'is_trusted'))->toBeTrue()
        ->and(Schema::hasColumn('user_devices', 'session_id'))->toBeTrue();
});

it('parses browser and os from user agents', function (): void {
    $chromeWindows = UserAgentParser::parse('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36');
    $safariIos = UserAgentParser::parse('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1');

    expect($chromeWindows['browser'])->toBe('Google Chrome')
        ->and($chromeWindows['os'])->toBe('Windows')
        ->and($safariIos['browser'])->toBe('Safari')
        ->and($safariIos['os'])->toBe('iOS');
});

it('tracks device details on login', function (): void {
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'device-track@example.com',
        'email_verified_at' => now(),
    ]);

    $this->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36')
        ->post(route('login.store'), [
            'email' => $student->email,
            'password' => 'password',
        ])->assertRedirect();

    $device = UserDevice::query()->where('user_id', $student->id)->first();

    expect($device)->not->toBeNull()
        ->and($device->ip_address)->not->toBeNull()
        ->and($device->browser)->toBe('Google Chrome')
        ->and($device->os)->toBe('Windows')
        ->and($device->session_id)->not->toBeNull()
        ->and($device->is_trusted)->toBeFalse();
});

it('shows device management page with browser ip and os', function (): void {
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'device-page@example.com',
        'email_verified_at' => now(),
    ]);

    $loginResponse = $this->post(route('login.store'), [
        'email' => $student->email,
        'password' => 'password',
    ]);

    $sessionCookie = config('session.cookie');
    $sessionId = $loginResponse->getCookie($sessionCookie)?->getValue();

    $this->withCookie($sessionCookie, $sessionId)
        ->get(route('account.devices.index'))
        ->assertOk()
        ->assertSee('Device management')
        ->assertSee('Known devices')
        ->assertSee('Active sessions')
        ->assertSee('Current device');
});

it('marks and removes trusted devices', function (): void {
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'device-trust@example.com',
        'email_verified_at' => now(),
    ]);

    $loginResponse = $this->post(route('login.store'), [
        'email' => $student->email,
        'password' => 'password',
    ]);

    $sessionCookie = config('session.cookie');
    $this->withCookie($sessionCookie, $loginResponse->getCookie($sessionCookie)?->getValue());

    $device = UserDevice::query()->where('user_id', $student->id)->firstOrFail();

    $this->post(route('account.devices.trust', $device))
        ->assertRedirect();

    expect($device->fresh()->is_trusted)->toBeTrue();

    $this->delete(route('account.devices.untrust', $device))
        ->assertRedirect();

    expect($device->fresh()->is_trusted)->toBeFalse();
});

it('lists active sessions with browser and os', function (): void {
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'device-sessions@example.com',
        'email_verified_at' => now(),
    ]);

    $loginResponse = $this->post(route('login.store'), [
        'email' => $student->email,
        'password' => 'password',
    ]);

    $sessionCookie = config('session.cookie');
    $sessionId = $loginResponse->getCookie($sessionCookie)?->getValue();

    $this->withCookie($sessionCookie, $sessionId);

    DB::table('sessions')->insert([
        'id' => 'other-device-session',
        'user_id' => $student->id,
        'ip_address' => '10.0.0.2',
        'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_0) AppleWebKit/605.1.15 Version/17.0 Safari/605.1.15',
        'payload' => base64_encode(serialize([])),
        'last_activity' => now()->subMinutes(5)->timestamp,
    ]);

    $this->get(route('account.devices.index'))
        ->assertOk()
        ->assertSee('Current session')
        ->assertSee('Safari')
        ->assertSee('macOS')
        ->assertSee('10.0.0.2');
});

it('logs out other devices remotely', function (): void {
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'device-logout@example.com',
        'email_verified_at' => now(),
    ]);

    $loginResponse = $this->post(route('login.store'), [
        'email' => $student->email,
        'password' => 'password',
    ]);

    $sessionCookie = config('session.cookie');
    $sessionId = $loginResponse->getCookie($sessionCookie)?->getValue();

    $this->withCookie($sessionCookie, $sessionId);

    DB::table('sessions')->insert([
        'id' => 'remote-session-id',
        'user_id' => $student->id,
        'ip_address' => '10.0.0.9',
        'user_agent' => 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 Chrome/120.0.0.0 Mobile Safari/537.36',
        'payload' => base64_encode(serialize([])),
        'last_activity' => now()->subMinutes(2)->timestamp,
    ]);

    UserDevice::query()->create([
        'user_id' => $student->id,
        'device_uuid' => '00000000-0000-0000-0000-000000000099',
        'device_name' => 'Android Chrome',
        'browser' => 'Google Chrome',
        'os' => 'Android',
        'platform' => 'Android',
        'ip_address' => '10.0.0.9',
        'session_id' => 'remote-session-id',
        'is_trusted' => false,
        'last_used_at' => now()->subMinutes(2),
    ]);

    $this->delete(route('account.devices.sessions.destroy-others'))
        ->assertRedirect();

    expect(DB::table('sessions')->where('id', 'remote-session-id')->exists())->toBeFalse()
        ->and(UserDevice::query()->where('session_id', 'remote-session-id')->exists())->toBeFalse()
        ->and(DB::table('sessions')->where('id', $sessionId)->exists())->toBeTrue();
});

it('prevents users from managing another users device', function (): void {
    $owner = createUserWithRole(UserRole::Student, [
        'email' => 'device-owner@example.com',
        'email_verified_at' => now(),
    ]);

    $intruder = createUserWithRole(UserRole::Student, [
        'email' => 'device-intruder@example.com',
        'email_verified_at' => now(),
    ]);

    $device = UserDevice::query()->create([
        'user_id' => $owner->id,
        'device_uuid' => '00000000-0000-0000-0000-000000000001',
        'device_name' => 'Owner Chrome',
        'browser' => 'Google Chrome',
        'os' => 'Windows',
        'platform' => 'Windows',
        'ip_address' => '127.0.0.1',
        'is_trusted' => false,
        'last_used_at' => now(),
    ]);

    $this->actingAs($intruder)
        ->post(route('account.devices.trust', $device))
        ->assertForbidden();
});
