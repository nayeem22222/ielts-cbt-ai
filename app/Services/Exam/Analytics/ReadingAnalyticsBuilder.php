<?php

declare(strict_types=1);

namespace App\Services\Exam\Analytics;

use App\Models\Question;
use App\Models\ReadingAnalytics;
use App\Models\ReadingQuestionTiming;
use App\Models\Result;
use App\Models\ResultQuestionScore;
use App\Models\StudentAnswer;
use App\Models\TestAttempt;
use App\Models\TestQuestion;
use App\Services\Service;
use Illuminate\Support\Collection;

class ReadingAnalyticsBuilder extends Service
{
    /**
     * @return array<string, mixed>
     */
    public function buildHeatMap(Collection $questionScores, Collection $timings): array
    {
        $timingByQuestion = $timings->keyBy('question_id');

        $cells = $questionScores->map(function (ResultQuestionScore $score) use ($timingByQuestion): array {
            /** @var ReadingQuestionTiming|null $timing */
            $timing = $timingByQuestion->get($score->question_id);
            $timeSpent = (int) ($timing?->time_spent_seconds ?? 0);
            $accuracy = $score->is_correct ? 100 : (((float) $score->partial_ratio) * 100);
            $timeIntensity = min(1, $timeSpent / 120);
            $accuracyIntensity = $accuracy / 100;

            return [
                'question_id' => $score->question_id,
                'question_number' => $score->question_number,
                'question_type' => $score->question_type->value,
                'is_correct' => $score->is_correct,
                'is_skipped' => blank($score->student_response),
                'time_spent_seconds' => $timeSpent,
                'accuracy_percent' => round($accuracy, 2),
                'intensity' => round(max($timeIntensity, 1 - $accuracyIntensity), 4),
                'tone' => $this->heatTone($score, $timeSpent),
            ];
        })->values()->all();

        return [
            'cells' => $cells,
            'legend' => [
                'low' => 'Fast & correct',
                'medium' => 'Slow or partial',
                'high' => 'Skipped or incorrect',
            ],
        ];
    }

    public function buildForAttempt(TestAttempt $attempt, Result $result): ReadingAnalytics
    {
        $attempt->loadMissing(['answers', 'test']);

        $questionScores = $result->questionScores()->with('question')->get();
        $timings = ReadingQuestionTiming::query()
            ->where('test_attempt_id', $attempt->id)
            ->get();

        $timePerQuestion = $this->buildTimePerQuestion($questionScores, $timings, $attempt->answers);
        $averageTime = $this->averageTime($timePerQuestion);
        $skipped = $this->skippedQuestions($questionScores);
        $accuracy = (float) ($result->statistics?->accuracy_percent ?? 0);
        $heatMap = $this->buildHeatMap($questionScores, $timings);

        return ReadingAnalytics::query()->updateOrCreate(
            ['test_attempt_id' => $attempt->id],
            [
                'result_id' => $result->id,
                'test_id' => $attempt->test_id,
                'user_id' => $attempt->user_id,
                'band' => $result->overall_band,
                'accuracy_percent' => $accuracy,
                'average_time_seconds' => $averageTime,
                'skipped_count' => $skipped,
                'total_questions' => $questionScores->count(),
                'time_per_question' => $timePerQuestion,
                'heat_map' => $heatMap,
                'metadata' => [
                    'raw_score' => (float) $result->raw_score,
                    'max_score' => (float) $result->max_score,
                ],
                'computed_at' => now(),
            ]
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildTimePerQuestion(
        Collection $questionScores,
        Collection $timings,
        Collection $answers,
    ): array {
        $timingByQuestion = $timings->keyBy('question_id');
        $answersByQuestion = $answers->keyBy('question_id');

        return $questionScores->map(function (ResultQuestionScore $score) use ($timingByQuestion, $answersByQuestion): array {
            /** @var ReadingQuestionTiming|null $timing */
            $timing = $timingByQuestion->get($score->question_id);
            /** @var StudentAnswer|null $answer */
            $answer = $answersByQuestion->get($score->question_id);

            return [
                'question_id' => $score->question_id,
                'question_number' => $score->question_number,
                'question_type' => $score->question_type->value,
                'time_spent_seconds' => (int) ($timing?->time_spent_seconds ?? 0),
                'visit_count' => (int) ($timing?->visit_count ?? 0),
                'is_correct' => $score->is_correct,
                'is_skipped' => blank($score->student_response),
                'is_flagged' => (bool) ($answer?->is_flagged ?? false),
                'accuracy_percent' => $score->is_correct
                    ? 100
                    : round(((float) $score->partial_ratio) * 100, 2),
            ];
        })->values()->all();
    }

    /**
     * @param  list<array<string, mixed>>  $timePerQuestion
     */
    private function averageTime(array $timePerQuestion): int
    {
        if ($timePerQuestion === []) {
            return 0;
        }

        $total = array_sum(array_column($timePerQuestion, 'time_spent_seconds'));

        return (int) round($total / count($timePerQuestion));
    }

    /**
     * @param  Collection<int, ResultQuestionScore>  $questionScores
     */
    private function skippedQuestions(Collection $questionScores): int
    {
        return $questionScores->filter(fn (ResultQuestionScore $score): bool => blank($score->student_response))->count();
    }

    private function heatTone(ResultQuestionScore $score, int $timeSpent): string
    {
        if (blank($score->student_response)) {
            return 'high';
        }

        if ($score->is_correct && $timeSpent <= 60) {
            return 'low';
        }

        if ($score->is_correct) {
            return 'medium';
        }

        return 'high';
    }
}
