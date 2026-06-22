<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Models\Setting;
use App\Services\Admin\SettingsService;
use Illuminate\Support\Facades\Crypt;

beforeEach(function (): void {
    seedRbac();
    app(SettingsService::class)->seedDefaults();
});

it('creates settings table with expected columns', function (): void {
    expect(\Illuminate\Support\Facades\Schema::hasTable('settings'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Schema::hasColumns('settings', ['group', 'key', 'value', 'is_encrypted']))->toBeTrue();
});

it('allows super admin to view settings page with all tabs', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'settings-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee('Platform Settings')
        ->assertSee('General')
        ->assertSee('Brand')
        ->assertSee('AI')
        ->assertSee('Payment')
        ->assertSee('Storage')
        ->assertSee('Redis')
        ->assertSee('Queue')
        ->assertSee('Security')
        ->assertSee('Backup');
});

it('renders settings link in admin sidebar', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'settings-nav@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(route('admin.settings.index'), false)
        ->assertSee('Settings');
});

it('prevents teachers from accessing settings', function (): void {
    $teacher = createUserWithRole(UserRole::Teacher, [
        'email' => 'settings-teacher@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($teacher)
        ->get(route('admin.settings.index'))
        ->assertForbidden();
});

it('updates general settings', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'settings-update@example.com',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->put(route('admin.settings.update', 'general'), [
        'site_name' => 'Updated Academy',
        'site_tagline' => 'Updated tagline',
        'support_email' => 'help@updated.com',
        'default_locale' => 'en',
        'timezone' => 'UTC',
        'maintenance_mode' => false,
        'maintenance_message' => 'Back soon',
    ]);

    $response->assertRedirect(route('admin.settings.index', ['tab' => 'general']));

    expect(setting('general', 'site_name'))->toBe('Updated Academy');
    expect(setting('general', 'support_email'))->toBe('help@updated.com');
});

it('stores encrypted api keys without exposing them in the view', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'settings-ai@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)->put(route('admin.settings.update', 'ai'), [
        'default_provider' => 'openai',
        'api_key' => 'sk-test-secret-key',
        'default_model' => 'gpt-4o-mini',
        'max_tokens' => 4096,
        'temperature' => 0.7,
        'evaluation_enabled' => true,
        'request_timeout' => 60,
    ]);

    $stored = Setting::query()->where('group', 'ai')->where('key', 'api_key')->first();

    expect($stored)->not->toBeNull();
    expect($stored->is_encrypted)->toBeTrue();
    expect($stored->value)->not->toBe('sk-test-secret-key');
    expect(Crypt::decryptString((string) $stored->value))->toBe('sk-test-secret-key');

    $this->actingAs($admin)
        ->get(route('admin.settings.index', ['tab' => 'ai']))
        ->assertOk()
        ->assertDontSee('sk-test-secret-key');
});

it('shows infrastructure status on storage tab', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'settings-infra@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.settings.index', ['tab' => 'storage']))
        ->assertOk()
        ->assertSee('Infrastructure Status');
});

it('runs on-demand backup for authorized admins', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'settings-backup@example.com',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->post(route('admin.settings.backup.run'));

    $response->assertRedirect(route('admin.settings.index', ['tab' => 'backup']));
    expect(\Illuminate\Support\Facades\Storage::disk('local')->allFiles('backups'))->not->toBeEmpty();
});

it('seeds default settings for all groups', function (): void {
    $groups = ['general', 'brand', 'ai', 'payment', 'storage', 'redis', 'queue', 'security', 'backup'];

    foreach ($groups as $group) {
        expect(Setting::query()->where('group', $group)->count())->toBeGreaterThan(0);
    }
});
