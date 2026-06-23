<?php

declare(strict_types=1);

namespace App\Services\Exam\Scoring;

use App\Models\Result;
use App\Models\ResultQuestionScore;
use App\Models\ResultStatistics;
use App\Models\StudentAnswer;
use App\Models\TestAttempt;
use Illuminate\Support\Collection;

class ReadingResultStatisticsBuilder
{
    /**
     * @param  Collection<int, ResultQuestionScore>  $questionScores
     */
    public function build(Result $result, TestAttempt $attempt, Collection $questionScores): ResultStatistics
    {
        $answers = $attempt->answers()->get()->keyBy('question_id');
        $totalQuestions = $questionScores->count();
        $answeredCount = $answers->filter(fn (StudentAnswer $answer): bool => filled($answer->answer_text) || filled($answer->selected_options))->count();
        $correctCount = $questionScores->where('is_correct', true)->count();
        $partialCount = $questionScores->filter(fn (ResultQuestionScore $score): bool => (float) $score->partial_ratio > 0 && ! $score->is_correct)->count();
        $rawScore = round((float) $questionScores->sum('score_awarded'), 2);
        $maxScore = round((float) $questionScores->sum('max_score'), 2);

        return ResultStatistics::query()->updateOrCreate(
            ['result_id' => $result->id],
            [
                'total_questions' => $totalQuestions,
                'answered_count' => $answeredCount,
                'correct_count' => $correctCount,
                'incorrect_count' => $questionScores->filter(
                    fn (ResultQuestionScore $score): bool => (float) $score->score_awarded <= 0 && filled($score->student_response)
                )->count(),
                'unanswered_count' => max(0, $totalQuestions - $answeredCount),
                'flagged_count' => $answers->where('is_flagged', true)->count(),
                'partial_count' => $partialCount,
                'raw_score' => $rawScore,
                'max_score' => $maxScore,
                'accuracy_percent' => $maxScore > 0 ? round(($rawScore / $maxScore) * 100, 2) : 0,
                'by_question_type' => $this->groupByQuestionType($questionScores),
                'by_passage' => $this->groupByPassage($questionScores),
            ]
        );
    }

    /**
     * @param  Collection<int, ResultQuestionScore>  $questionScores
     * @return array<string, array<string, int|float>>
     */
    private function groupByQuestionType(Collection $questionScores): array
    {
        $groups = [];

        foreach ($questionScores->groupBy(fn (ResultQuestionScore $score): string => $score->question_type->value) as $type => $items) {
            $groups[$type] = [
                'label' => $items->first()->question_type->label(),
                'total' => $items->count(),
                'correct' => $items->where('is_correct', true)->count(),
                'raw_score' => round((float) $items->sum('score_awarded'), 2),
                'max_score' => round((float) $items->sum('max_score'), 2),
            ];
        }

        return $groups;
    }

    /**
     * @param  Collection<int, ResultQuestionScore>  $questionScores
     * @return array<string, array<string, int|float|string|null>>
     */
    private function groupByPassage(Collection $questionScores): array
    {
        $groups = [];

        foreach ($questionScores->groupBy('test_section_id') as $sectionId => $items) {
            $section = $items->first()?->section;
            $key = (string) $sectionId;

            $groups[$key] = [
                'section_id' => $section?->id,
                'title' => $section?->title,
                'total' => $items->count(),
                'correct' => $items->where('is_correct', true)->count(),
                'raw_score' => round((float) $items->sum('score_awarded'), 2),
                'max_score' => round((float) $items->sum('max_score'), 2),
            ];
        }

        return $groups;
    }
}
