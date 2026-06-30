<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningAnswerStatus;
use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Enums\Listening\ListeningSectionType;
use App\Enums\Listening\ListeningTestStatus;
use App\Enums\Listening\ListeningTestType;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Services\Listening\Student\ListeningAutoSaveService;
use App\Services\Listening\Student\ListeningMultipleAnswerCountingService;
use App\Services\Listening\Student\ListeningQuestionPaletteService;

beforeEach(function (): void {
    seedRbac();
});

function maCountingFixture(): array
{
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);

    $test = ListeningTest::query()->create([
        'title' => 'Multiple Answer Counting '.uniqid(),
        'slug' => 'ma-count-'.uniqid(),
        'test_code' => 'LST-MA-'.strtoupper(uniqid()),
        'test_type' => ListeningTestType::Academic,
        'status' => ListeningTestStatus::Published,
        'is_active' => true,
        'duration_minutes' => 30,
        'total_sections' => 4,
        'total_questions' => 10,
        'published_at' => now(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $section = ListeningSection::query()->create([
        'listening_test_id' => $test->id,
        'section_number' => 3,
        'title' => 'Part 3',
        'section_type' => ListeningSectionType::Conversation,
        'start_question_number' => 21,
        'end_question_number' => 30,
        'total_questions' => 10,
        'display_order' => 3,
        'is_active' => true,
    ]);

    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Questions 21-22',
        'question_type' => ListeningQuestionType::MultipleAnswer,
        'start_question_number' => 21,
        'end_question_number' => 22,
        'total_questions' => 2,
        'display_order' => 1,
        'layout_type' => ListeningLayoutType::Default,
        'options' => [
            ['key' => 'A', 'text' => 'One'],
            ['key' => 'B', 'text' => 'Two'],
            ['key' => 'C', 'text' => 'Three'],
        ],
        'settings' => ['required_answers' => 2],
        'is_active' => true,
    ]);

    $question21 = ListeningQuestion::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'listening_question_group_id' => $group->id,
        'question_number' => 21,
        'question_type' => ListeningQuestionType::MultipleAnswer,
        'question_text' => 'Which TWO features had impact?',
        'answer_format' => ListeningAnswerFormat::Multiple,
        'display_order' => 21,
        'is_active' => true,
    ]);

    $question22 = ListeningQuestion::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'listening_question_group_id' => $group->id,
        'question_number' => 22,
        'question_type' => ListeningQuestionType::MultipleAnswer,
        'answer_format' => ListeningAnswerFormat::Multiple,
        'display_order' => 22,
        'is_active' => true,
    ]);

    $student = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);

    $attempt = ListeningAttempt::query()->create([
        'user_id' => $student->id,
        'listening_test_id' => $test->id,
        'status' => \App\Enums\Listening\ListeningAttemptStatus::InProgress,
        'total_questions' => 10,
        'total_answered' => 0,
        'current_section_number' => 3,
        'current_question_number' => 21,
        'started_at' => now(),
    ]);

    foreach ([$question21, $question22] as $question) {
        ListeningAttemptAnswer::query()->create([
            'listening_attempt_id' => $attempt->id,
            'listening_test_id' => $test->id,
            'listening_question_id' => $question->id,
            'question_number' => $question->question_number,
            'answer_status' => ListeningAnswerStatus::Unanswered,
        ]);
    }

    return compact('question21', 'question22', 'attempt');
}

it('counts zero answered slots when only one letter is selected for a choose two group', function (): void {
    ['question21' => $question21, 'attempt' => $attempt] = maCountingFixture();

    ListeningAttemptAnswer::query()
        ->where('listening_attempt_id', $attempt->id)
        ->where('listening_question_id', $question21->id)
        ->update([
            'student_answer' => [
                ['value' => 'A', 'type' => 'letter'],
            ],
            'normalized_answer' => [
                ['value' => 'A', 'type' => 'letter'],
            ],
            'answer_status' => ListeningAnswerStatus::Unanswered,
        ]);

    $palette = app(ListeningQuestionPaletteService::class)->build($attempt->fresh());

    expect(app(ListeningMultipleAnswerCountingService::class)->countAnsweredQuestions($attempt->fresh()))->toBe(0)
        ->and(collect($palette)->firstWhere('question_number', 21)['is_answered'])->toBeFalse()
        ->and(collect($palette)->firstWhere('question_number', 22)['is_answered'])->toBeFalse();
});

it('counts two answered slots when two letters are selected for a choose two group', function (): void {
    ['question21' => $question21, 'attempt' => $attempt] = maCountingFixture();

    ListeningAttemptAnswer::query()
        ->where('listening_attempt_id', $attempt->id)
        ->where('listening_question_id', $question21->id)
        ->update([
            'student_answer' => [
                ['value' => 'A', 'type' => 'letter'],
                ['value' => 'E', 'type' => 'letter'],
            ],
            'normalized_answer' => [
                ['value' => 'A', 'type' => 'letter'],
                ['value' => 'E', 'type' => 'letter'],
            ],
            'answer_status' => ListeningAnswerStatus::Answered,
        ]);

    $palette = app(ListeningQuestionPaletteService::class)->build($attempt->fresh());

    expect(app(ListeningMultipleAnswerCountingService::class)->countAnsweredQuestions($attempt->fresh()))->toBe(2)
        ->and(collect($palette)->firstWhere('question_number', 21)['is_answered'])->toBeTrue()
        ->and(collect($palette)->firstWhere('question_number', 22)['is_answered'])->toBeTrue();
});

it('syncs sibling question rows when autosave completes a choose two group', function (): void {
    ['question21' => $question21, 'question22' => $question22, 'attempt' => $attempt] = maCountingFixture();

    app(ListeningAutoSaveService::class)->saveAnswer(
        $attempt,
        $question21,
        [
            ['value' => 'A', 'type' => 'letter'],
            ['value' => 'C', 'type' => 'letter'],
        ],
    );

    $attempt->refresh();
    $answer21 = ListeningAttemptAnswer::query()->where('listening_question_id', $question21->id)->first();
    $answer22 = ListeningAttemptAnswer::query()->where('listening_question_id', $question22->id)->first();

    expect($attempt->total_answered)->toBe(2)
        ->and($answer21?->answer_status)->toBe(ListeningAnswerStatus::Answered)
        ->and($answer22?->answer_status)->toBe(ListeningAnswerStatus::Answered)
        ->and($answer21?->student_answer)->toHaveCount(2);
});

it('keeps partial multiple answer selections unanswered in autosave', function (): void {
    ['question21' => $question21, 'question22' => $question22, 'attempt' => $attempt] = maCountingFixture();

    app(ListeningAutoSaveService::class)->saveAnswer(
        $attempt,
        $question21,
        [
            ['value' => 'A', 'type' => 'letter'],
        ],
    );

    $attempt->refresh();

    expect($attempt->total_answered)->toBe(0)
        ->and(ListeningAttemptAnswer::query()->where('listening_question_id', $question21->id)->value('answer_status'))
        ->toBe(ListeningAnswerStatus::Unanswered)
        ->and(ListeningAttemptAnswer::query()->where('listening_question_id', $question22->id)->value('answer_status'))
        ->toBe(ListeningAnswerStatus::Unanswered);
});

it('expands palette to every official question number in a multiple answer group range', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);

    $test = ListeningTest::query()->create([
        'title' => 'MA Range Palette '.uniqid(),
        'slug' => 'ma-range-'.uniqid(),
        'test_code' => 'LST-RNG-'.strtoupper(uniqid()),
        'test_type' => ListeningTestType::Academic,
        'status' => ListeningTestStatus::Published,
        'is_active' => true,
        'duration_minutes' => 30,
        'total_sections' => 4,
        'total_questions' => 10,
        'published_at' => now(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $section = ListeningSection::query()->create([
        'listening_test_id' => $test->id,
        'section_number' => 3,
        'title' => 'Part 3',
        'section_type' => ListeningSectionType::Conversation,
        'start_question_number' => 21,
        'end_question_number' => 30,
        'total_questions' => 10,
        'display_order' => 3,
        'is_active' => true,
    ]);

    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Questions 21-22',
        'question_type' => ListeningQuestionType::MultipleAnswer,
        'start_question_number' => 21,
        'end_question_number' => 22,
        'total_questions' => 2,
        'display_order' => 1,
        'layout_type' => ListeningLayoutType::Default,
        'options' => [
            ['key' => 'A', 'text' => 'One'],
            ['key' => 'B', 'text' => 'Two'],
        ],
        'settings' => ['required_answers' => 2],
        'is_active' => true,
    ]);

    $question21 = ListeningQuestion::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'listening_question_group_id' => $group->id,
        'question_number' => 21,
        'question_type' => ListeningQuestionType::MultipleAnswer,
        'question_text' => 'Which TWO features had impact?',
        'answer_format' => ListeningAnswerFormat::Multiple,
        'display_order' => 21,
        'is_active' => true,
    ]);

    $student = createUserWithRole(UserRole::Student, ['email_verified_at' => now()]);

    $attempt = ListeningAttempt::query()->create([
        'user_id' => $student->id,
        'listening_test_id' => $test->id,
        'status' => \App\Enums\Listening\ListeningAttemptStatus::InProgress,
        'total_questions' => 10,
        'total_answered' => 0,
        'current_section_number' => 3,
        'current_question_number' => 21,
        'started_at' => now(),
    ]);

    ListeningAttemptAnswer::query()->create([
        'listening_attempt_id' => $attempt->id,
        'listening_test_id' => $test->id,
        'listening_question_id' => $question21->id,
        'question_number' => 21,
        'student_answer' => [
            ['value' => 'A', 'type' => 'letter'],
            ['value' => 'B', 'type' => 'letter'],
        ],
        'normalized_answer' => [
            ['value' => 'A', 'type' => 'letter'],
            ['value' => 'B', 'type' => 'letter'],
        ],
        'answer_status' => ListeningAnswerStatus::Answered,
    ]);

    $palette = app(ListeningQuestionPaletteService::class)->build($attempt->fresh());
    $numbers = collect($palette)->pluck('question_number')->all();

    expect($numbers)->toContain(21, 22)
        ->and(collect($palette)->firstWhere('question_number', 21)['is_answered'])->toBeTrue()
        ->and(collect($palette)->firstWhere('question_number', 22)['is_answered'])->toBeTrue()
        ->and(app(ListeningMultipleAnswerCountingService::class)->countAnsweredQuestions($attempt->fresh()))->toBe(2);
});
