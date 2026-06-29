<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\Enums\Listening\ListeningAttemptStatus;
use App\Models\Listening\ListeningAttempt;

class ListeningReviewService
{
    public function __construct(
        private readonly ListeningQuestionPaletteService $palette,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildReviewSummary(ListeningAttempt $attempt): array
    {
        $attempt->loadMissing(['test.sections', 'answers']);
        $palette = $this->palette->build($attempt);
        $currentQuestion = (int) ($attempt->current_question_number ?: 1);

        $answered = 0;
        $unanswered = 0;
        $flagged = 0;
        $parts = [];
        $unansweredNumbers = [];
        $flaggedNumbers = [];

        foreach ($attempt->test?->sections()->where('is_active', true)->orderBy('section_number')->get() ?? [] as $section) {
            $partQuestions = [];

            foreach ($palette as $item) {
                if ((int) ($item['section_number'] ?? 0) !== (int) $section->section_number) {
                    continue;
                }

                $number = (int) ($item['question_number'] ?? 0);
                $isAnswered = (bool) ($item['is_answered'] ?? false);
                $isFlagged = (bool) ($item['is_flagged'] ?? false);
                $isCurrent = (int) $number === $currentQuestion;

                if ($isAnswered) {
                    $answered++;
                } else {
                    $unanswered++;
                    $unansweredNumbers[] = $number;
                }

                if ($isFlagged) {
                    $flagged++;
                    $flaggedNumbers[] = $number;
                }

                $status = (string) ($item['status'] ?? 'unanswered');
                if ($isCurrent) {
                    $status = 'current';
                } elseif ($isFlagged && $isAnswered) {
                    $status = 'answered-flagged';
                } elseif ($isFlagged) {
                    $status = 'flagged';
                } elseif ($isAnswered) {
                    $status = 'answered';
                } else {
                    $status = 'unanswered';
                }

                $partQuestions[] = [
                    'question_id' => (int) ($item['question_id'] ?? 0),
                    'question_number' => $number,
                    'answered' => $isAnswered,
                    'flagged' => $isFlagged,
                    'current' => $isCurrent,
                    'status' => $status,
                    'section_number' => (int) $section->section_number,
                ];
            }

            $parts[] = [
                'section_id' => $section->id,
                'part_label' => 'Part '.(int) $section->section_number,
                'title' => $section->title,
                'question_range' => 'Questions '.(int) $section->start_question_number.'–'.(int) $section->end_question_number,
                'questions' => $partQuestions,
            ];
        }

        sort($unansweredNumbers);
        sort($flaggedNumbers);

        $total = count($palette);

        return [
            'summary' => [
                'total' => $total,
                'answered' => $answered,
                'unanswered' => $unanswered,
                'flagged' => $flagged,
                'not_visited' => max(0, $total - $answered),
            ],
            'parts' => $parts,
            'unanswered_numbers' => $unansweredNumbers,
            'flagged_numbers' => $flaggedNumbers,
        ];
    }

    public function attemptIsPlayable(ListeningAttempt $attempt): bool
    {
        return $attempt->status === ListeningAttemptStatus::InProgress;
    }
}
