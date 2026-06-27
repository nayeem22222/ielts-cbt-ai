<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\PassageStatus;
use App\Enums\Exam\TestAttemptStatus;
use App\Models\ReadingAttempt;
use App\Models\ReadingCorrectAnswer;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingTest;
use App\Models\User;
use App\Services\Admin\Exam\ReadingPassageBuilderService;
use App\Support\Reading\ReadingHtmlSanitizer;

beforeEach(function (): void {
    seedRbac();
    test()->withoutVite();
});

function perfSecurityStudent(): User
{
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'reading-perf-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    assignStudentPackage($student, createDemoPackage([
        'slug' => 'reading-perf-package-'.uniqid(),
        'module_access' => [IeltsModule::Reading->value],
        'is_public' => true,
        'is_active' => true,
    ]));

    return $student;
}

function createPerfSecurityReadingTest(string $slug = 'perf-security-reading'): ReadingTest
{
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);

    $test = ReadingTest::query()->create([
        'title' => 'Performance Security Test',
        'slug' => $slug,
        'exam_type' => ExamType::Academic,
        'duration_minutes' => 60,
        'status' => PublishStatus::Published,
        'published_at' => now(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $passage = ReadingPassage::query()->create([
        'reading_test_id' => $test->id,
        'part_number' => 1,
        'title' => 'Secure Passage',
        'start_question' => 1,
        'end_question' => 2,
        'content_html' => '<p>Safe passage content</p>',
        'status' => PassageStatus::Published,
        'sort_order' => 1,
    ]);

    $group = ReadingQuestionGroup::query()->create([
        'passage_id' => $passage->id,
        'title' => 'Questions 1-2',
        'question_type' => OfficialReadingQuestionType::TrueFalseNotGiven,
        'start_question' => 1,
        'end_question' => 2,
        'sort_order' => 1,
        'status' => PassageStatus::Published,
    ]);

    foreach ([1, 2] as $number) {
        $question = $group->questions()->create([
            'question_number' => $number,
            'prompt' => "Statement {$number}",
            'marks' => 1,
            'sort_order' => $number,
        ]);

        ReadingCorrectAnswer::query()->create([
            'question_id' => $question->id,
            'answer' => 'TRUE',
        ]);
    }

    return $test->fresh();
}

it('does not expose correct answers in student renderer output', function (): void {
    $student = perfSecurityStudent();
    $test = createPerfSecurityReadingTest();

    $response = $this->actingAs($student)->get(route('reading-tests.start', $test));

    $response->assertOk();
    expect($response->getContent())->not->toContain('reading_correct_answers');
    expect($response->getContent())->not->toContain('"correct_answer"');
    expect($response->getContent())->not->toContain('answer_json');
});

it('returns compact timer payload without heavy fields', function (): void {
    $student = perfSecurityStudent();
    $test = createPerfSecurityReadingTest();

    $this->actingAs($student)->get(route('reading-tests.start', $test))->assertOk();

    $attempt = ReadingAttempt::query()->where('user_id', $student->id)->firstOrFail();

    $response = $this->actingAs($student)->getJson(route('reading-attempts.timer', $attempt));

    $response->assertOk()
        ->assertJsonStructure([
            'data' => ['remaining_seconds', 'status', 'server_time'],
        ])
        ->assertJsonMissingPath('data.ends_at')
        ->assertJsonMissingPath('data.duration_seconds');
});

it('does not return evaluation payload on submit', function (): void {
    $student = perfSecurityStudent();
    $test = createPerfSecurityReadingTest();

    $this->actingAs($student)->get(route('reading-tests.start', $test))->assertOk();
    $attempt = ReadingAttempt::query()->where('user_id', $student->id)->firstOrFail();

    $response = $this->actingAs($student)->postJson(route('reading-attempts.submit', $attempt));

    $response->assertOk()
        ->assertJsonPath('data.redirect_url', route('reading-attempts.result', $attempt))
        ->assertJsonMissingPath('data.evaluation')
        ->assertJsonMissingPath('data.evaluation.questions');
});

it('blocks saving answers after submit', function (): void {
    $student = perfSecurityStudent();
    $test = createPerfSecurityReadingTest();

    $this->actingAs($student)->get(route('reading-tests.start', $test))->assertOk();
    $attempt = ReadingAttempt::query()->where('user_id', $student->id)->firstOrFail();
    $question = $test->questions()->firstOrFail();

    $attempt->forceFill(['status' => TestAttemptStatus::Submitted, 'submitted_at' => now()])->save();

    $this->actingAs($student)->postJson(route('reading-attempts.answers.store', $attempt), [
        'question_id' => $question->id,
        'question_number' => $question->question_number,
        'question_type' => OfficialReadingQuestionType::TrueFalseNotGiven->value,
        'passage_id' => $question->group->passage_id,
        'group_id' => $question->group_id,
        'answer' => 'TRUE',
    ])->assertForbidden();
});

it('registers reading throttle middleware on high traffic endpoints', function (): void {
    $autosave = app('router')->getRoutes()->getByName('reading-attempts.answers.store');
    $timer = app('router')->getRoutes()->getByName('reading-attempts.timer');
    $submit = app('router')->getRoutes()->getByName('reading-attempts.submit');

    expect(collect($autosave->gatherMiddleware()))->toContain('throttle:reading-autosave');
    expect(collect($timer->gatherMiddleware()))->toContain('throttle:reading-timer');
    expect(collect($submit->gatherMiddleware()))->toContain('throttle:reading-submit');
});

it('sanitizes dangerous passage html on save', function (): void {
    $test = createPerfSecurityReadingTest('xss-passage-test');
    $passage = $test->passages()->firstOrFail();

    app(ReadingPassageBuilderService::class)->update($passage, [
        'title' => $passage->title,
        'subtitle' => null,
        'instruction' => null,
        'start_question' => 1,
        'end_question' => 2,
        'content_html' => '<p>Hello</p><script>alert(1)</script><iframe src="evil"></iframe>',
        'status' => PassageStatus::Published->value,
        'auto_paragraph_labels' => false,
        'sort_order' => 1,
    ]);

    $passage->refresh();

    expect($passage->content_html)->not->toContain('<script');
    expect($passage->content_html)->not->toContain('<iframe');
    expect(ReadingHtmlSanitizer::sanitize($passage->content_html))->toContain('Hello');
});

it('caches published reading test structure', function (): void {
    $test = createPerfSecurityReadingTest('cache-reading-test');

    $renderer = app(\App\Services\Exam\ReadingTestRendererService::class);
    $cache = app(\App\Services\Exam\ReadingTestPublicCacheService::class);

    $first = $renderer->cachedForRenderer($test);
    $second = $renderer->cachedForRenderer($test->fresh());

    expect($cache->cacheKey($test))->toContain('reading_test_public:'.$test->id.':v');
    expect($first->relationLoaded('passages'))->toBeTrue();
    expect($second->passages->count())->toBe($first->passages->count());
});
