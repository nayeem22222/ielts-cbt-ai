<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\Enums\Listening\ListeningAttemptPhase;
use App\Enums\Listening\ListeningAttemptStatus;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningTest;
use App\Models\User;
use App\Repositories\Listening\Student\ListeningAttemptAnswerRepository;
use App\Repositories\Listening\Student\ListeningAttemptRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListeningAttemptService
{
    public function __construct(
        private readonly ListeningAttemptRepository $attempts,
        private readonly ListeningAttemptAnswerRepository $answers,
        private readonly ListeningTestAccessService $access,
        private readonly ListeningTimerService $timer,
        private readonly ListeningOfficialTimerService $officialTimer,
        private readonly ListeningAttemptLifecycleService $lifecycle,
    ) {}

    public function start(User $user, ListeningTest $test, array $clientMeta = []): ListeningAttempt
    {
        if (! $this->access->isStartable($test)) {
            throw ValidationException::withMessages([
                'test' => 'This listening test is not available.',
            ]);
        }

        if (config('listening.student_attempts.resume_existing_attempt', true)) {
            $existing = $this->attempts->findInProgressForUserAndTest($user->id, $test->id);

            if ($existing !== null && ! config('listening.student_attempts.allow_multiple_in_progress_attempts', false)) {
                return $existing;
            }
        }

        $totalSeconds = ((int) $test->duration_minutes * 60) + ((int) $test->transfer_time_minutes * 60);

        return DB::transaction(function () use ($user, $test, $clientMeta): ListeningAttempt {
            return $this->lifecycle->startAttempt($user, $test, $clientMeta);
        });
    }

    public function updatePosition(ListeningAttempt $attempt, int $sectionNumber, int $questionNumber): ListeningAttempt
    {
        return $this->attempts->update($attempt, [
            'current_section_number' => max(1, min(4, $sectionNumber)),
            'current_question_number' => max(1, min(max(1, (int) $attempt->total_questions), $questionNumber)),
        ]);
    }

    public function markSubmitted(ListeningAttempt $attempt, bool $auto = false): ListeningAttempt
    {
        $status = $auto ? ListeningAttemptStatus::AutoSubmitted : ListeningAttemptStatus::Submitted;

        return $this->attempts->update($attempt, [
            'status' => $status,
            'current_phase' => ListeningAttemptPhase::Submitted,
            'submitted_at' => now(),
            'remaining_seconds' => $this->timer->remainingSeconds($attempt),
        ]);
    }

    public function markExpired(ListeningAttempt $attempt): ListeningAttempt
    {
        return $this->attempts->update($attempt, [
            'status' => ListeningAttemptStatus::Expired,
            'current_phase' => ListeningAttemptPhase::Expired,
            'submitted_at' => $attempt->submitted_at ?? now(),
            'remaining_seconds' => 0,
        ]);
    }

    public function assertOwnedBy(ListeningAttempt $attempt, User $user): void
    {
        if ((int) $attempt->user_id !== (int) $user->id) {
            abort(403);
        }
    }

    public function assertEditable(ListeningAttempt $attempt): void
    {
        if ($attempt->status !== ListeningAttemptStatus::InProgress) {
            abort(403, 'This attempt can no longer be edited.');
        }

        if ($this->officialTimer->isExpired($attempt)) {
            abort(403, 'This attempt has expired.');
        }

        if (! $this->officialTimer->canSaveAnswer($attempt)) {
            abort(403, 'Answers cannot be edited in the current phase.');
        }
    }
}
