<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\Enums\Listening\ListeningAnswerStatus;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAttemptAnswer;
use Illuminate\Support\Collection;

class ListeningNavigationService
{
    /**
     * @param  Collection<int, ListeningAttemptAnswer>  $answers
     * @return list<array{number: int, status: string, is_current: bool}>
     */
    public function buildPalette(ListeningAttempt $attempt, Collection $answers, int $currentQuestionNumber): array
    {
        $palette = [];

        for ($number = 1; $number <= (int) $attempt->total_questions; $number++) {
            /** @var ListeningAttemptAnswer|null $answer */
            $answer = $answers->firstWhere('question_number', $number);
            $isFlagged = is_array($answer?->meta) && ($answer->meta['is_flagged'] ?? false) === true;
            $status = 'unanswered';

            if ($number === $currentQuestionNumber) {
                $status = 'current';
            } elseif ($isFlagged) {
                $status = 'flagged';
            } elseif ($answer?->answer_status === ListeningAnswerStatus::Answered) {
                $status = 'answered';
            }

            $palette[] = [
                'number' => $number,
                'status' => $status,
                'is_current' => $number === $currentQuestionNumber,
            ];
        }

        return $palette;
    }

    public function sectionForQuestionNumber(int $questionNumber): int
    {
        return match (true) {
            $questionNumber <= 10 => 1,
            $questionNumber <= 20 => 2,
            $questionNumber <= 30 => 3,
            default => 4,
        };
    }
}
