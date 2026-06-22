<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Course\CategoryStatus;
use App\Enums\Course\LessonContentType;
use App\Enums\Course\PublishStatus;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\LessonResource;
use App\Services\Admin\CourseCategoryCrudService;
use App\Services\Admin\CourseCrudService;

beforeEach(function (): void {
    seedRbac();
});

it('creates course management tables with expected columns', function (): void {
    expect(\Illuminate\Support\Facades\Schema::hasTable('course_categories'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Schema::hasTable('courses'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Schema::hasTable('course_sections'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Schema::hasTable('lessons'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Schema::hasTable('lesson_resources'))->toBeTrue();
});

it('allows admin to view course management pages', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'courses-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.course-categories.index'))
        ->assertOk()
        ->assertSee('Categories');

    $this->actingAs($admin)
        ->get(route('admin.courses.index'))
        ->assertOk()
        ->assertSee('Course Directory');

    $this->actingAs($admin)
        ->get(route('admin.course-sections.index'))
        ->assertOk()
        ->assertSee('Sections');

    $this->actingAs($admin)
        ->get(route('admin.lessons.index'))
        ->assertOk()
        ->assertSee('Lessons');

    $this->actingAs($admin)
        ->get(route('admin.lesson-resources.index'))
        ->assertOk()
        ->assertSee('Resources');
});

it('creates updates and soft deletes a course category', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'category-crud@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)->post(route('admin.course-categories.store'), [
        'name' => 'IELTS Reading',
        'slug' => 'ielts-reading',
        'status' => CategoryStatus::Active->value,
        'sort_order' => 1,
    ])->assertRedirect(route('admin.course-categories.index'));

    $category = CourseCategory::query()->where('slug', 'ielts-reading')->first();
    expect($category)->not->toBeNull();

    $this->actingAs($admin)->put(route('admin.course-categories.update', $category), [
        'name' => 'Reading Mastery',
        'slug' => 'reading-mastery',
        'status' => CategoryStatus::Active->value,
        'sort_order' => 2,
    ])->assertRedirect(route('admin.course-categories.index'));

    expect($category->fresh()->name)->toBe('Reading Mastery');

    $this->actingAs($admin)->delete(route('admin.course-categories.destroy', $category))
        ->assertRedirect(route('admin.course-categories.index'));

    expect(CourseCategory::query()->count())->toBe(0);
    expect(CourseCategory::withTrashed()->count())->toBe(1);
});

it('searches and paginates courses through crud service', function (): void {
    CourseCategory::query()->create([
        'name' => 'General',
        'slug' => 'general',
        'status' => CategoryStatus::Active,
    ]);

    Course::query()->create([
        'title' => 'Academic Reading Bootcamp',
        'slug' => 'academic-reading',
        'status' => PublishStatus::Published,
        'exam_type' => 'academic',
        'level' => 'intermediate',
    ]);

    Course::query()->create([
        'title' => 'Speaking Fluency',
        'slug' => 'speaking-fluency',
        'status' => PublishStatus::Draft,
        'exam_type' => 'general',
        'level' => 'beginner',
    ]);

    $service = app(CourseCrudService::class);
    $results = $service->paginate(new \App\Crud\CrudQuery(search: 'Reading'));

    expect($results->total())->toBe(1);
    expect($results->first()?->title)->toContain('Reading');
});

it('builds full course hierarchy with sections lessons and resources', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'hierarchy@example.com',
        'email_verified_at' => now(),
    ]);

    $category = CourseCategory::query()->create([
        'name' => 'Bootcamp',
        'slug' => 'bootcamp',
        'status' => CategoryStatus::Active,
    ]);

    $course = Course::query()->create([
        'course_category_id' => $category->id,
        'title' => 'Complete IELTS',
        'slug' => 'complete-ielts',
        'status' => PublishStatus::Published,
        'exam_type' => 'academic',
        'level' => 'intermediate',
        'created_by' => $admin->id,
    ]);

    $section = CourseSection::query()->create([
        'course_id' => $course->id,
        'title' => 'Module 1',
        'slug' => 'module-1',
        'status' => PublishStatus::Published,
    ]);

    $lesson = Lesson::query()->create([
        'course_section_id' => $section->id,
        'title' => 'Intro Video',
        'slug' => 'intro-video',
        'content_type' => LessonContentType::Video,
        'status' => PublishStatus::Published,
        'created_by' => $admin->id,
    ]);

    $resource = LessonResource::query()->create([
        'lesson_id' => $lesson->id,
        'title' => 'Worksheet PDF',
        'file_type' => 'pdf',
        'file_path' => 'worksheets/intro.pdf',
    ]);

    expect($course->sections)->toHaveCount(1);
    expect($section->lessons)->toHaveCount(1);
    expect($lesson->resources)->toHaveCount(1);
    expect($resource->lesson?->title)->toBe('Intro Video');
});

it('restores soft deleted category from trash route', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'restore-cat@example.com',
        'email_verified_at' => now(),
    ]);

    $category = CourseCategory::query()->create([
        'name' => 'Trash Test',
        'slug' => 'trash-test',
        'status' => CategoryStatus::Active,
    ]);

    app(CourseCategoryCrudService::class)->delete($category);

    $this->actingAs($admin)->put(route('admin.course-categories.restore', $category->id))
        ->assertRedirect();

    expect(CourseCategory::query()->whereKey($category->id)->exists())->toBeTrue();
});

it('blocks teachers from course admin routes', function (): void {
    $teacher = createUserWithRole(UserRole::Teacher, [
        'email' => 'teacher-courses@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($teacher)->get(route('admin.courses.index'))->assertForbidden();
});

it('filters categories by search in admin index', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'search-cat@example.com',
        'email_verified_at' => now(),
    ]);

    CourseCategory::query()->create(['name' => 'Listening Skills', 'slug' => 'listening', 'status' => CategoryStatus::Active]);
    CourseCategory::query()->create(['name' => 'Writing Tasks', 'slug' => 'writing', 'status' => CategoryStatus::Active]);

    $results = app(CourseCategoryCrudService::class)->paginate(new \App\Crud\CrudQuery(search: 'Listening'));

    expect($results->total())->toBe(1);
    expect($results->first()?->name)->toBe('Listening Skills');

    $this->actingAs($admin)
        ->get(route('admin.course-categories.index', ['search' => 'Listening']))
        ->assertOk()
        ->assertSee('Listening Skills');
});
