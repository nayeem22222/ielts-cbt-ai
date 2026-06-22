<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use Database\Seeders\RoleSeeder;

beforeEach(function (): void {
    seedRbac();
});

it('renders enterprise admin layout on dashboard', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'layout-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-admin-layout', false)
        ->assertSee('data-admin-sidebar', false)
        ->assertSee('data-admin-breadcrumb', false)
        ->assertSee('Admin navigation', false)
        ->assertSee('Operations Control Center')
        ->assertSee('All rights reserved');
});

it('renders sidebar navigation links', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'layout-nav@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Access Management')
        ->assertSee(route('admin.users.index'), false)
        ->assertSee(route('admin.roles.index'), false)
        ->assertSee(route('admin.permissions.index'), false);
});

it('renders breadcrumbs on users page', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'layout-breadcrumb@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('aria-label="Breadcrumb"', false)
        ->assertSee('Dashboard')
        ->assertSee('Users');
});

it('includes dark mode and sidebar persistence hooks', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'layout-state@example.com',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get(route('admin.dashboard'));

    $response->assertOk()
        ->assertSee('adminLayoutState()', false)
        ->assertSee('toggleDark()', false)
        ->assertSee('toggleCollapsed()', false)
        ->assertSee('toggleMenu(', false)
        ->assertSee('data-storage-dark="aa-admin-dark"', false)
        ->assertSee('data-storage-collapsed="aa-admin-sidebar-collapsed"', false)
        ->assertSee('data-storage-menu="aa-admin-menu-open"', false);
});

it('includes responsive mobile menu markup', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'layout-mobile@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('data-admin-mobile-menu', false)
        ->assertSee('Open navigation menu', false)
        ->assertSee('toggleMobile()', false);
});

it('renders admin footer links', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'layout-footer@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.roles.index'))
        ->assertOk()
        ->assertSee('Security')
        ->assertSee(route('account.devices.index'), false)
        ->assertSee('v1.0');
});
