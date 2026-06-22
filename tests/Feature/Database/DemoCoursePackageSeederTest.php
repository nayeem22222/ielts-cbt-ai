<?php

declare(strict_types=1);

use App\Enums\Commerce\IeltsModule;
use App\Enums\Commerce\PackageDiscountType;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\LessonResource;
use App\Models\Package;
use Database\Seeders\DemoCoursePackageSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SuperAdminSeeder;

beforeEach(function (): void {
    $this->seed(RoleSeeder::class);
    $this->seed(PermissionSeeder::class);
    $this->seed(SuperAdminSeeder::class);
});

it('seeds demo course categories courses sections lessons resources and packages', function (): void {
    $this->seed(DemoCoursePackageSeeder::class);

    expect(CourseCategory::query()->whereIn('slug', ['ielts-preparation', 'spoken-english'])->count())->toBe(2);
    expect(Course::query()->whereIn('slug', [
        'complete-ielts-course',
        'spoken-english-for-beginners',
        'spoken-english-for-kids',
    ])->count())->toBe(3);

    $ieltsCourse = Course::query()->where('slug', 'complete-ielts-course')->first();
    expect($ieltsCourse)->not->toBeNull();
    expect($ieltsCourse->sections()->count())->toBeGreaterThanOrEqual(3);
    expect($ieltsCourse->lessons()->count())->toBeGreaterThanOrEqual(9);
    expect($ieltsCourse->resources()->count())->toBeGreaterThanOrEqual(2);

    expect(Package::query()->whereIn('slug', [
        'free-trial',
        'ielts-monthly-package',
        'ielts-2-month-package',
        'ielts-3-month-package',
        'spoken-english-package',
        'kids-english-package',
    ])->count())->toBe(6);
});

it('is idempotent when demo seeder runs more than once', function (): void {
    $this->seed(DemoCoursePackageSeeder::class);
    $this->seed(DemoCoursePackageSeeder::class);

    expect(CourseCategory::query()->count())->toBe(2);
    expect(Course::query()->count())->toBe(3);
    expect(CourseSection::query()->count())->toBe(12);
    expect(Lesson::query()->count())->toBe(44);
    expect(LessonResource::query()->count())->toBe(12);
    expect(Package::query()->count())->toBe(6);
});

it('assigns realistic package module access and attempt limits', function (): void {
    $this->seed(DemoCoursePackageSeeder::class);

    $freeTrial = Package::query()->where('slug', 'free-trial')->firstOrFail();
    expect($freeTrial->allowsModule(IeltsModule::Reading))->toBeTrue();
    expect($freeTrial->attemptLimitFor(IeltsModule::Reading))->toBe(2);
    expect($freeTrial->attemptLimitFor(IeltsModule::Writing))->toBe(1);
    expect($freeTrial->attemptLimitFor(IeltsModule::Speaking))->toBe(1);

    $monthly = Package::query()->where('slug', 'ielts-monthly-package')->firstOrFail();
    expect($monthly->price)->toBe('1500.00');
    expect($monthly->duration_days)->toBe(30);
    expect($monthly->discount_type)->toBe(PackageDiscountType::Percent);
    expect($monthly->attemptLimitFor(IeltsModule::Speaking))->toBe(3);
    expect($monthly->attemptLimitFor(IeltsModule::Reading))->toBeNull();

    $twoMonth = Package::query()->where('slug', 'ielts-2-month-package')->firstOrFail();
    expect($twoMonth->attemptLimitFor(IeltsModule::Speaking))->toBe(6);

    $threeMonth = Package::query()->where('slug', 'ielts-3-month-package')->firstOrFail();
    expect($threeMonth->attemptLimitFor(IeltsModule::Speaking))->toBe(9);

    $spoken = Package::query()->where('slug', 'spoken-english-package')->firstOrFail();
    expect($spoken->allowsModule(IeltsModule::Listening))->toBeTrue();
    expect($spoken->allowsModule(IeltsModule::Reading))->toBeFalse();
    expect($spoken->courses()->where('slug', 'spoken-english-for-beginners')->exists())->toBeTrue();
});

it('stores short and full course descriptions in the description field', function (): void {
    $this->seed(DemoCoursePackageSeeder::class);

    $course = Course::query()->where('slug', 'complete-ielts-course')->firstOrFail();

    expect($course->description)->toContain('full IELTS preparation pathway');
    expect($course->description)->toContain('Build confidence across all four IELTS modules');
    expect($course->thumbnail_path)->toBe('placeholders/courses/complete-ielts-course.jpg');
});
