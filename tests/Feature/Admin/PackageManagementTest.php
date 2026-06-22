<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Commerce\BillingInterval;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Commerce\PackageDiscountType;
use App\Enums\Commerce\PackageStatus;
use App\Models\Course;
use App\Models\Package;
use App\Services\Admin\PackageCrudService;

beforeEach(function (): void {
    seedRbac();
});

it('adds package access rule columns to packages table', function (): void {
    expect(\Illuminate\Support\Facades\Schema::hasColumns('packages', [
        'module_access',
        'attempt_limits',
        'discount_type',
        'discount_value',
    ]))->toBeTrue();
});

it('allows admin to view packages index', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'packages-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.packages.index'))
        ->assertOk()
        ->assertSee('Package Directory');
});

it('creates package with module access attempt limits price discount and status', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'packages-crud@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)->post(route('admin.packages.store'), [
        'name' => 'IELTS Pro Monthly',
        'slug' => 'ielts-pro-monthly',
        'description' => 'Full access plan',
        'module_access' => [IeltsModule::Reading->value, IeltsModule::Listening->value],
        'attempt_limits' => [
            'reading' => 10,
            'listening' => 5,
        ],
        'billing_interval' => BillingInterval::Monthly->value,
        'price' => 1999,
        'currency' => 'BDT',
        'discount_type' => PackageDiscountType::Percent->value,
        'discount_value' => 10,
        'duration_days' => 30,
        'trial_days' => 7,
        'is_active' => true,
        'is_public' => true,
        'sort_order' => 1,
        'status' => PackageStatus::Active->value,
    ])->assertRedirect(route('admin.packages.index'));

    $package = Package::query()->where('slug', 'ielts-pro-monthly')->first();

    expect($package)->not->toBeNull();
    expect($package->module_access)->toBe(['reading', 'listening']);
    expect($package->attemptLimitFor(IeltsModule::Reading))->toBe(10);
    expect($package->attemptLimitFor(IeltsModule::Writing))->toBeNull();
    expect($package->allowsModule(IeltsModule::Reading))->toBeTrue();
    expect($package->allowsModule(IeltsModule::Writing))->toBeFalse();
    expect($package->effectivePrice())->toBe(1799.1);
    expect($package->duration_days)->toBe(30);
    expect($package->status)->toBe(PackageStatus::Active);
});

it('updates and soft deletes a package', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'packages-update@example.com',
        'email_verified_at' => now(),
    ]);

    $package = Package::query()->create([
        'name' => 'Starter',
        'slug' => 'starter',
        'billing_interval' => BillingInterval::Monthly,
        'price' => 500,
        'currency' => 'BDT',
        'discount_type' => PackageDiscountType::None,
        'status' => PackageStatus::Active,
    ]);

    $this->actingAs($admin)->put(route('admin.packages.update', $package), [
        'name' => 'Starter Plus',
        'slug' => 'starter-plus',
        'module_access' => IeltsModule::values(),
        'billing_interval' => BillingInterval::Monthly->value,
        'price' => 750,
        'currency' => 'BDT',
        'discount_type' => PackageDiscountType::Fixed->value,
        'discount_value' => 50,
        'duration_days' => 60,
        'status' => PackageStatus::Active->value,
        'is_active' => true,
        'is_public' => true,
    ])->assertRedirect(route('admin.packages.index'));

    expect($package->fresh()->name)->toBe('Starter Plus');
    expect($package->fresh()->effectivePrice())->toBe(700.0);

    $this->actingAs($admin)->delete(route('admin.packages.destroy', $package))
        ->assertRedirect(route('admin.packages.index'));

    expect(Package::query()->count())->toBe(0);
    expect(Package::withTrashed()->count())->toBe(1);
});

it('searches packages through crud service pagination', function (): void {
    Package::query()->create([
        'name' => 'Speaking Intensive',
        'slug' => 'speaking-intensive',
        'billing_interval' => BillingInterval::Monthly,
        'price' => 999,
        'currency' => 'BDT',
        'discount_type' => PackageDiscountType::None,
        'status' => PackageStatus::Active,
    ]);

    Package::query()->create([
        'name' => 'Writing Mastery',
        'slug' => 'writing-mastery',
        'billing_interval' => BillingInterval::Yearly,
        'price' => 4999,
        'currency' => 'BDT',
        'discount_type' => PackageDiscountType::None,
        'status' => PackageStatus::Active,
    ]);

    $results = app(PackageCrudService::class)->paginate(new \App\Crud\CrudQuery(search: 'Speaking'));

    expect($results->total())->toBe(1);
    expect($results->first()?->slug)->toBe('speaking-intensive');
});

it('syncs courses to package on create', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'packages-courses@example.com',
        'email_verified_at' => now(),
    ]);

    $course = Course::query()->create([
        'title' => 'Bundle Course',
        'slug' => 'bundle-course',
        'status' => 'published',
        'exam_type' => 'academic',
        'level' => 'intermediate',
    ]);

    $this->actingAs($admin)->post(route('admin.packages.store'), [
        'name' => 'Bundle Plan',
        'slug' => 'bundle-plan',
        'module_access' => [IeltsModule::Reading->value],
        'billing_interval' => BillingInterval::Monthly->value,
        'price' => 1000,
        'currency' => 'BDT',
        'discount_type' => PackageDiscountType::None->value,
        'duration_days' => 30,
        'status' => PackageStatus::Active->value,
        'is_active' => true,
        'is_public' => true,
        'course_ids' => [$course->id],
    ]);

    $package = Package::query()->where('slug', 'bundle-plan')->first();

    expect($package?->courses)->toHaveCount(1);
    expect($package?->courses->first()?->id)->toBe($course->id);
});

it('restores soft deleted package from trash route', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'packages-restore@example.com',
        'email_verified_at' => now(),
    ]);

    $package = Package::query()->create([
        'name' => 'Restore Me',
        'slug' => 'restore-me',
        'billing_interval' => BillingInterval::Monthly,
        'price' => 100,
        'currency' => 'BDT',
        'discount_type' => PackageDiscountType::None,
        'status' => PackageStatus::Active,
    ]);

    app(PackageCrudService::class)->delete($package);

    $this->actingAs($admin)->put(route('admin.packages.restore', $package->id))->assertRedirect();

    expect(Package::query()->whereKey($package->id)->exists())->toBeTrue();
});

it('blocks teachers from package admin routes', function (): void {
    $teacher = createUserWithRole(UserRole::Teacher, [
        'email' => 'teacher-packages@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($teacher)->get(route('admin.packages.index'))->assertForbidden();
});
