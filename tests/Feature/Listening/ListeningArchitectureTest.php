<?php

declare(strict_types=1);

use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningAnswerStatus;
use App\Enums\Listening\ListeningAttemptStatus;
use App\Enums\Listening\ListeningConstants;
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
use App\Models\Listening\ListeningTestSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

it('runs listening migrations successfully', function (): void {
    expect(Schema::hasTable('listening_tests'))->toBeTrue()
        ->and(Schema::hasTable('listening_sections'))->toBeTrue()
        ->and(Schema::hasTable('listening_audios'))->toBeTrue()
        ->and(Schema::hasTable('listening_transcripts'))->toBeTrue()
        ->and(Schema::hasTable('listening_question_groups'))->toBeTrue()
        ->and(Schema::hasTable('listening_questions'))->toBeTrue()
        ->and(Schema::hasTable('listening_attempts'))->toBeTrue()
        ->and(Schema::hasTable('listening_attempt_answers'))->toBeTrue()
        ->and(Schema::hasTable('listening_question_markers'))->toBeTrue()
        ->and(Schema::hasTable('listening_test_settings'))->toBeTrue();
});

it('creates a listening test with relationships', function (): void {
    $test = ListeningTest::query()->create([
        'title' => 'Academic Listening QA',
        'slug' => 'academic-listening-qa',
        'test_code' => 'LST-QA-001',
        'status' => ListeningTestStatus::Draft,
        'test_type' => ListeningTestType::Academic,
        'total_sections' => ListeningConstants::TOTAL_SECTIONS,
        'total_questions' => ListeningConstants::TOTAL_QUESTIONS,
    ]);

    $section = $test->sections()->create([
        'section_number' => 1,
        'section_type' => ListeningSectionType::Conversation,
        'start_question_number' => 1,
        'end_question_number' => 10,
        'total_questions' => 10,
        'display_order' => 1,
    ]);

    $group = $test->questionGroups()->create([
        'listening_section_id' => $section->id,
        'question_type' => ListeningQuestionType::FormCompletion,
        'start_question_number' => 1,
        'end_question_number' => 5,
        'total_questions' => 5,
        'display_order' => 1,
        'layout_type' => ListeningLayoutType::Form,
    ]);

    $question = $test->questions()->create([
        'listening_section_id' => $section->id,
        'listening_question_group_id' => $group->id,
        'question_number' => 1,
        'question_type' => ListeningQuestionType::FormCompletion,
        'answer_format' => ListeningAnswerFormat::Text,
        'correct_answer' => ['answer' => 'Smith'],
        'display_order' => 1,
    ]);

    expect($test->sections)->toHaveCount(1)
        ->and($section->test->is($test))->toBeTrue()
        ->and($group->section->is($section))->toBeTrue()
        ->and($question->group->is($group))->toBeTrue();
});

it('creates attempt and answer for user', function (): void {
    $user = User::factory()->create();

    $test = ListeningTest::query()->create([
        'title' => 'Attempt Test',
        'slug' => 'attempt-test',
        'test_code' => 'LST-ATT-001',
        'status' => ListeningTestStatus::Published,
        'test_type' => ListeningTestType::Academic,
    ]);

    $section = $test->sections()->create([
        'section_number' => 1,
        'section_type' => ListeningSectionType::Conversation,
        'start_question_number' => 1,
        'end_question_number' => 10,
        'total_questions' => 10,
        'display_order' => 1,
    ]);

    $group = $test->questionGroups()->create([
        'listening_section_id' => $section->id,
        'question_type' => ListeningQuestionType::MCQ,
        'start_question_number' => 1,
        'end_question_number' => 1,
        'total_questions' => 1,
        'display_order' => 1,
        'layout_type' => ListeningLayoutType::Default,
    ]);

    $question = $test->questions()->create([
        'listening_section_id' => $section->id,
        'listening_question_group_id' => $group->id,
        'question_number' => 1,
        'question_type' => ListeningQuestionType::MCQ,
        'answer_format' => ListeningAnswerFormat::Letter,
        'correct_answer' => ['answer' => 'B'],
        'display_order' => 1,
    ]);

    $attempt = ListeningAttempt::query()->create([
        'user_id' => $user->id,
        'listening_test_id' => $test->id,
        'status' => ListeningAttemptStatus::InProgress,
        'started_at' => now(),
    ]);

    $answer = ListeningAttemptAnswer::query()->create([
        'listening_attempt_id' => $attempt->id,
        'listening_test_id' => $test->id,
        'listening_question_id' => $question->id,
        'question_number' => 1,
        'student_answer' => ['answer' => 'B'],
        'answer_status' => ListeningAnswerStatus::Answered,
        'answered_at' => now(),
    ]);

    expect($attempt->user->is($user))->toBeTrue()
        ->and($attempt->test->is($test))->toBeTrue()
        ->and($answer->attempt->is($attempt))->toBeTrue()
        ->and($answer->question->is($question))->toBeTrue();
});

it('creates listening test settings with official defaults', function (): void {
    $test = ListeningTest::query()->create([
        'title' => 'Settings Test',
        'slug' => 'settings-test',
        'test_code' => 'LST-SET-001',
        'status' => ListeningTestStatus::Draft,
        'test_type' => ListeningTestType::Academic,
    ]);

    $setting = $test->setting()->create(ListeningTestSetting::officialDefaults());

    expect($setting->allow_audio_replay)->toBeFalse()
        ->and($setting->allow_audio_seek)->toBeFalse()
        ->and($setting->auto_submit_on_timer_end)->toBeTrue()
        ->and($setting->auto_save_interval_seconds)->toBe(10);
});

it('validates listening enum values', function (): void {
    expect(ListeningQuestionType::values())->toContain('mcq', 'short_answer')
        ->and(ListeningTestStatus::Published->label())->toBe('Published')
        ->and(ListeningSectionType::Lecture->backedValue())->toBe('lecture')
        ->and(ListeningConstants::SECTION_QUESTION_RANGES[4]['end'])->toBe(40);
});

it('seeder creates missing settings for listening tests', function (): void {
    $test = ListeningTest::query()->create([
        'title' => 'Seeder Test',
        'slug' => 'seeder-test',
        'test_code' => 'LST-SED-001',
        'status' => ListeningTestStatus::Draft,
        'test_type' => ListeningTestType::Academic,
    ]);

    expect($test->setting)->toBeNull();

    $this->seed(\Database\Seeders\Listening\ListeningTestSettingSeeder::class);

    $test->refresh();

    expect($test->setting)->not->toBeNull()
        ->and($test->setting?->show_transcript_after_submit)->toBeFalse();
});
