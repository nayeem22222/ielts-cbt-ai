<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Result;

final readonly class ListeningSectionBreakdownData
{
    public function __construct(
        public int $sectionNumber,
        public string $questionRange,
        public int $totalQuestions,
        public float $correct,
        public float $incorrect,
        public int $unanswered,
        public float $score,
        public float $percentage,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'section_number' => $this->sectionNumber,
            'question_range' => $this->questionRange,
            'total_questions' => $this->totalQuestions,
            'correct' => $this->correct,
            'incorrect' => $this->incorrect,
            'unanswered' => $this->unanswered,
            'score' => $this->score,
            'percentage' => $this->percentage,
        ];
    }
}
