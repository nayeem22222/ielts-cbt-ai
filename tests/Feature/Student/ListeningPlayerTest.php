<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningAttemptStatus;
use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Enums\Listening\ListeningSectionType;
use App\Enums\Listening\ListeningTestStatus;
use App\Enums\Listening\ListeningTestType;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAudio;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Models\Listening\ListeningTestSetting;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    seedRbac();
    test()->withoutVite();
    Storage::fake('public');
});

function listeningStudentWithAccess(): User
{
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'listening-player-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    assignStudentPackage($student, createDemoPackage([
        'slug' => 'listening-player-package-'.uniqid(),
        'module_access' => [IeltsModule::Listening->value],
        'is_public' => true,
        'is_active' => true,
    ]));

    return $student;
}

function createPlayableListeningTest(array $overrides = []): ListeningTest
{
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);

    $test = ListeningTest::query()->create(array_merge([
        'title' => 'Student Listening Mock '.uniqid(),
        'slug' => 'student-listening-'.uniqid(),
        'test_code' => 'LST-STU-'.strtoupper(uniqid()),
        'test_type' => ListeningTestType::Academic,
        'status' => ListeningTestStatus::Published,
        'is_active' => true,
        'duration_minutes' => 30,
        'transfer_time_minutes' => 10,
        'total_sections' => 4,
        'total_questions' => 40,
        'published_at' => now(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ], $overrides));

    ListeningTestSetting::query()->create(array_merge(
        ['listening_test_id' => $test->id],
        ListeningTestSetting::officialDefaults(),
    ));

    $ranges = [1 => [1, 10], 2 => [11, 20], 3 => [21, 30], 4 => [31, 40]];

    foreach ($ranges as $sectionNumber => [$start, $end]) {
        $path = "listening/audio/normalized/section-{$sectionNumber}-{$test->id}.mp3";
        Storage::disk('public')->put($path, 'fake-audio-content');

        $audio = ListeningAudio::factory()->completed()->create([
            'path' => $path,
            'normalized_path' => $path,
            'meta' => ['audio' => ['playable_path' => $path]],
        ]);

        $section = ListeningSection::query()->create([
            'listening_test_id' => $test->id,
            'section_number' => $sectionNumber,
            'title' => 'Section '.$sectionNumber,
            'section_type' => ListeningSectionType::Conversation,
            'audio_id' => $audio->id,
            'start_question_number' => $start,
            'end_question_number' => $end,
            'total_questions' => 10,
            'display_order' => $sectionNumber,
            'is_active' => true,
        ]);

        $lines = [];
        for ($n = $start; $n <= $end; $n++) {
            $lines[] = "Answer {$n}: [blank:{$n}]";
        }

        $group = ListeningQuestionGroup::query()->create([
            'listening_test_id' => $test->id,
            'listening_section_id' => $section->id,
            'title' => "Group {$start}-{$end}",
            'question_type' => ListeningQuestionType::FormCompletion,
            'start_question_number' => $start,
            'end_question_number' => $end,
            'total_questions' => 10,
            'display_order' => 1,
            'layout_type' => ListeningLayoutType::Form,
            'content' => implode("\n", $lines),
            'settings' => ['word_limit' => 2, 'template_type' => 'form'],
            'is_active' => true,
        ]);

        for ($n = $start; $n <= $end; $n++) {
            ListeningQuestion::query()->create([
                'listening_test_id' => $test->id,
                'listening_section_id' => $section->id,
                'listening_question_group_id' => $group->id,
                'question_number' => $n,
                'question_type' => ListeningQuestionType::FormCompletion,
                'question_text' => "Question {$n}",
                'answer_format' => ListeningAnswerFormat::Text,
                'correct_answer' => [['value' => "answer{$n}", 'type' => 'text']],
                'display_order' => $n,
                'is_active' => true,
            ]);
        }
    }

    return $test->fresh(['sections', 'questions', 'questionGroups', 'setting']);
}

it('shows published test instructions to enrolled student', function (): void {
    $test = createPlayableListeningTest();
    $student = listeningStudentWithAccess();

    $this->actingAs($student)
        ->get(route('student.listening.tests.instructions', $test))
        ->assertOk()
        ->assertSee($test->title)
        ->assertSee('Start Listening Test');
});

it('blocks draft test instructions', function (): void {
    $test = createPlayableListeningTest(['status' => ListeningTestStatus::Draft, 'published_at' => null]);
    $student = listeningStudentWithAccess();

    $this->actingAs($student)
        ->get(route('student.listening.tests.instructions', $test))
        ->assertOk()
        ->assertSee('not available');
});

it('starts attempt and creates answer rows', function (): void {
    $test = createPlayableListeningTest();
    $student = listeningStudentWithAccess();

    $this->actingAs($student)
        ->post(route('student.listening.tests.start', $test))
        ->assertRedirect();

    $attempt = ListeningAttempt::query()->where('user_id', $student->id)->first();
    expect($attempt)->not->toBeNull();
    expect($attempt->status)->toBe(ListeningAttemptStatus::InProgress);
    expect($attempt->answers()->count())->toBe(40);
});

it('resumes existing in progress attempt', function (): void {
    $test = createPlayableListeningTest();
    $student = listeningStudentWithAccess();

    $this->actingAs($student)->post(route('student.listening.tests.start', $test));
    $this->actingAs($student)->post(route('student.listening.tests.start', $test));

    expect(ListeningAttempt::query()->where('user_id', $student->id)->count())->toBe(1);
});

it('player payload excludes sensitive fields', function (): void {
    $test = createPlayableListeningTest();
    $student = listeningStudentWithAccess();
    $this->actingAs($student)->post(route('student.listening.tests.start', $test));
    $attempt = ListeningAttempt::query()->where('user_id', $student->id)->firstOrFail();

    $response = $this->actingAs($student)->get(route('student.listening.attempts.player', $attempt));
    $response->assertOk();
    $content = $response->getContent();
    expect($content)->not->toContain('correct_answer');
    expect($content)->not->toContain('transcript_text');
    expect($content)->not->toContain('accepted_answers');
});

it('saves an answer for the attempt', function (): void {
    $test = createPlayableListeningTest();
    $student = listeningStudentWithAccess();
    $this->actingAs($student)->post(route('student.listening.tests.start', $test));
    $attempt = ListeningAttempt::query()->where('user_id', $student->id)->firstOrFail();
    $question = $test->questions()->where('question_number', 1)->firstOrFail();

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.answers.save', $attempt), [
            'question_id' => $question->id,
            'student_answer' => [['value' => 'Paris', 'type' => 'text']],
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($attempt->answers()->where('listening_question_id', $question->id)->first()?->student_answer)
        ->toBe([['value' => 'Paris', 'type' => 'text']]);
});

it('bulk saves answers', function (): void {
    $test = createPlayableListeningTest();
    $student = listeningStudentWithAccess();
    $this->actingAs($student)->post(route('student.listening.tests.start', $test));
    $attempt = ListeningAttempt::query()->where('user_id', $student->id)->firstOrFail();
    $questions = $test->questions()->whereIn('question_number', [1, 2])->get();

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.answers.bulk_save', $attempt), [
            'answers' => $questions->map(fn ($q) => [
                'question_id' => $q->id,
                'student_answer' => [['value' => 'bulk', 'type' => 'text']],
            ])->all(),
        ])
        ->assertOk()
        ->assertJson(['success' => true, 'saved_count' => 2]);
});

it('rejects saving answer for question outside attempt test', function (): void {
    $testA = createPlayableListeningTest();
    $testB = createPlayableListeningTest();
    $student = listeningStudentWithAccess();
    $response = $this->actingAs($student)->post(route('student.listening.tests.start', $testA));
    $response->assertRedirect();
    $attempt = ListeningAttempt::query()
        ->where('user_id', $student->id)
        ->where('listening_test_id', $testA->id)
        ->first();
    expect($attempt)->not->toBeNull();
    $foreignQuestion = $testB->questions()->firstOrFail();

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.answers.save', $attempt), [
            'question_id' => $foreignQuestion->id,
            'student_answer' => [['value' => 'X', 'type' => 'text']],
        ])
        ->assertStatus(422);
});

it('flags a question', function (): void {
    $test = createPlayableListeningTest();
    $student = listeningStudentWithAccess();
    $this->actingAs($student)->post(route('student.listening.tests.start', $test));
    $attempt = ListeningAttempt::query()->firstOrFail();
    $question = $test->questions()->firstOrFail();

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.questions.flag', [$attempt, $question]), [
            'flagged' => true,
        ])
        ->assertOk();

    $answer = $attempt->answers()->where('listening_question_id', $question->id)->first();
    expect($answer?->meta['is_flagged'] ?? false)->toBeTrue();
});

it('submits attempt and blocks further edits', function (): void {
    $test = createPlayableListeningTest();
    $student = listeningStudentWithAccess();
    $this->actingAs($student)->post(route('student.listening.tests.start', $test));
    $attempt = ListeningAttempt::query()->firstOrFail();

    $this->actingAs($student)
        ->post(route('student.listening.attempts.submit', $attempt))
        ->assertRedirect(route('student.listening.attempts.submitted', $attempt));

    $attempt->refresh();
    expect($attempt->status)->toBe(ListeningAttemptStatus::Submitted);

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.answers.save', $attempt), [
            'question_id' => $test->questions()->first()->id,
            'student_answer' => [['value' => 'Late', 'type' => 'text']],
        ])
        ->assertForbidden();
});

it('auto submits expired attempt via middleware', function (): void {
    $test = createPlayableListeningTest();
    $student = listeningStudentWithAccess();
    $this->actingAs($student)->post(route('student.listening.tests.start', $test));
    $attempt = ListeningAttempt::query()->firstOrFail();
    $attempt->update(['expires_at' => now()->subMinute()]);

    $this->actingAs($student)
        ->get(route('student.listening.attempts.player', $attempt))
        ->assertRedirect(route('student.listening.attempts.expired', $attempt));
});

it('forbids another user from accessing attempt', function (): void {
    $test = createPlayableListeningTest();
    $owner = listeningStudentWithAccess();
    $other = listeningStudentWithAccess();
    $this->actingAs($owner)->post(route('student.listening.tests.start', $test));
    $attempt = ListeningAttempt::query()->firstOrFail();

    $this->actingAs($other)
        ->get(route('student.listening.attempts.player', $attempt))
        ->assertForbidden();
});

it('protects audio route for other users', function (): void {
    $test = createPlayableListeningTest();
    $owner = listeningStudentWithAccess();
    $other = listeningStudentWithAccess();
    $this->actingAs($owner)->post(route('student.listening.tests.start', $test));
    $attempt = ListeningAttempt::query()->firstOrFail();

    $this->actingAs($other)
        ->get(route('student.listening.attempts.audio.section', [$attempt, 1]))
        ->assertForbidden();
});

it('streams audio for valid section', function (): void {
    $test = createPlayableListeningTest();
    $student = listeningStudentWithAccess();
    $this->actingAs($student)->post(route('student.listening.tests.start', $test));
    $attempt = ListeningAttempt::query()->firstOrFail();

    $this->actingAs($student)
        ->get(route('student.listening.attempts.audio.section', [$attempt, 1]))
        ->assertOk();
});

it('rejects invalid section audio request', function (): void {
    $test = createPlayableListeningTest();
    $student = listeningStudentWithAccess();
    $this->actingAs($student)->post(route('student.listening.tests.start', $test));
    $attempt = ListeningAttempt::query()->firstOrFail();

    $this->actingAs($student)
        ->get(route('student.listening.attempts.audio.section', [$attempt, 9]))
        ->assertNotFound();
});

it('loads player with question renderers', function (): void {
    $test = createPlayableListeningTest();
    $student = listeningStudentWithAccess();
    $this->actingAs($student)->post(route('student.listening.tests.start', $test));
    $attempt = ListeningAttempt::query()->firstOrFail();

    $this->actingAs($student)
        ->get(route('student.listening.attempts.player', $attempt))
        ->assertOk()
        ->assertSee('Part 1', false)
        ->assertSee('listening-question-group', false)
        ->assertSee('listening-blank-pill', false)
        ->assertSee('listening-part-footer', false);
});

it('timer remaining is exposed in player payload', function (): void {
    $test = createPlayableListeningTest();
    $student = listeningStudentWithAccess();
    $this->actingAs($student)->post(route('student.listening.tests.start', $test));
    $attempt = ListeningAttempt::query()->firstOrFail();

    $this->actingAs($student)
        ->get(route('student.listening.attempts.player', $attempt))
        ->assertOk()
        ->assertSee('listening-official-timer', false)
        ->assertSee('listening-timer-display', false);
});

it('reading module files remain unaffected by listening player', function (): void {
    $files = glob(base_path('app/Services/Listening/Student/*.php')) ?: [];
    foreach ($files as $file) {
        $contents = file_get_contents($file) ?: '';
        expect($contents)->not->toContain('ReadingTest');
        expect($contents)->not->toContain('ReadingAttempt');
    }
});

function createPartialListeningTest(): ListeningTest
{
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);

    $test = ListeningTest::query()->create([
        'title' => 'Partial Listening Test '.uniqid(),
        'slug' => 'partial-listening-'.uniqid(),
        'test_code' => 'LST-PART-'.strtoupper(uniqid()),
        'test_type' => ListeningTestType::Academic,
        'status' => ListeningTestStatus::Published,
        'is_active' => true,
        'duration_minutes' => 30,
        'transfer_time_minutes' => 10,
        'total_sections' => 1,
        'total_questions' => 5,
        'published_at' => now(),
        'created_by' => $admin->id,
        'updated_by' => $admin->id,
    ]);

    $section = ListeningSection::query()->create([
        'listening_test_id' => $test->id,
        'section_number' => 1,
        'title' => 'Section 1',
        'section_type' => ListeningSectionType::Conversation,
        'start_question_number' => 1,
        'end_question_number' => 5,
        'total_questions' => 5,
        'display_order' => 1,
        'is_active' => true,
    ]);

    $group = ListeningQuestionGroup::query()->create([
        'listening_test_id' => $test->id,
        'listening_section_id' => $section->id,
        'title' => 'Group 1-5',
        'question_type' => ListeningQuestionType::FormCompletion,
        'start_question_number' => 1,
        'end_question_number' => 5,
        'total_questions' => 5,
        'display_order' => 1,
        'layout_type' => ListeningLayoutType::Form,
        'content' => "Answer 1: [blank:1]\nAnswer 2: [blank:2]\nAnswer 3: [blank:3]\nAnswer 4: [blank:4]\nAnswer 5: [blank:5]",
        'settings' => ['word_limit' => 2, 'template_type' => 'form'],
        'is_active' => true,
    ]);

    for ($n = 1; $n <= 5; $n++) {
        ListeningQuestion::query()->create([
            'listening_test_id' => $test->id,
            'listening_section_id' => $section->id,
            'listening_question_group_id' => $group->id,
            'question_number' => $n,
            'question_type' => ListeningQuestionType::FormCompletion,
            'question_text' => "Question {$n}",
            'answer_format' => ListeningAnswerFormat::Text,
            'correct_answer' => [['value' => "answer{$n}", 'type' => 'text']],
            'display_order' => $n,
            'is_active' => true,
        ]);
    }

    return $test->fresh(['sections', 'questions', 'questionGroups']);
}

it('allows starting a partially configured published listening test', function (): void {
    $test = createPartialListeningTest();
    $student = listeningStudentWithAccess();

    $this->actingAs($student)
        ->get(route('student.listening.tests.instructions', $test))
        ->assertOk()
        ->assertSee('Start Listening Test')
        ->assertSee('not fully configured', false);

    $this->actingAs($student)
        ->post(route('student.listening.tests.start', $test))
        ->assertRedirect();

    $attempt = ListeningAttempt::query()->where('listening_test_id', $test->id)->first();
    expect($attempt)->not->toBeNull()
        ->and($attempt->total_questions)->toBe(5);
});

it('shows listening tests link in student sidebar when module is enabled', function (): void {
    $student = listeningStudentWithAccess();

    $this->actingAs($student)
        ->get(route('student.dashboard'))
        ->assertOk()
        ->assertSee('Listening Tests')
        ->assertSee(route('student.listening.tests.index'), false);
});
