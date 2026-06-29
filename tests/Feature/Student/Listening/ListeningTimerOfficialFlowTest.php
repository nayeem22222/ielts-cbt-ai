<?php

declare(strict_types=1);

use App\Enums\Auth\UserRole;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningAttemptPhase;
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
use App\Services\Listening\Student\ListeningAutoSubmitService;
use App\Services\Listening\Student\ListeningOfficialTimerService;
use App\Services\Listening\Student\ListeningPhaseTransitionService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    seedRbac();
    test()->withoutVite();
    Storage::fake('public');
});

function timerStudent(): User
{
    $student = createUserWithRole(UserRole::Student, [
        'email' => 'listening-timer-'.uniqid().'@example.com',
        'email_verified_at' => now(),
    ]);

    assignStudentPackage($student, createDemoPackage([
        'slug' => 'listening-timer-package-'.uniqid(),
        'module_access' => [IeltsModule::Listening->value],
        'is_public' => true,
        'is_active' => true,
    ]));

    return $student;
}

function timerPlayableTest(array $overrides = [], ?int $transferMinutes = 10): ListeningTest
{
    $admin = createUserWithRole(UserRole::SuperAdmin, ['email_verified_at' => now()]);

    $test = ListeningTest::query()->create(array_merge([
        'title' => 'Timer Mock '.uniqid(),
        'slug' => 'listening-timer-'.uniqid(),
        'test_code' => 'LST-TMR-'.strtoupper(uniqid()),
        'test_type' => ListeningTestType::Academic,
        'status' => ListeningTestStatus::Published,
        'is_active' => true,
        'duration_minutes' => 30,
        'transfer_time_minutes' => $transferMinutes,
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

    foreach ([1 => [1, 10], 2 => [11, 20], 3 => [21, 30], 4 => [31, 40]] as $sectionNumber => [$start, $end]) {
        $path = "listening/audio/normalized/timer-{$sectionNumber}-{$test->id}.mp3";
        Storage::disk('public')->put($path, 'fake');

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

function timerStartAttempt(User $student, ListeningTest $test): ListeningAttempt
{
    test()->actingAs($student)->post(route('student.listening.tests.start', $test))->assertRedirect();

    return ListeningAttempt::query()->where('user_id', $student->id)->where('listening_test_id', $test->id)->firstOrFail();
}

it('starts attempt in listening phase with official timestamps', function (): void {
    $test = timerPlayableTest();
    $student = timerStudent();
    $attempt = timerStartAttempt($student, $test);

    expect($attempt->current_phase)->toBe(ListeningAttemptPhase::Listening)
        ->and($attempt->listening_started_at)->not->toBeNull()
        ->and($attempt->listening_ended_at)->not->toBeNull()
        ->and($attempt->timer_started_at)->not->toBeNull();
});

it('sets expires_at to include transfer time', function (): void {
    $test = timerPlayableTest([], 10);
    $student = timerStudent();
    $attempt = timerStartAttempt($student, $test);

    expect($attempt->transfer_ended_at)->not->toBeNull()
        ->and($attempt->expires_at?->equalTo($attempt->transfer_ended_at))->toBeTrue();
});

it('returns server authoritative timer state', function (): void {
    $test = timerPlayableTest();
    $student = timerStudent();
    $attempt = timerStartAttempt($student, $test);

    $this->actingAs($student)
        ->getJson(route('student.listening.attempts.timer.state', $attempt))
        ->assertOk()
        ->assertJsonPath('timer.current_phase', ListeningAttemptPhase::Listening->value)
        ->assertJsonStructure(['timer' => ['server_now', 'total_remaining_seconds', 'listening_remaining_seconds']]);
});

it('does not extend timer from client sync payload', function (): void {
    $test = timerPlayableTest();
    $student = timerStudent();
    $attempt = timerStartAttempt($student, $test);
    $before = $attempt->expires_at;

    $this->actingAs($student)->postJson(route('student.listening.attempts.timer.sync', $attempt), [
        'client_remaining_seconds' => 99999,
    ]);

    expect($attempt->refresh()->expires_at?->equalTo($before))->toBeTrue();
});

it('transitions from listening to transfer phase', function (): void {
    $test = timerPlayableTest();
    $student = timerStudent();
    $attempt = timerStartAttempt($student, $test);
    $attempt->update([
        'listening_ended_at' => now()->subMinute(),
        'transfer_started_at' => now()->subMinute(),
    ]);

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.phase.transfer', $attempt))
        ->assertOk()
        ->assertJsonPath('phase.current_phase', ListeningAttemptPhase::Transfer->value);

    expect($attempt->refresh()->current_phase)->toBe(ListeningAttemptPhase::Transfer);
});

it('auto submits when transfer time ends', function (): void {
    $test = timerPlayableTest();
    $student = timerStudent();
    $attempt = timerStartAttempt($student, $test);
    $attempt->update(['expires_at' => now()->subSecond()]);

    app(ListeningAutoSubmitService::class)->autoSubmitIfExpired($attempt->refresh());

    expect($attempt->refresh()->status)->toBe(ListeningAttemptStatus::AutoSubmitted)
        ->and($attempt->auto_submitted_at)->not->toBeNull();
});

it('auto submits after listening when transfer disabled', function (): void {
    config(['listening.official_flow.allow_transfer_time' => false]);
    $test = timerPlayableTest([], 0);
    $student = timerStudent();
    $attempt = timerStartAttempt($student, $test);

    expect($attempt->transfer_ended_at)->toBeNull()
        ->and($attempt->expires_at?->equalTo($attempt->listening_ended_at))->toBeTrue();
});

it('middleware redirects expired attempt', function (): void {
    $test = timerPlayableTest();
    $student = timerStudent();
    $attempt = timerStartAttempt($student, $test);
    $attempt->update(['expires_at' => now()->subMinute()]);

    $this->actingAs($student)
        ->get(route('student.listening.attempts.player', $attempt))
        ->assertRedirect(route('student.listening.attempts.expired', $attempt));
});

it('rejects phase rollback after submission', function (): void {
    $test = timerPlayableTest();
    $student = timerStudent();
    $attempt = timerStartAttempt($student, $test);
    $attempt->update([
        'status' => ListeningAttemptStatus::Submitted,
        'current_phase' => ListeningAttemptPhase::Submitted,
        'submitted_at' => now(),
    ]);

    expect(fn () => app(ListeningPhaseTransitionService::class)->transition(
        $attempt,
        ListeningAttemptPhase::Listening,
    ))->toThrow(Illuminate\Validation\ValidationException::class);
});

it('allows audio start once per section', function (): void {
    $test = timerPlayableTest();
    $student = timerStudent();
    $attempt = timerStartAttempt($student, $test);

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.audio.start', $attempt), ['section_number' => 1])
        ->assertOk()
        ->assertJsonPath('audio.play_count', 1);

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.audio.start', $attempt), ['section_number' => 1])
        ->assertStatus(422);
});

it('blocks audio start during transfer phase', function (): void {
    $test = timerPlayableTest();
    $student = timerStudent();
    $attempt = timerStartAttempt($student, $test);
    $attempt->update([
        'current_phase' => ListeningAttemptPhase::Transfer,
        'listening_ended_at' => now()->subMinute(),
    ]);

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.audio.start', $attempt), ['section_number' => 1])
        ->assertStatus(403);
});

it('command auto submits expired attempts', function (): void {
    $test = timerPlayableTest();
    $student = timerStudent();
    $attempt = timerStartAttempt($student, $test);
    $attempt->update(['expires_at' => now()->subMinute()]);

    Artisan::call('listening:attempts:auto-submit-expired', ['--limit' => 10]);

    expect($attempt->refresh()->status)->toBe(ListeningAttemptStatus::AutoSubmitted);
});

it('double auto submit is safe', function (): void {
    $test = timerPlayableTest();
    $student = timerStudent();
    $attempt = timerStartAttempt($student, $test);
    $attempt->update(['expires_at' => now()->subMinute()]);
    $service = app(ListeningAutoSubmitService::class);

    $service->autoSubmit($attempt);
    $firstSubmittedAt = $attempt->refresh()->auto_submitted_at;
    $service->autoSubmit($attempt->refresh());

    expect($attempt->refresh()->status)->toBe(ListeningAttemptStatus::AutoSubmitted)
        ->and($attempt->auto_submitted_at?->equalTo($firstSubmittedAt))->toBeTrue();
});

it('blocks another user from timer sync', function (): void {
    $test = timerPlayableTest();
    $owner = timerStudent();
    $other = timerStudent();
    $attempt = timerStartAttempt($owner, $test);

    $this->actingAs($other)
        ->postJson(route('student.listening.attempts.timer.sync', $attempt))
        ->assertForbidden();
});

it('timer payload excludes correct answers and transcript', function (): void {
    $test = timerPlayableTest();
    $student = timerStudent();
    $attempt = timerStartAttempt($student, $test);

    $response = $this->actingAs($student)->getJson(route('student.listening.attempts.timer.state', $attempt));
    $encoded = json_encode($response->json());

    expect($encoded)->not->toContain('correct_answer')
        ->and($encoded)->not->toContain('transcript_text');
});

it('auto submit endpoint is idempotent for submitted attempts', function (): void {
    $test = timerPlayableTest();
    $student = timerStudent();
    $attempt = timerStartAttempt($student, $test);
    $attempt->update([
        'status' => ListeningAttemptStatus::AutoSubmitted,
        'current_phase' => ListeningAttemptPhase::Submitted,
        'submitted_at' => now(),
        'auto_submitted_at' => now(),
    ]);

    $this->actingAs($student)
        ->postJson(route('student.listening.attempts.auto_submit', $attempt))
        ->assertOk()
        ->assertJsonPath('already_submitted', true);
});

it('player includes official timer UI elements', function (): void {
    $test = timerPlayableTest();
    $student = timerStudent();
    $attempt = timerStartAttempt($student, $test);

    $this->actingAs($student)
        ->get(route('student.listening.attempts.player', $attempt))
        ->assertOk()
        ->assertSee('listening-official-timer', false)
        ->assertSee('listening-phase-banner', false)
        ->assertSee('listening-time-warning-modal', false);
});

it('reading module files remain unaffected by listening timer flow', function (): void {
    $files = glob(base_path('app/Services/Listening/Student/*.php')) ?: [];
    foreach ($files as $file) {
        $contents = file_get_contents($file) ?: '';
        expect($contents)->not->toContain('ReadingTest');
        expect($contents)->not->toContain('ReadingAttempt');
    }
});

it('official timer service calculates remaining seconds from server time', function (): void {
    $test = timerPlayableTest();
    $student = timerStudent();
    $attempt = timerStartAttempt($student, $test);

    $state = app(ListeningOfficialTimerService::class)->getState($attempt);
    expect($state->totalRemainingSeconds)->toBeGreaterThan(0)
        ->and($state->listeningRemainingSeconds)->toBeGreaterThan(0);
});
