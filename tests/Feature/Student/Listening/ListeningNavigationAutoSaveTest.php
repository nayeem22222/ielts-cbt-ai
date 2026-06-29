<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningAnswerStatus;
use App\Enums\Listening\ListeningAttemptStatus;
use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Enums\Listening\ListeningSectionType;
use App\Enums\Listening\ListeningTestStatus;
use App\Enums\Listening\ListeningTestType;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningAudio;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Models\Listening\ListeningTestSetting;
use App\Models\User;
use App\Services\Listening\Student\ListeningAutoSaveService;
use App\Services\Listening\Student\ListeningPlayerRecoveryService;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    seedRbac();
    test()->withoutVite();
    Storage::fake('public');
});

function navListeningStudent(): User
{
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'listening-nav-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    assignStudentPackage($student, createDemoPackage([
        'slug' => 'listening-nav-package-'.uniqid(),
        'module_access' => [IeltsModule::Listening->value],
        'is_public' => true,
        'is_active' => true,
    ]));

    return $student;
}

function navPlayableListeningTest(): ListeningTest
{
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);

    $test = ListeningTest::query()->create([
        'title' => 'Navigation Mock '.uniqid(),
        'slug' => 'listening-nav-'.uniqid(),
        'test_code' => 'LST-NAV-'.strtoupper(uniqid()),
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
    ]);

    ListeningTestSetting::query()->create(array_merge(
        ['listening_test_id' => $test->id],
        ListeningTestSetting::officialDefaults(),
    ));

    foreach ([1 => [1, 10], 2 => [11, 20], 3 => [21, 30], 4 => [31, 40]] as $sectionNumber => [$start, $end]) {
        $path = "listening/audio/normalized/nav-{$sectionNumber}-{$test->id}.mp3";
        Storage::disk('public')->put($path, 'fake-audio');

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

    return $test->fresh(['sections', 'questions']);
}

function navStartAttempt(User $student, ListeningTest $test): ListeningAttempt
{
    test()->actingAs($student)->post(route('student.listening.tests.start', $test))->assertRedirect();

    return ListeningAttempt::query()->where('user_id', $student->id)->where('listening_test_id', $test->id)->firstOrFail();
}

it('single autosave saves answer and returns palette', function (): void {
    $test = navPlayableListeningTest();
    $student = navListeningStudent();
    $attempt = navStartAttempt($student, $test);
    $question = $test->questions()->where('question_number', 1)->firstOrFail();

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.autosave', $attempt), [
            'question_id' => $question->id,
            'answer' => [['value' => 'library', 'type' => 'text']],
            'client_sequence' => 1,
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('answer_status', ListeningAnswerStatus::Answered->value)
        ->assertJsonStructure(['palette', 'navigation', 'total_answered']);
});

it('bulk autosave saves multiple answers', function (): void {
    $test = navPlayableListeningTest();
    $student = navListeningStudent();
    $attempt = navStartAttempt($student, $test);
    $questions = $test->questions()->orderBy('question_number')->take(2)->get();

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.autosave.bulk', $attempt), [
            'answers' => [
                ['question_id' => $questions[0]->id, 'answer' => [['value' => 'A', 'type' => 'text']]],
                ['question_id' => $questions[1]->id, 'answer' => [['value' => 'B', 'type' => 'text']]],
            ],
            'current_section_number' => 1,
            'current_question_number' => 2,
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('total_answered', 2);
});

it('updates total_answered after autosave', function (): void {
    $test = navPlayableListeningTest();
    $student = navListeningStudent();
    $attempt = navStartAttempt($student, $test);
    $question = $test->questions()->firstOrFail();

    $this->actingAs($student)->postJson(route('student.listening.attempts.autosave', $attempt), [
        'question_id' => $question->id,
        'answer' => [['value' => 'test', 'type' => 'text']],
    ]);

    expect($attempt->refresh()->total_answered)->toBe(1);
});

it('treats empty answer as unanswered', function (): void {
    $test = navPlayableListeningTest();
    $student = navListeningStudent();
    $attempt = navStartAttempt($student, $test);
    $question = $test->questions()->firstOrFail();

    $this->actingAs($student)->postJson(route('student.listening.attempts.autosave', $attempt), [
        'question_id' => $question->id,
        'answer' => [['value' => 'filled', 'type' => 'text']],
    ]);

    $this->actingAs($student)->postJson(route('student.listening.attempts.autosave', $attempt), [
        'question_id' => $question->id,
        'answer' => '',
    ])->assertJsonPath('answer_status', ListeningAnswerStatus::Unanswered->value);
});

it('skips duplicate hash writes', function (): void {
    $test = navPlayableListeningTest();
    $student = navListeningStudent();
    $attempt = navStartAttempt($student, $test);
    $question = $test->questions()->firstOrFail();
    $payload = [
        'question_id' => $question->id,
        'answer' => [['value' => 'same', 'type' => 'text']],
        'client_answer_hash' => hash('sha256', json_encode([['value' => 'same', 'type' => 'text']])),
        'client_sequence' => 5,
    ];

    $this->actingAs($student)->postJson(route('student.listening.attempts.autosave', $attempt), $payload);
    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.autosave', $attempt), array_merge($payload, ['client_sequence' => 6]))
        ->assertJsonPath('skipped', true);
});

it('updates current position via navigation endpoint', function (): void {
    $test = navPlayableListeningTest();
    $student = navListeningStudent();
    $attempt = navStartAttempt($student, $test);

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.navigation.update', $attempt), [
            'current_section_number' => 2,
            'current_question_number' => 15,
            'direction' => 'jump',
        ])
        ->assertOk()
        ->assertJsonPath('navigation.current_question_number', 15)
        ->assertJsonPath('navigation.current_section_number', 2);

    expect($attempt->refresh()->current_question_number)->toBe(15);
});

it('rejects invalid position', function (): void {
    $test = navPlayableListeningTest();
    $student = navListeningStudent();
    $attempt = navStartAttempt($student, $test);

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.navigation.update', $attempt), [
            'current_section_number' => 1,
            'current_question_number' => 25,
        ])
        ->assertStatus(422);
});

it('rejects autosave for question outside attempt test', function (): void {
    $testA = navPlayableListeningTest();
    $testB = navPlayableListeningTest();
    $student = navListeningStudent();
    $attempt = navStartAttempt($student, $testA);
    $foreign = $testB->questions()->firstOrFail();

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.autosave', $attempt), [
            'question_id' => $foreign->id,
            'answer' => [['value' => 'X', 'type' => 'text']],
        ])
        ->assertStatus(422);
});

it('flags and unflags a question for review', function (): void {
    $test = navPlayableListeningTest();
    $student = navListeningStudent();
    $attempt = navStartAttempt($student, $test);
    $question = $test->questions()->firstOrFail();

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.questions.review', [$attempt, $question]), ['flagged' => true])
        ->assertOk()
        ->assertJsonPath('flagged', true);

    $row = ListeningAttemptAnswer::query()->where('listening_attempt_id', $attempt->id)->where('listening_question_id', $question->id)->firstOrFail();
    expect($row->meta['is_flagged'] ?? false)->toBeTrue();

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.questions.review', [$attempt, $question]), ['flagged' => false])
        ->assertJsonPath('flagged', false);
});

it('returns palette statuses from autosave response', function (): void {
    $test = navPlayableListeningTest();
    $student = navListeningStudent();
    $attempt = navStartAttempt($student, $test);
    $question = $test->questions()->where('question_number', 3)->firstOrFail();

    $response = $this->actingAs($student)->postJson(route('student.listening.attempts.autosave', $attempt), [
        'question_id' => $question->id,
        'answer' => [['value' => 'answered', 'type' => 'text']],
    ]);

    $palette = $response->json('palette');
    $answeredItem = collect($palette)->firstWhere('question_number', 3);
    expect($answeredItem['status'] ?? null)->toBe('answered');
});

it('rejects autosave on submitted attempt', function (): void {
    $test = navPlayableListeningTest();
    $student = navListeningStudent();
    $attempt = navStartAttempt($student, $test);
    $attempt->update(['status' => ListeningAttemptStatus::Submitted, 'submitted_at' => now()]);
    $question = $test->questions()->firstOrFail();

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.autosave', $attempt), [
            'question_id' => $question->id,
            'answer' => [['value' => 'late', 'type' => 'text']],
        ])
        ->assertForbidden();
});

it('rejects autosave on expired attempt', function (): void {
    $test = navPlayableListeningTest();
    $student = navListeningStudent();
    $attempt = navStartAttempt($student, $test);
    $attempt->update(['expires_at' => now()->subMinute()]);
    $question = $test->questions()->firstOrFail();

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.autosave', $attempt), [
            'question_id' => $question->id,
            'answer' => [['value' => 'late', 'type' => 'text']],
        ])
        ->assertStatus(403);
});

it('blocks another user from autosave', function (): void {
    $test = navPlayableListeningTest();
    $owner = navListeningStudent();
    $other = navListeningStudent();
    $attempt = navStartAttempt($owner, $test);
    $question = $test->questions()->firstOrFail();

    $this->actingAs($other)
        ->postJson(route('student.listening.attempts.autosave', $attempt), [
            'question_id' => $question->id,
            'answer' => [['value' => 'hack', 'type' => 'text']],
        ])
        ->assertForbidden();
});

it('bulk autosave uses transaction and updates counts', function (): void {
    $test = navPlayableListeningTest();
    $student = navListeningStudent();
    $attempt = navStartAttempt($student, $test);
    $questions = $test->questions()->orderBy('question_number')->take(3)->get();

    $this->actingAs($student)->postJson(route('student.listening.attempts.autosave.bulk', $attempt), [
        'answers' => $questions->map(fn ($q) => [
            'question_id' => $q->id,
            'answer' => [['value' => "v{$q->question_number}", 'type' => 'text']],
        ])->all(),
    ]);

    expect($attempt->refresh()->total_answered)->toBe(3);
    expect(ListeningAttemptAnswer::query()->where('listening_attempt_id', $attempt->id)->where('answer_status', ListeningAnswerStatus::Answered)->count())->toBe(3);
});

it('recovery payload excludes correct answers and transcript fields', function (): void {
    $test = navPlayableListeningTest();
    $student = navListeningStudent();
    $attempt = navStartAttempt($student, $test);

    $payload = app(ListeningPlayerRecoveryService::class)->buildRecoveryPayload($attempt);
    $encoded = json_encode($payload);

    expect($encoded)->not->toContain('correct_answer')
        ->and($encoded)->not->toContain('transcript_text')
        ->and($encoded)->not->toContain('accepted_answers');
});

it('applies recovery draft answers via state sync', function (): void {
    $test = navPlayableListeningTest();
    $student = navListeningStudent();
    $attempt = navStartAttempt($student, $test);
    $question = $test->questions()->where('question_number', 4)->firstOrFail();

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.state.sync', $attempt), [
            'current_section_number' => 1,
            'current_question_number' => 4,
            'recover_answers' => [[
                'question_id' => $question->id,
                'question_number' => 4,
                'answer' => [['value' => 'recovered', 'type' => 'text']],
            ]],
        ])
        ->assertOk()
        ->assertJsonPath('applied_count', 1);

    $row = ListeningAttemptAnswer::query()->where('listening_attempt_id', $attempt->id)->where('listening_question_id', $question->id)->firstOrFail();
    expect($row->student_answer[0]['value'] ?? null)->toBe('recovered');
});

it('player includes unsynced submit warning UI elements', function (): void {
    $test = navPlayableListeningTest();
    $student = navListeningStudent();
    $attempt = navStartAttempt($student, $test);

    $this->actingAs($student)
        ->get(route('student.listening.attempts.player', $attempt))
        ->assertOk()
        ->assertSee('listening-submit-unsynced', false)
        ->assertSee('listening-recovery-modal', false)
        ->assertSee('listening-offline-banner', false);
});

it('reading module files remain unaffected by listening navigation autosave', function (): void {
    $files = glob(base_path('app/Services/Listening/Student/*.php')) ?: [];
    foreach ($files as $file) {
        $contents = file_get_contents($file) ?: '';
        expect($contents)->not->toContain('ReadingTest');
        expect($contents)->not->toContain('ReadingAttempt');
    }
});

it('does not update score fields during autosave', function (): void {
    $test = navPlayableListeningTest();
    $student = navListeningStudent();
    $attempt = navStartAttempt($student, $test);
    $question = $test->questions()->firstOrFail();

    $attempt->update(['total_correct' => 0, 'raw_score' => 0, 'band_score' => 0]);

    app(ListeningAutoSaveService::class)->saveAnswer(
        $attempt,
        $question,
        [['value' => 'safe', 'type' => 'text']],
    );

    $attempt->refresh();
    expect($attempt->total_correct)->toBe(0)
        ->and($attempt->raw_score)->toBe(0)
        ->and((float) $attempt->band_score)->toBe(0.0);
});
