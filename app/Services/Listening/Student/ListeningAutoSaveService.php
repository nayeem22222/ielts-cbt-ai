<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\Actions\Listening\NormalizeListeningAnswerDataAction;
use App\DTOs\Listening\Student\AutoSaveResultData;
use App\Enums\Listening\ListeningAnswerStatus;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAttemptAnswer;
use App\Models\Listening\ListeningQuestion;
use App\Repositories\Listening\Student\ListeningAttemptAnswerRepository;
use App\Repositories\Listening\Student\ListeningAttemptRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListeningAutoSaveService
{
    public function __construct(
        private readonly ListeningAttemptRepository $attempts,
        private readonly ListeningAttemptAnswerRepository $answers,
        private readonly NormalizeListeningAnswerDataAction $normalizeAnswer,
        private readonly ListeningQuestionPaletteService $palette,
        private readonly ListeningNavigationStateService $navigationState,
    ) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public function saveAnswer(
        ListeningAttempt $attempt,
        ListeningQuestion $question,
        mixed $answer,
        array $meta = [],
    ): AutoSaveResultData {
        $this->assertQuestionBelongsToAttempt($attempt, $question);

        $defaultType = $question->answer_format?->value ?? 'text';
        $normalized = $this->normalizeStudentAnswer($answer, $defaultType);
        $hash = $this->calculateAnswerHash($normalized);
        $isAnswered = ! $this->isAnswerEmpty($answer, $normalized);

        return DB::transaction(function () use (
            $attempt,
            $question,
            $normalized,
            $hash,
            $isAnswered,
            $meta,
        ): AutoSaveResultData {
            $row = $this->answers->findForAttemptQuestion($attempt, $question->id);

            if ($row !== null && $this->skipIfSameHash($row, $hash) && ! $this->isOutdatedClientRequest($row, $meta)) {
                return $this->buildResult($attempt, $question, $row, skipped: true);
            }

            if ($row !== null && $this->isOutdatedClientRequest($row, $meta)) {
                return $this->buildResult($attempt, $question, $row, skipped: true);
            }

            $existingMeta = is_array($row?->meta) ? $row->meta : [];
            $isFlagged = ($existingMeta['is_flagged'] ?? false) === true;
            $autosaveMeta = $this->buildAutosaveMeta($meta, $hash, $normalized);

            if ($row === null) {
                $row = ListeningAttemptAnswer::query()->create([
                    'listening_attempt_id' => $attempt->id,
                    'listening_test_id' => $attempt->listening_test_id,
                    'listening_question_id' => $question->id,
                    'question_number' => $question->question_number,
                    'student_answer' => $isAnswered ? $normalized : null,
                    'normalized_answer' => $isAnswered ? $normalized : null,
                    'answer_status' => $this->resolveStatus($isAnswered, $isFlagged),
                    'answered_at' => $isAnswered ? now() : null,
                    'time_spent_seconds' => isset($meta['time_spent_seconds']) ? (int) $meta['time_spent_seconds'] : null,
                    'meta' => array_merge($existingMeta, ['autosave' => $autosaveMeta]),
                ]);
            } else {
                $row->fill([
                    'student_answer' => $isAnswered ? $normalized : null,
                    'normalized_answer' => $isAnswered ? $normalized : null,
                    'answer_status' => $this->resolveStatus($isAnswered, $isFlagged),
                    'answered_at' => $isAnswered ? now() : null,
                    'time_spent_seconds' => isset($meta['time_spent_seconds'])
                        ? (int) $meta['time_spent_seconds']
                        : $row->time_spent_seconds,
                    'meta' => array_merge($existingMeta, ['autosave' => $autosaveMeta]),
                ])->save();
            }

            $this->updateAttemptAnsweredCount($attempt);

            return $this->buildResult($attempt, $question, $row->refresh(), skipped: false);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $answers
     * @return array<string, mixed>
     */
    public function bulkSave(ListeningAttempt $attempt, array $answers): array
    {
        $results = [];

        DB::transaction(function () use ($attempt, $answers, &$results): void {
            foreach ($answers as $item) {
                $question = ListeningQuestion::query()->find((int) ($item['question_id'] ?? 0));

                if ($question === null) {
                    continue;
                }

                $meta = [
                    'client_answer_hash' => $item['client_answer_hash'] ?? null,
                    'client_sequence' => $item['client_sequence'] ?? null,
                    'client_saved_at' => $item['client_saved_at'] ?? null,
                    'saved_from' => 'bulk',
                ];

                $results[] = $this->saveAnswer(
                    $attempt,
                    $question,
                    $item['answer'] ?? null,
                    $meta,
                )->toArray();
            }
        });

        return [
            'success' => true,
            'results' => $results,
            'total_answered' => $attempt->refresh()->total_answered,
            'palette' => $this->palette->build($attempt),
            'navigation' => $this->navigationState->getNavigationPayload($attempt),
            'saved_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function normalizeStudentAnswer(mixed $answer, string $defaultType = 'text'): array
    {
        return $this->normalizeAnswer->execute($answer, $defaultType);
    }

    /**
     * @param  list<array<string, mixed>>|mixed  $answer
     */
    public function calculateAnswerHash(mixed $answer): string
    {
        $normalized = is_array($answer) && $this->looksNormalized($answer)
            ? $answer
            : $this->normalizeStudentAnswer($answer);

        return hash('sha256', json_encode($normalized));
    }

    public function skipIfSameHash(ListeningAttemptAnswer $attemptAnswer, string $hash): bool
    {
        if (! config('listening.autosave.answer_hashing', true)) {
            return false;
        }

        $meta = is_array($attemptAnswer->meta) ? $attemptAnswer->meta : [];
        $autosave = is_array($meta['autosave'] ?? null) ? $meta['autosave'] : [];
        $lastHash = (string) ($autosave['last_saved_hash'] ?? $autosave['client_hash'] ?? '');

        return $lastHash !== '' && hash_equals($lastHash, $hash);
    }

    public function updateAttemptAnsweredCount(ListeningAttempt $attempt): void
    {
        $this->attempts->update($attempt, [
            'total_answered' => $this->palette->countAnswered($attempt),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getSaveResponse(ListeningAttempt $attempt): array
    {
        $attempt->refresh();

        return [
            'total_answered' => $attempt->total_answered,
            'palette' => $this->palette->build($attempt),
            'navigation' => $this->navigationState->getNavigationPayload($attempt),
            'counts' => [
                'answered' => $this->palette->countAnswered($attempt),
                'unanswered' => $this->palette->countUnanswered($attempt),
                'flagged' => $this->palette->countFlagged($attempt),
            ],
            'saved_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $normalized
     */
    public function isAnswerEmpty(mixed $answer, array $normalized = []): bool
    {
        if ($answer === null) {
            return true;
        }

        if (is_string($answer) && trim($answer) === '') {
            return true;
        }

        if ($answer === []) {
            return true;
        }

        if ($normalized === []) {
            $normalized = $this->normalizeStudentAnswer($answer);
        }

        if ($normalized === []) {
            return true;
        }

        foreach ($normalized as $item) {
            if (! is_array($item)) {
                continue;
            }

            $value = trim((string) ($item['value'] ?? ''));

            if ($value !== '') {
                return false;
            }
        }

        return true;
    }

    private function assertQuestionBelongsToAttempt(ListeningAttempt $attempt, ListeningQuestion $question): void
    {
        if ((int) $question->listening_test_id !== (int) $attempt->listening_test_id) {
            throw ValidationException::withMessages([
                'question_id' => 'Question does not belong to this listening test.',
            ]);
        }

        if (! $question->is_active) {
            throw ValidationException::withMessages([
                'question_id' => 'Question is not active.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     * @param  list<array<string, mixed>>  $normalized
     * @return array<string, mixed>
     */
    private function buildAutosaveMeta(array $meta, string $hash, array $normalized): array
    {
        return [
            'client_hash' => (string) ($meta['client_answer_hash'] ?? $hash),
            'last_saved_hash' => $hash,
            'client_sequence' => isset($meta['client_sequence']) ? (int) $meta['client_sequence'] : null,
            'saved_from' => (string) ($meta['saved_from'] ?? 'single'),
            'client_saved_at' => (string) ($meta['client_saved_at'] ?? now()->toIso8601String()),
        ];
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function isOutdatedClientRequest(ListeningAttemptAnswer $row, array $meta): bool
    {
        $existingMeta = is_array($row->meta) ? $row->meta : [];
        $existingAutosave = is_array($existingMeta['autosave'] ?? null) ? $existingMeta['autosave'] : [];

        $existingAt = $existingAutosave['client_saved_at'] ?? null;
        $incomingAt = $meta['client_saved_at'] ?? null;

        if (is_string($existingAt) && is_string($incomingAt) && $incomingAt !== '') {
            return strtotime($incomingAt) < strtotime($existingAt);
        }

        $existingSeq = (int) ($existingAutosave['client_sequence'] ?? 0);
        $incomingSeq = (int) ($meta['client_sequence'] ?? 0);

        return $incomingSeq > 0 && $incomingSeq <= $existingSeq;
    }

    private function resolveStatus(bool $isAnswered, bool $isFlagged): ListeningAnswerStatus
    {
        if ($isAnswered) {
            return ListeningAnswerStatus::Answered;
        }

        if ($isFlagged) {
            return ListeningAnswerStatus::Flagged;
        }

        return ListeningAnswerStatus::Unanswered;
    }

    private function buildResult(
        ListeningAttempt $attempt,
        ListeningQuestion $question,
        ListeningAttemptAnswer $row,
        bool $skipped,
    ): AutoSaveResultData {
        $attempt->refresh();

        return new AutoSaveResultData(
            success: true,
            skipped: $skipped,
            questionId: $question->id,
            questionNumber: (int) $question->question_number,
            answerStatus: (string) ($row->answer_status?->value ?? ListeningAnswerStatus::Unanswered->value),
            totalAnswered: (int) $attempt->total_answered,
            palette: $this->palette->build($attempt),
            navigation: $this->navigationState->getNavigationPayload($attempt),
            savedAt: now()->toIso8601String(),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $answer
     */
    private function looksNormalized(array $answer): bool
    {
        return isset($answer[0]) && is_array($answer[0]) && array_key_exists('value', $answer[0]);
    }
}
