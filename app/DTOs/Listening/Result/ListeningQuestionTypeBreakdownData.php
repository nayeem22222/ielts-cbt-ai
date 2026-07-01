<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Result;

final readonly class ListeningQuestionTypeBreakdownData
{
    public function __construct(
        public string $questionType,
        public string $label,
        public int $total,
        public float $correct,
        public float $incorrect,
        public float $score,
        public float $percentage,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'question_type' => $this->questionType,
            'label' => $this->label,
            'total' => $this->total,
            'correct' => $this->correct,
            'incorrect' => $this->incorrect,
            'score' => $this->score,
            'percentage' => $this->percentage,
        ];
    }
}
