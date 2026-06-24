<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Commerce\BillingInterval;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Commerce\PackageDiscountType;
use App\Enums\Commerce\PackageStatus;
use App\Enums\Course\CategoryStatus;
use App\Enums\Course\CourseLevel;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\ReadingQuestionType;
use App\Enums\Exam\TestAttemptStatus;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseEnrollment;
use App\Models\ExamTest;
use App\Models\Package;
use App\Models\Question;
use App\Models\ReadingAnalytics;
use App\Models\Result;
use App\Models\StudentPackage;
use App\Models\TestAttempt;
use App\Models\User;
use App\Services\Admin\Exam\ReadingTestBuilderService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

beforeEach(function (): void {
    seedRbac();
});

/**
 * @return array<string, mixed>
 */
function fortyQuestionReadingImportPayload(string $slug): array
{
    $passageSizes = [14, 13, 13];
    $passages = [];
    $questionNumber = 1;

    foreach ($passageSizes as $index => $size) {
        $questions = [];

        for ($i = 0; $i < $size; $i++) {
            $questions[] = [
                'type' => ReadingQuestionType::TrueFalseNg->value,
                'question_number' => $questionNumber,
                'prompt' => "Statement for question {$questionNumber}",
                'correct_answer' => 'True',
                'marks' => 1,
                'sort_order' => $questionNumber,
            ];
            $questionNumber++;
        }

        $passages[] = [
            'title' => 'Passage '.($index + 1),
            'sort_order' => $index + 1,
            'instructions' => 'Answer questions for passage '.($index + 1),
            'stimulus_text' => 'Reading passage '.($index + 1).' content for the full-flow integration test.',
            'questions' => $questions,
        ];
    }

    return [
        'version' => 1,
        'test' => [
            'title' => 'Full Flow Reading Test',
            'slug' => $slug,
            'exam_type' => ExamType::Academic->value,
            'duration_seconds' => 3600,
            'status' => PublishStatus::Published->value,
        ],
        'passages' => $passages,
    ];
}

it('completes admin course package reading test and student enrollment flow', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, [
        'email' => 'flow-admin@example.com',
        'email_verified_at' => now(),
    ]);

    $this->actingAs($admin)->post(route('admin.course-categories.store'), [
        'name' => 'IELTS Reading Prep',
        'slug' => 'ielts-reading-prep-flow',
        'status' => CategoryStatus::Active->value,
        'sort_order' => 1,
    ])->assertRedirect(route('admin.course-categories.index'));

    $category = CourseCategory::query()->where('slug', 'ielts-reading-prep-flow')->firstOrFail();

    $this->actingAs($admin)->post(route('admin.courses.store'), [
        'title' => 'Reading Mastery Course',
        'slug' => 'reading-mastery-flow',
        'course_category_id' => $category->id,
        'description' => 'Course for full reading flow test',
        'exam_type' => ExamType::Academic->value,
        'level' => CourseLevel::Intermediate->value,
        'status' => PublishStatus::Published->value,
        'published_at' => now()->toDateString(),
    ])->assertRedirect(route('admin.courses.index'));

    $course = Course::query()->where('slug', 'reading-mastery-flow')->firstOrFail();

    $this->actingAs($admin)->post(route('admin.packages.store'), [
        'name' => 'Reading Flow Package',
        'slug' => 'reading-flow-package',
        'description' => 'Package for end-to-end reading flow',
        'module_access' => [IeltsModule::Reading->value],
        'attempt_limits' => ['reading' => 20],
        'billing_interval' => BillingInterval::Monthly->value,
        'price' => 999,
        'currency' => 'BDT',
        'discount_type' => PackageDiscountType::None->value,
        'discount_value' => 0,
        'duration_days' => 30,
        'trial_days' => 0,
        'is_active' => true,
        'is_public' => true,
        'sort_order' => 1,
        'status' => PackageStatus::Active->value,
        'course_ids' => [$course->id],
    ])->assertRedirect(route('admin.packages.index'));

    $package = Package::query()->where('slug', 'reading-flow-package')->firstOrFail();
    expect($package->courses()->where('courses.id', $course->id)->exists())->toBeTrue();

    $this->actingAs($admin)->post(route('admin.reading-tests.store'), [
        'title' => 'Full Flow Reading Test',
        'slug' => 'full-flow-reading-test',
        'description' => 'Three passages and forty questions',
        'exam_type' => ExamType::Academic->value,
        'duration_seconds' => 3600,
        'is_timed' => true,
        'status' => PublishStatus::Draft->value,
    ])->assertRedirect();

    $readingTest = ExamTest::query()->where('slug', 'full-flow-reading-test')->firstOrFail();

    $importPayload = fortyQuestionReadingImportPayload($readingTest->slug);
    $jsonFile = UploadedFile::fake()->createWithContent(
        'reading-import.json',
        json_encode($importPayload, JSON_THROW_ON_ERROR)
    );

    $this->actingAs($admin)->post(route('admin.reading-tests.import-json', $readingTest), [
        'file' => $jsonFile,
    ])->assertRedirect(route('admin.reading-tests.builder', $readingTest));

    expect($readingTest->testQuestions()->count())->toBe(40);
    expect($readingTest->sections()->count())->toBe(3);

    $this->actingAs($admin)->put(route('admin.reading-tests.update', $readingTest), [
        'title' => 'Full Flow Reading Test',
        'slug' => 'full-flow-reading-test',
        'description' => 'Three passages and forty questions',
        'exam_type' => ExamType::Academic->value,
        'duration_seconds' => 3600,
        'is_timed' => true,
        'status' => PublishStatus::Published->value,
        'published_at' => now()->toDateString(),
    ])->assertRedirect(route('admin.reading-tests.index'));

    expect($readingTest->fresh()->status)->toBe(PublishStatus::Published);

    $studentEmail = 'flow-student-'.Str::random(6).'@example.com';

    $this->post(route('logout'));

    $this->post(route('register.store'), [
        'name' => 'Flow Student',
        'email' => $studentEmail,
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertRedirect(route('verification.notice'));

    $student = User::query()->where('email', $studentEmail)->firstOrFail();
    $student->forceFill(['email_verified_at' => now()])->save();

    $this->actingAs($student)->post(route('student.packages.enroll', $package))
        ->assertRedirect(route('student.packages.index'));

    expect(StudentPackage::query()->where('user_id', $student->id)->where('package_id', $package->id)->exists())->toBeTrue();
    expect(CourseEnrollment::query()->where('user_id', $student->id)->where('course_id', $course->id)->exists())->toBeTrue();

    $this->actingAs($student)
        ->get(route('exam.reading.show', $readingTest))
        ->assertOk()
        ->assertSee('Full Flow Reading Test')
        ->assertSee('Submit');

    $attempt = TestAttempt::query()->where('user_id', $student->id)->firstOrFail();
    expect($attempt->status)->toBe(TestAttemptStatus::InProgress);

    $questions = Question::query()
        ->whereIn('id', $readingTest->testQuestions()->pluck('question_id'))
        ->orderBy('question_number')
        ->get();

    $answers = $questions->map(fn (Question $question): array => [
        'question_id' => $question->id,
        'answer_text' => 'True',
        'is_flagged' => $question->question_number === 1,
    ])->all();

    $submitResponse = $this->actingAs($student)->postJson(route('exam.reading.submit', $attempt), [
        'answers' => $answers,
        'question_timings' => $questions->map(fn (Question $question): array => [
            'question_id' => $question->id,
            'time_spent_seconds' => 30,
            'visit_count' => 1,
        ])->all(),
    ]);

    $submitResponse->assertOk()
        ->assertJsonPath('data.raw_score', 40)
        ->assertJsonPath('data.max_score', 40);

    $result = Result::query()->where('test_attempt_id', $attempt->id)->firstOrFail();

    $this->actingAs($student)
        ->get(route('exam.reading.results', $result))
        ->assertOk()
        ->assertSee('Overall Band')
        ->assertSee('Question-by-Question Report')
        ->assertSee('Performance Heat Map');

    expect($attempt->fresh()->status)->toBe(TestAttemptStatus::Completed);
    expect(ReadingAnalytics::query()->where('test_attempt_id', $attempt->id)->exists())->toBeTrue();
    expect((float) $result->fresh()->overall_band)->toBe(9.0);
});

it('submits reading test from player with redirect to results page', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);
    $slug = 'player-submit-'.uniqid();

    $test = app(ReadingTestBuilderService::class)->importTest(
        fortyQuestionReadingImportPayload($slug),
        $admin
    );
    $test->update(['status' => PublishStatus::Published, 'published_at' => now()]);

    $student = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);
    assignStudentPackage($student, createDemoPackage([
        'slug' => 'player-submit-package-'.uniqid(),
        'module_access' => [IeltsModule::Reading->value],
        'is_public' => true,
        'is_active' => true,
    ]));

    $this->actingAs($student)->get(route('exam.reading.show', $test))->assertOk()->assertSee('Submit');

    $attempt = TestAttempt::query()->where('user_id', $student->id)->firstOrFail();
    $question = Question::query()->where('question_number', 1)->firstOrFail();

    $response = $this->actingAs($student)->postJson(route('exam.reading.submit', $attempt), [
        'answers' => [[
            'question_id' => $question->id,
            'answer_text' => 'True',
        ]],
    ]);

    $response->assertOk();
    $result = Result::query()->where('test_attempt_id', $attempt->id)->firstOrFail();

    expect($response->json('data.redirect_url'))->toBe(route('exam.reading.results', $result));

    $this->actingAs($student)
        ->get(route('exam.reading.results', $result))
        ->assertOk()
        ->assertSee('Results');
});

it('blocks students from viewing another students reading results', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);
    $test = app(ReadingTestBuilderService::class)->importTest(
        fortyQuestionReadingImportPayload('results-auth-'.uniqid()),
        $admin
    );
    $test->update(['status' => PublishStatus::Published, 'published_at' => now()]);

    $owner = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);
    $other = createUserWithRole(UserRole::Student, [
        'email' => 'other-student-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    assignStudentPackage($owner, createDemoPackage([
        'slug' => 'owner-package-'.uniqid(),
        'module_access' => [IeltsModule::Reading->value],
        'is_public' => true,
        'is_active' => true,
    ]));

    $this->actingAs($owner)->get(route('exam.reading.show', $test));
    $attempt = TestAttempt::query()->where('user_id', $owner->id)->firstOrFail();
    $question = Question::query()->firstOrFail();

    $this->actingAs($owner)->postJson(route('exam.reading.submit', $attempt), [
        'answers' => [['question_id' => $question->id, 'answer_text' => 'True']],
    ]);

    $result = Result::query()->where('test_attempt_id', $attempt->id)->firstOrFail();

    $this->actingAs($other)->get(route('exam.reading.results', $result))->assertForbidden();
});
