<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\PassageStatus;
use App\Enums\Exam\ReadingQuestionTicketStatus;
use App\Enums\Exam\TestAttemptStatus;
use App\Models\ReadingAttempt;
use App\Models\ReadingCorrectAnswer;
use App\Models\ReadingHighlight;
use App\Models\ReadingNote;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingQuestionTicket;
use App\Models\ReadingTest;
use App\Models\User;

beforeEach(function (): void {
    seedRbac();
    test()->withoutVite();
});

function experienceStudent(): User
{
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'reading-exp-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    assignStudentPackage($student, createDemoPackage([
        'slug' => 'reading-exp-package-'.uniqid(),
        'module_access' => [IeltsModule::Reading->value],
        'is_public' => true,
        'is_active' => true,
    ]));

    return $student;
}

function createExperienceReadingTest(): ReadingTest
{
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);

    $test = ReadingTest::query()->create([
        'title' => 'Experience Test',
        'slug' => 'experience-'.uniqid(),
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
        'title' => 'Passage One',
        'start_question' => 1,
        'end_question' => 2,
        'content_html' => '<p>Urban transport systems are evolving rapidly.</p>',
        'status' => PassageStatus::Published,
        'sort_order' => 1,
    ]);

    foreach ([1 => 'TRUE', 2 => 'FALSE'] as $number => $answer) {
        $group = ReadingQuestionGroup::query()->create([
            'passage_id' => $passage->id,
            'question_type' => OfficialReadingQuestionType::TrueFalseNotGiven,
            'title' => "Q{$number}",
            'start_question' => $number,
            'end_question' => $number,
            'sort_order' => $number,
            'status' => PassageStatus::Published,
        ]);

        $question = $group->questions()->create([
            'question_number' => $number,
            'prompt' => "Prompt {$number}",
            'explanation' => "Because paragraph {$number} states it clearly.",
            'reference_start_offset' => 0,
            'reference_end_offset' => 12,
            'reference_paragraph' => 'A',
            'marks' => 1,
            'sort_order' => 1,
        ]);

        ReadingCorrectAnswer::query()->create([
            'question_id' => $question->id,
            'answer' => $answer,
        ]);
    }

    return $test->fresh();
}

function startExperienceAttempt(User $student, ReadingTest $test): ReadingAttempt
{
    test()->actingAs($student)->get(route('reading-tests.start', $test))->assertOk();

    return ReadingAttempt::query()
        ->where('user_id', $student->id)
        ->where('reading_test_id', $test->id)
        ->where('status', TestAttemptStatus::InProgress)
        ->firstOrFail();
}

it('stores and lists passage highlights for an attempt', function (): void {
    $student = experienceStudent();
    $test = createExperienceReadingTest();
    $attempt = startExperienceAttempt($student, $test);
    $passage = $test->passages->first();

    $this->actingAs($student)->postJson(route('reading-attempts.highlights.store', $attempt), [
        'passage_id' => $passage->id,
        'selected_text' => 'urban transport',
        'start_offset' => 0,
        'end_offset' => 15,
        'highlight_color' => 'yellow',
    ])->assertCreated();

    expect(ReadingHighlight::query()->where('attempt_id', $attempt->id)->count())->toBe(1);

    $this->actingAs($student)
        ->getJson(route('reading-attempts.highlights.index', $attempt))
        ->assertOk()
        ->assertJsonPath('data.0.selected_text', 'urban transport');
});

it('autosaves reading notes for an attempt', function (): void {
    $student = experienceStudent();
    $test = createExperienceReadingTest();
    $attempt = startExperienceAttempt($student, $test);
    $question = ReadingQuestion::query()->where('question_number', 1)->firstOrFail();
    $passage = $test->passages->first();

    $this->actingAs($student)->postJson(route('reading-attempts.notes.store', $attempt), [
        'title' => 'TFNG tip',
        'content' => 'Watch for qualifiers like always and never.',
        'question_id' => $question->id,
        'passage_id' => $passage->id,
        'selected_text' => 'Urban transport',
        'start_offset' => 0,
        'end_offset' => 14,
    ])->assertCreated()
        ->assertJsonPath('data.start_offset', 0)
        ->assertJsonPath('data.end_offset', 14);

    expect(ReadingNote::query()->where('attempt_id', $attempt->id)->count())->toBe(1);
});

it('creates a student question ticket', function (): void {
    $student = experienceStudent();
    $test = createExperienceReadingTest();
    $attempt = startExperienceAttempt($student, $test);
    $question = ReadingQuestion::query()->where('question_number', 1)->firstOrFail();

    $this->actingAs($student)->postJson(route('reading-attempts.tickets.store', $attempt), [
        'question_id' => $question->id,
        'issue_type' => 'wrong_answer',
        'message' => 'The keyed answer does not match the passage.',
    ])->assertCreated();

    expect(ReadingQuestionTicket::query()->count())->toBe(1);
});

it('shows admin reading tickets and resolves them', function (): void {
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);
    $student = experienceStudent();
    $test = createExperienceReadingTest();
    $attempt = startExperienceAttempt($student, $test);
    $question = ReadingQuestion::query()->where('question_number', 1)->firstOrFail();

    ReadingQuestionTicket::query()->create([
        'reading_test_id' => $test->id,
        'attempt_id' => $attempt->id,
        'question_id' => $question->id,
        'question_number' => $question->question_number,
        'user_id' => $student->id,
        'issue_type' => 'typo',
        'message' => 'Spelling mistake in prompt.',
        'status' => ReadingQuestionTicketStatus::Open,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.reading-tickets.index'))
        ->assertOk()
        ->assertSee('Typo');

    $ticket = ReadingQuestionTicket::query()->firstOrFail();

    $this->actingAs($admin)
        ->post(route('admin.reading-tickets.resolve', $ticket))
        ->assertRedirect();

    expect($ticket->fresh()->status)->toBe(ReadingQuestionTicketStatus::Resolved);
});

it('includes analytics on the result page', function (): void {
    $student = experienceStudent();
    $test = createExperienceReadingTest();
    $attempt = startExperienceAttempt($student, $test);

    $this->actingAs($student)
        ->postJson(route('reading-attempts.submit', $attempt))
        ->assertOk();

    $this->actingAs($student)
        ->get(route('reading-attempts.result', $attempt))
        ->assertOk()
        ->assertSee('Question Map')
        ->assertSee('Reading Insights');
});
