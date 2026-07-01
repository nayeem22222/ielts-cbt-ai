<?php

declare(strict_types=1);

namespace App\Services\Listening\Result;

use App\DTOs\Listening\Result\ListeningQuestionResultItemData;
use App\DTOs\Listening\Result\ListeningQuestionTypeBreakdownData;
use App\DTOs\Listening\Result\ListeningSectionBreakdownData;
use App\Enums\Listening\ListeningMatchStatus;
use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningAttemptAnswerEvaluation;
use App\Models\Listening\ListeningAttemptEvaluation;
use App\Models\Listening\ListeningSection;
use Illuminate\Support\Collection;

class ListeningResultBreakdownService
{
    /**
     * @param  Collection<int, ListeningAttemptAnswerEvaluation>  $answerEvaluations
     * @param  Collection<int, ListeningSection>  $sections
     * @return list<array<string, mixed>>
     */
    public function buildSectionBreakdown(Collection $answerEvaluations, Collection $sections): array
    {
        $sectionMap = $this->buildSectionMap($sections);
        $grouped = [];

        foreach ($answerEvaluations as $evaluation) {
            $sectionNumber = $this->resolveSectionNumber((int) $evaluation->question_number, $sections);
            $grouped[$sectionNumber][] = $evaluation;
        }

        $breakdown = [];

        foreach ($sectionMap as $sectionNumber => $range) {
            $items = collect($grouped[$sectionNumber] ?? []);
            $totals = $this->summarizeItems($items);

            $breakdown[] = (new ListeningSectionBreakdownData(
                sectionNumber: $sectionNumber,
                questionRange: $range,
                totalQuestions: $totals['total'],
                correct: $totals['correct'],
                incorrect: $totals['incorrect'],
                unanswered: $totals['unanswered'],
                score: $totals['score'],
                percentage: $totals['percentage'],
            ))->toArray();
        }

        usort($breakdown, fn (array $a, array $b): int => $a['section_number'] <=> $b['section_number']);

        return $breakdown;
    }

    /**
     * @param  Collection<int, ListeningAttemptAnswerEvaluation>  $answerEvaluations
     * @return list<array<string, mixed>>
     */
    public function buildQuestionTypeBreakdown(Collection $answerEvaluations): array
    {
        $grouped = $answerEvaluations->groupBy(fn (ListeningAttemptAnswerEvaluation $e): string => (string) $e->question_type);

        $breakdown = [];

        foreach ($grouped as $type => $items) {
            $collection = collect($items);
            $totals = $this->summarizeItems($collection);
            $label = $this->resolveTypeLabel((string) $type);

            $breakdown[] = (new ListeningQuestionTypeBreakdownData(
                questionType: (string) $type,
                label: $label,
                total: $totals['total'],
                correct: $totals['correct'],
                incorrect: $totals['incorrect'],
                score: $totals['score'],
                percentage: $totals['percentage'],
            ))->toArray();
        }

        usort($breakdown, fn (array $a, array $b): int => strcmp($a['question_type'], $b['question_type']));

        return $breakdown;
    }

    /**
     * @param  Collection<int, ListeningAttemptAnswerEvaluation>  $answerEvaluations
     * @param  Collection<int, ListeningSection>  $sections
     * @return list<ListeningQuestionResultItemData>
     */
    public function buildQuestionSummaryItems(Collection $answerEvaluations, Collection $sections): array
    {
        $items = [];

        foreach ($answerEvaluations->sortBy('question_number') as $evaluation) {
            $items[] = new ListeningQuestionResultItemData(
                questionId: (int) $evaluation->listening_question_id,
                questionNumber: (int) $evaluation->question_number,
                sectionNumber: $this->resolveSectionNumber((int) $evaluation->question_number, $sections),
                questionType: (string) $evaluation->question_type,
                studentAnswer: $this->stringifySnapshot($evaluation->student_answer_snapshot),
                normalizedAnswer: $this->stringifyNormalized($evaluation->normalized_student_answer),
                isCorrect: (bool) $evaluation->is_correct,
                marksAwarded: (float) $evaluation->marks_awarded,
                marksAvailable: (float) $evaluation->marks_available,
                matchStatus: $evaluation->match_status?->value ?? ListeningMatchStatus::Unanswered->value,
                matchReason: $evaluation->match_reason,
                correctAnswer: $this->stringifySnapshot($evaluation->correct_answer_snapshot),
                acceptedAnswers: $this->normalizeAcceptedAnswers($evaluation->accepted_answers_snapshot),
                normalizationSteps: $evaluation->normalization_steps,
            );
        }

        return $items;
    }

    /**
     * @param  Collection<int, ListeningAttemptAnswerEvaluation>  $answerEvaluations
     * @return array{total_correct: float, total_incorrect: float, total_unanswered: int, raw_score: float}
     */
    public function calculateTotals(Collection $answerEvaluations): array
    {
        $totalCorrect = 0.0;
        $totalIncorrect = 0.0;
        $totalUnanswered = 0;

        foreach ($answerEvaluations as $evaluation) {
            $status = $evaluation->match_status;

            if ($status === ListeningMatchStatus::Unanswered) {
                $totalUnanswered++;
            } elseif ($status === ListeningMatchStatus::Incorrect) {
                $totalIncorrect += (float) $evaluation->marks_available;
            } else {
                $totalCorrect += (float) $evaluation->marks_awarded;
            }
        }

        return [
            'total_correct' => round($totalCorrect, 2),
            'total_incorrect' => round($totalIncorrect, 2),
            'total_unanswered' => $totalUnanswered,
            'raw_score' => round($totalCorrect, 2),
        ];
    }

    /**
     * @param  Collection<int, ListeningSection>  $sections
     * @return array<int, string>
     */
    private function buildSectionMap(Collection $sections): array
    {
        if ($sections->isEmpty()) {
            return [
                1 => '1-10',
                2 => '11-20',
                3 => '21-30',
                4 => '31-40',
            ];
        }

        $map = [];

        foreach ($sections->sortBy('section_number') as $section) {
            $map[(int) $section->section_number] = sprintf(
                '%d-%d',
                (int) $section->start_question_number,
                (int) $section->end_question_number,
            );
        }

        return $map;
    }

    /**
     * @param  Collection<int, ListeningSection>  $sections
     */
    private function resolveSectionNumber(int $questionNumber, Collection $sections): int
    {
        foreach ($sections as $section) {
            if ($questionNumber >= (int) $section->start_question_number
                && $questionNumber <= (int) $section->end_question_number) {
                return (int) $section->section_number;
            }
        }

        return max(1, (int) ceil($questionNumber / 10));
    }

    /**
     * @param  Collection<int, ListeningAttemptAnswerEvaluation>  $items
     * @return array{total: int, correct: float, incorrect: float, unanswered: int, score: float, percentage: float}
     */
    private function summarizeItems(Collection $items): array
    {
        $total = $items->count();
        $correct = 0.0;
        $incorrect = 0.0;
        $unanswered = 0;

        foreach ($items as $item) {
            $status = $item->match_status;

            if ($status === ListeningMatchStatus::Unanswered) {
                $unanswered++;
            } elseif ($status === ListeningMatchStatus::Incorrect) {
                $incorrect += (float) $item->marks_available;
            } else {
                $correct += (float) $item->marks_awarded;
            }
        }

        $score = round($correct, 2);
        $percentage = $total > 0 ? round(($score / $total) * 100, 2) : 0.0;

        return [
            'total' => $total,
            'correct' => round($correct, 2),
            'incorrect' => round($incorrect, 2),
            'unanswered' => $unanswered,
            'score' => $score,
            'percentage' => $percentage,
        ];
    }

    private function resolveTypeLabel(string $type): string
    {
        $enum = ListeningQuestionType::tryFrom($type);

        return $enum?->label() ?? ucwords(str_replace('_', ' ', $type));
    }

    /**
     * @param  array<string, mixed>|list<mixed>|null  $snapshot
     */
    private function stringifySnapshot(?array $snapshot): ?string
    {
        if ($snapshot === null || $snapshot === []) {
            return null;
        }

        if (isset($snapshot['value']) && is_scalar($snapshot['value'])) {
            return (string) $snapshot['value'];
        }

        if (array_is_list($snapshot)) {
            $parts = [];

            foreach ($snapshot as $item) {
                if (is_scalar($item)) {
                    $parts[] = (string) $item;
                } elseif (is_array($item)) {
                    $parts[] = (string) ($item['value'] ?? $item['label'] ?? json_encode($item));
                }
            }

            return $parts === [] ? null : implode(', ', $parts);
        }

        return json_encode($snapshot);
    }

    /**
     * @param  array<string, mixed>|list<mixed>|null  $normalized
     */
    private function stringifyNormalized(?array $normalized): ?string
    {
        if ($normalized === null || $normalized === []) {
            return null;
        }

        if (isset($normalized['value']) && is_scalar($normalized['value'])) {
            return (string) $normalized['value'];
        }

        if (array_is_list($normalized)) {
            return implode(', ', array_map(
                fn (mixed $v): string => is_scalar($v) ? (string) $v : json_encode($v),
                $normalized,
            ));
        }

        return json_encode($normalized);
    }

    /**
     * @param  array<string, mixed>|list<mixed>|null  $accepted
     * @return list<mixed>
     */
    private function normalizeAcceptedAnswers(?array $accepted): array
    {
        if ($accepted === null || $accepted === []) {
            return [];
        }

        return array_is_list($accepted) ? $accepted : [$accepted];
    }
}
