<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Enrollment\CourseEnrollmentStatus;
use App\Enums\Enrollment\StudentPackageStatus;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseEnrollment;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\Package;
use App\Models\StudentPackage;
use App\Services\Enrollment\CourseEnrollmentService;
use App\Services\Enrollment\EnrollmentService;
use App\Services\Enrollment\PackageAccessService;
use App\Services\Enrollment\ProgressTrackingService;
use Database\Seeders\DemoCoursePackageSeeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

beforeEach(function (): void {
    seedRbac();
});

function createPublishedCourseWithLessons(): Course
{
    $category = CourseCategory::query()->create([
        'name' => 'Test Category',
        'slug' => 'test-category-'.Str::random(4),
        'status' => 'active',
    ]);

    $course = Course::query()->create([
        'course_category_id' => $category->id,
        'slug' => 'test-course-'.Str::random(4),
        'title' => 'Test Course',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $section = CourseSection::query()->create([
        'course_id' => $course->id,
        'title' => 'Section 1',
        'slug' => 'section-1',
        'status' => 'published',
    ]);

    Lesson::query()->create([
        'course_section_id' => $section->id,
        'slug' => 'lesson-1',
        'title' => 'Lesson 1',
        'content_type' => 'video',
        'duration_seconds' => 600,
        'status' => 'published',
        'published_at' => now(),
    ]);

    Lesson::query()->create([
        'course_section_id' => $section->id,
        'slug' => 'lesson-2',
        'title' => 'Lesson 2',
        'content_type' => 'text',
        'duration_seconds' => 900,
        'status' => 'published',
        'published_at' => now(),
    ]);

    return $course->fresh(['sections.lessons']);
}

it('creates enrollment system tables with expected columns', function (): void {
    expect(Schema::hasTable('course_enrollments'))->toBeTrue();
    expect(Schema::hasTable('lesson_progress'))->toBeTrue();
    expect(Schema::hasTable('module_attempt_usages'))->toBeTrue();
    expect(Schema::hasColumns('course_enrollments', [
        'user_id',
        'course_id',
        'student_package_id',
        'status',
        'progress_percent',
    ]))->toBeTrue();
});

it('activates a student package and provisions course enrollments', function (): void {
    $student = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);
    $course = createPublishedCourseWithLessons();

    $package = createDemoPackage([
        'slug' => 'enrollment-test-package',
        'name' => 'Enrollment Test Package',
        'duration_days' => 30,
        'module_access' => IeltsModule::values(),
        'attempt_limits' => ['speaking' => 3],
        'is_public' => true,
        'is_active' => true,
    ]);

    $package->courses()->attach($course->id, ['sort_order' => 1, 'is_featured' => true]);

    $studentPackage = app(EnrollmentService::class)->enrollInPackage($student, $package);

    expect($studentPackage->status)->toBe(StudentPackageStatus::Active)
        ->and($studentPackage->starts_at)->not->toBeNull()
        ->and($studentPackage->expires_at)->not->toBeNull()
        ->and($studentPackage->activated_at)->not->toBeNull();

    expect(CourseEnrollment::query()
        ->where('user_id', $student->id)
        ->where('course_id', $course->id)
        ->where('status', CourseEnrollmentStatus::Active->value)
        ->exists())->toBeTrue();
});

it('tracks lesson progress and syncs course enrollment progress', function (): void {
    $student = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);
    $course = createPublishedCourseWithLessons();
    $package = createDemoPackage(['slug' => 'progress-package', 'is_public' => true, 'is_active' => true]);
    $package->courses()->attach($course->id);

    assignStudentPackage($student, $package);

    $lesson = $course->sections->first()->lessons->first();
    $progressService = app(ProgressTrackingService::class);

    $progressService->completeLesson($student, $lesson);

    expect($progressService->courseProgressPercent($student, $course))->toBe(50);

    $enrollment = CourseEnrollment::query()
        ->where('user_id', $student->id)
        ->where('course_id', $course->id)
        ->first();

    expect($enrollment->progress_percent)->toBe(50);
});

it('enforces module access rules and attempt limits', function (): void {
    $student = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);

    $package = createDemoPackage([
        'slug' => 'limited-speaking-package',
        'module_access' => [IeltsModule::Reading->value, IeltsModule::Speaking->value],
        'attempt_limits' => ['speaking' => 2],
        'is_public' => true,
        'is_active' => true,
    ]);

    assignStudentPackage($student, $package);
    $access = app(PackageAccessService::class);

    expect($access->canAccessModule($student, IeltsModule::Reading))->toBeTrue()
        ->and($access->canAccessModule($student, IeltsModule::Listening))->toBeFalse()
        ->and($access->remainingAttempts($student, IeltsModule::Speaking))->toBe(2);

    $access->recordModuleAttempt($student, IeltsModule::Speaking);
    $access->recordModuleAttempt($student, IeltsModule::Speaking);

    expect($access->remainingAttempts($student, IeltsModule::Speaking))->toBe(0)
        ->and($access->canAccessModule($student, IeltsModule::Speaking))->toBeFalse();
});

it('expires packages and linked course enrollments', function (): void {
    $student = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);
    $course = createPublishedCourseWithLessons();
    $package = createDemoPackage(['slug' => 'expiring-package', 'is_public' => true, 'is_active' => true]);
    $package->courses()->attach($course->id);

    $studentPackage = assignStudentPackage($student, $package);
    app(EnrollmentService::class)->expirePackage($studentPackage);

    expect($studentPackage->fresh()->status)->toBe(StudentPackageStatus::Expired)
        ->and(app(CourseEnrollmentService::class)->canAccessCourse($student, $course))->toBeFalse();
});

it('allows students to activate public packages from the student area', function (): void {
    $student = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);
    $package = createDemoPackage([
        'slug' => 'student-self-enroll',
        'is_public' => true,
        'is_active' => true,
        'status' => 'active',
    ]);

    $this->actingAs($student)
        ->post(route('student.packages.enroll', $package))
        ->assertRedirect(route('student.packages.index'));

    expect(StudentPackage::query()->where('user_id', $student->id)->where('package_id', $package->id)->exists())->toBeTrue();
});

it('allows admin to assign package enrollment to a student', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);
    $student = createUserWithRole(UserRole::Student, ['email' => 'assigned-student@example.com', 'email_verified_at' => now()]);
    $package = createDemoPackage(['slug' => 'admin-assigned-package', 'is_active' => true]);

    $this->actingAs($admin)
        ->post(route('admin.enrollments.store'), [
            'user_id' => $student->id,
            'package_id' => $package->id,
        ])
        ->assertRedirect(route('admin.enrollments.index'));

    expect(StudentPackage::query()->where('user_id', $student->id)->where('package_id', $package->id)->exists())->toBeTrue();
});

it('blocks exam modules without active package access', function (): void {
    $student = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);

    $this->actingAs($student)
        ->get(route('exam.reading'))
        ->assertForbidden();
});

it('allows exam modules when package grants access', function (): void {
    $student = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);

    assignStudentPackage($student, createDemoPackage([
        'slug' => 'reading-access-package',
        'module_access' => [IeltsModule::Reading->value],
        'is_public' => true,
        'is_active' => true,
    ]));

    $this->actingAs($student)
        ->get(route('exam.reading'))
        ->assertOk();
});

it('blocks course pages without enrollment and allows enrolled students', function (): void {
    $student = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);
    $course = createPublishedCourseWithLessons();

    $this->actingAs($student)
        ->get(route('student.courses.show', $course))
        ->assertForbidden();

    $package = createDemoPackage(['slug' => 'course-access-package', 'is_public' => true, 'is_active' => true]);
    $package->courses()->attach($course->id);
    assignStudentPackage($student, $package);

    $this->actingAs($student)
        ->get(route('student.courses.show', $course))
        ->assertOk()
        ->assertSee($course->title);
});

it('updates lesson progress through the student api route', function (): void {
    $student = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);
    $course = createPublishedCourseWithLessons();
    $package = createDemoPackage(['slug' => 'api-progress-package', 'is_public' => true, 'is_active' => true]);
    $package->courses()->attach($course->id);
    assignStudentPackage($student, $package);

    $lesson = $course->sections->first()->lessons->first();

    $this->actingAs($student)
        ->putJson(route('student.lessons.progress.update', $lesson), [
            'progress_percent' => 100,
            'time_spent_seconds' => 120,
        ])
        ->assertOk()
        ->assertJsonPath('data.progress_percent', 100)
        ->assertJsonPath('data.course_progress_percent', 50);
});

it('works with demo seeded packages for enrollment flows', function (): void {
    $this->seed(\Database\Seeders\SuperAdminSeeder::class);
    $this->seed(DemoCoursePackageSeeder::class);

    $student = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);
    $package = Package::query()->where('slug', 'free-trial')->firstOrFail();

    assignStudentPackage($student, $package);
    $access = app(PackageAccessService::class);

    expect($access->canAccessModule($student, IeltsModule::Reading))->toBeTrue()
        ->and($access->remainingAttempts($student, IeltsModule::Writing))->toBe(1)
        ->and(app(CourseEnrollmentService::class)->accessibleEnrollments($student))->not->toBeEmpty();
});
