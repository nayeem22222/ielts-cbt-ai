<?php

declare(strict_types=1);

use App\Enums\Auth\AuthenticationEventType;
use App\Enums\Auth\UserRole;
use App\Models\AuthEventLog;

beforeEach(function (): void {
    seedRbac();
});

it('renders dashboard with kpi cards', function (): void {
    createUserWithRole(UserRole::Student, ['email' => 'kpi-student@example.com']);

    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'kpi-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-dashboard-kpis', false)
        ->assertSee('Total Users')
        ->assertSee('Revenue')
        ->assertSee('AI Evaluations')
        ->assertSee('Active Sessions');
});

it('renders dashboard charts section', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'charts-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('User Growth')
        ->assertSee('Revenue Trend')
        ->assertSee('Module Completion')
        ->assertSee('data-dashboard-chart', false);
});

it('renders recent activities from auth events', function (): void {
    $student = createUserWithRole(UserRole::Student, ['email' => 'activity-student@example.com']);

    AuthEventLog::query()->create([
        'user_id' => $student->id,
        'email' => $student->email,
        'event' => AuthenticationEventType::UserLoggedIn->value,
        'ip_address' => '127.0.0.1',
        'created_at' => now(),
    ]);

    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'activity-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-dashboard-activities', false)
        ->assertSee('User logged in')
        ->assertSee($student->name);
});

it('renders quick actions with admin routes', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'actions-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-dashboard-quick-actions', false)
        ->assertSee('Quick Actions')
        ->assertSee(route('admin.users.create'), false)
        ->assertSee(route('admin.roles.index'), false);
});

it('renders notification center', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'notify-admin@example.com',
        'email_verified_at' => now(),
    ]);

    AuthEventLog::query()->create([
        'user_id' => $admin->id,
        'email' => $admin->email,
        'event' => AuthenticationEventType::UserLoggedIn->value,
        'ip_address' => '127.0.0.1',
        'created_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-dashboard-notifications', false)
        ->assertSee('Notification Center');
});

it('renders server health checks', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'health-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-dashboard-server-health', false)
        ->assertSee('Server Health')
        ->assertSee('Database')
        ->assertSee('Cache')
        ->assertSee('Queue');
});

it('renders ai queue widget with summary counts', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'ai-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-dashboard-ai-queue', false)
        ->assertSee('AI Queue')
        ->assertSee('pending')
        ->assertSee('processing')
        ->assertSee('completed')
        ->assertSee('failed');
});

it('uses responsive dashboard grid layout', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'responsive-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('xl:grid-cols-12', false)
        ->assertSee('xl:col-span-8', false)
        ->assertSee('xl:col-span-4', false);
});
