<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Enrollment\CourseEnrollmentStatus;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\LessonResource;
use App\Models\Package;
use App\Services\Enrollment\ProgressTrackingService;
use Database\Seeders\DemoCoursePackageSeeder;
use Illuminate\Support\Str;

beforeEach(function (): void {
    seedRbac();
});

it('renders student lms dashboard sections', function (): void {
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'lms-dashboard@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('Learning Dashboard')
        ->assertSee('Continue Learning')
        ->assertSee('Course Progress')
        ->assertSee('Upcoming Live Classes')
        ->assertSee('Downloads')
        ->assertSee('Assignments')
        ->assertSee('Certificates')
        ->assertSee('Browse packages');
});

it('shows real enrollment data on the student lms dashboard', function (): void {
    $this->seed(\Database\Seeders\SuperAdminSeeder::class);
    $this->seed(DemoCoursePackageSeeder::class);

    $student = createUserWithRole(UserRole::Student, [
        'email' => 'lms-data@example.com',
        'email_verified_at' => now(),
    ]);

    $package = Package::query()->where('slug', 'free-trial')->firstOrFail();
    assignStudentPackage($student, $package);

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('Complete IELTS Course')
        ->assertDontSee('Browse packages');
});

it('shows continue learning and progress after lesson activity', function (): void {
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'lms-progress@example.com',
        'email_verified_at' => now(),
    ]);

    $category = CourseCategory::query()->create([
        'name' => 'LMS Category',
        'slug' => 'lms-category',
        'status' => 'active',
    ]);

    $course = Course::query()->create([
        'course_category_id' => $category->id,
        'slug' => 'lms-course',
        'title' => 'LMS Progress Course',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $section = CourseSection::query()->create([
        'course_id' => $course->id,
        'title' => 'Section 1',
        'slug' => 'section-1',
        'status' => 'published',
    ]);

    $lesson = Lesson::query()->create([
        'course_section_id' => $section->id,
        'slug' => 'lesson-1',
        'title' => 'Resume This Lesson',
        'content_type' => 'video',
        'duration_seconds' => 600,
        'status' => 'published',
        'published_at' => now(),
    ]);

    Lesson::query()->create([
        'course_section_id' => $section->id,
        'slug' => 'live-lesson',
        'title' => 'Live Strategy Session',
        'content_type' => 'live',
        'duration_seconds' => 1800,
        'status' => 'published',
        'published_at' => now()->addDay(),
    ]);

    Lesson::query()->create([
        'course_section_id' => $section->id,
        'slug' => 'assignment-lesson',
        'title' => 'Assignment: Weekly Task',
        'content_type' => 'quiz',
        'duration_seconds' => 900,
        'status' => 'published',
        'published_at' => now(),
    ]);

    LessonResource::query()->create([
        'course_id' => $course->id,
        'title' => 'Practice Sheet',
        'file_path' => 'demo/practice-sheet.pdf',
        'file_type' => 'pdf',
        'is_downloadable' => true,
    ]);

    $package = createDemoPackage([
        'slug' => 'lms-dashboard-package',
        'is_public' => true,
        'is_active' => true,
    ]);
    $package->courses()->attach($course->id);
    assignStudentPackage($student, $package);

    app(ProgressTrackingService::class)->updateProgress($student, $lesson, 45);

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('Resume This Lesson')
        ->assertSee('Live Strategy Session')
        ->assertSee('Practice Sheet')
        ->assertSee('Assignment: Weekly Task')
        ->assertSee('Resume Lesson');
});

it('shows certificates for completed courses', function (): void {
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'lms-cert@example.com',
        'email_verified_at' => now(),
    ]);

    $course = Course::query()->create([
        'slug' => 'completed-course-'.Str::random(4),
        'title' => 'Completed LMS Course',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $package = createDemoPackage(['slug' => 'cert-package-'.Str::random(4), 'is_public' => true, 'is_active' => true]);
    $package->courses()->attach($course->id);
    assignStudentPackage($student, $package);

    $student->courseEnrollments()->update([
        'progress_percent' => 100,
        'status' => CourseEnrollmentStatus::Completed->value,
        'completed_at' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('Completed LMS Course')
        ->assertSee('Certificate');
});

it('uses responsive dashboard layout classes', function (): void {
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'lms-responsive@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('sm:grid-cols-2', false)
        ->assertSee('xl:grid-cols-4', false)
        ->assertSee('xl:grid-cols-[1.35fr_.65fr]', false);
});

it('renders updated student sidebar navigation links', function (): void {
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'lms-sidebar@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('Student LMS')
        ->assertSee(route('student.courses.index'), false)
        ->assertSee(route('student.packages.index'), false)
        ->assertSee('#downloads', false);
});
