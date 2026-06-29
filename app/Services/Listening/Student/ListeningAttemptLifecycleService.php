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

class ListeningAttemptLifecycleService
{
    public function __construct(
        private readonly ListeningAttemptRepository $attempts,
        private readonly ListeningAttemptAnswerRepository $answers,
        private readonly ListeningOfficialFlowService $flow,
    ) {}

    public function startAttempt(User $user, ListeningTest $test, array $clientMeta = []): ListeningAttempt
    {
        $now = now();
        $listeningMinutes = (int) ($test->duration_minutes ?: config('listening.official_flow.default_listening_minutes', 30));
        $transferMinutes = config('listening.official_flow.allow_transfer_time', true)
            ? (int) ($test->transfer_time_minutes ?: config('listening.official_flow.default_transfer_minutes', 10))
            : 0;

        $listeningEndedAt = $now->copy()->addMinutes($listeningMinutes);
        $transferStartedAt = $transferMinutes > 0 ? $listeningEndedAt->copy() : null;
        $transferEndedAt = $transferMinutes > 0 ? $listeningEndedAt->copy()->addMinutes($transferMinutes) : null;
        $expiresAt = $transferEndedAt ?? $listeningEndedAt;
        $totalSeconds = (int) $now->diffInSeconds($expiresAt);

        $activeQuestionCount = $test->questions()->where('is_active', true)->count();

        $attempt = $this->attempts->create([
            'user_id' => $user->id,
            'listening_test_id' => $test->id,
            'status' => ListeningAttemptStatus::InProgress,
            'current_phase' => ListeningAttemptPhase::Listening,
            'started_at' => $now,
            'listening_started_at' => $now,
            'listening_ended_at' => $listeningEndedAt,
            'transfer_started_at' => $transferStartedAt,
            'transfer_ended_at' => $transferEndedAt,
            'timer_started_at' => $now,
            'last_timer_sync_at' => $now,
            'expires_at' => $expiresAt,
            'total_questions' => max(1, $activeQuestionCount),
            'total_answered' => 0,
            'remaining_seconds' => $totalSeconds,
            'duration_seconds' => $totalSeconds,
            'current_section_number' => 1,
            'current_question_number' => 1,
            'browser_info' => $clientMeta['browser_info'] ?? null,
            'ip_address' => $clientMeta['ip_address'] ?? null,
            'device_info' => $clientMeta['device_info'] ?? null,
            'timer_meta' => ['flow' => 'official'],
        ]);

        $this->flow->initializeAttemptFlow($attempt);

        if (config('listening.student_attempts.create_answer_rows_on_start', true)) {
            $questions = $test->questions()->where('is_active', true)->orderBy('question_number')->get();
            $this->answers->createRowsForQuestions($attempt, $questions);
        }

        return $attempt->refresh();
    }

    public function resumeAttempt(User $user, ListeningAttempt $attempt): ListeningAttempt
    {
        return $attempt;
    }

    public function expireAttempt(ListeningAttempt $attempt): ListeningAttempt
    {
        return $this->attempts->update($attempt, [
            'status' => ListeningAttemptStatus::Expired,
            'current_phase' => ListeningAttemptPhase::Expired,
            'submitted_at' => $attempt->submitted_at ?? now(),
            'remaining_seconds' => 0,
        ]);
    }

    public function cancelAttempt(ListeningAttempt $attempt): ListeningAttempt
    {
        return $this->attempts->update($attempt, [
            'status' => ListeningAttemptStatus::Cancelled,
            'remaining_seconds' => 0,
        ]);
    }

    public function lockSubmittedAttempt(ListeningAttempt $attempt): void
    {
        if ($attempt->status === ListeningAttemptStatus::InProgress) {
            return;
        }

        $this->attempts->update($attempt, [
            'current_phase' => ListeningAttemptPhase::Submitted,
            'remaining_seconds' => 0,
        ]);
    }
}
