<?php

declare(strict_types=1);

namespace App\Services\Exam\Analytics;

use App\Models\ReadingQuestionTiming;
use App\Models\TestAttempt;
use App\Services\Service;
use Illuminate\Support\Carbon;

class ReadingQuestionTimingService extends Service
{
    /**
     * @param  array<int, array{question_id: int, time_spent_seconds?: int, visit_count?: int}>  $timings
     */
    public function syncTimings(TestAttempt $attempt, array $timings): void
    {
        foreach ($timings as $timing) {
            $questionId = (int) ($timing['question_id'] ?? 0);

            if ($questionId <= 0) {
                continue;
            }

            $existing = ReadingQuestionTiming::query()->firstOrNew([
                'test_attempt_id' => $attempt->id,
                'question_id' => $questionId,
            ]);

            $seconds = max(
                (int) ($existing->time_spent_seconds ?? 0),
                (int) ($timing['time_spent_seconds'] ?? 0)
            );
            $visits = max(
                (int) ($existing->visit_count ?? 0),
                (int) ($timing['visit_count'] ?? 0)
            );

            $now = now();

            ReadingQuestionTiming::query()->updateOrCreate(
                [
                    'test_attempt_id' => $attempt->id,
                    'question_id' => $questionId,
                ],
                [
                    'time_spent_seconds' => $seconds,
                    'visit_count' => $visits,
                    'first_viewed_at' => $existing->first_viewed_at ?? $now,
                    'last_viewed_at' => $now,
                ]
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function timingsForAttempt(TestAttempt $attempt): array
    {
        return ReadingQuestionTiming::query()
            ->where('test_attempt_id', $attempt->id)
            ->with('question')
            ->get()
            ->map(fn (ReadingQuestionTiming $timing): array => [
                'question_id' => $timing->question_id,
                'question_number' => $timing->question?->question_number,
                'time_spent_seconds' => $timing->time_spent_seconds,
                'visit_count' => $timing->visit_count,
                'first_viewed_at' => $timing->first_viewed_at?->toIso8601String(),
                'last_viewed_at' => $timing->last_viewed_at?->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
