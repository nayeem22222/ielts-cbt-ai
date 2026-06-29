<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Student;

final readonly class QuestionPaletteItemData
{
    public function __construct(
        public int $questionId,
        public int $questionNumber,
        public int $sectionNumber,
        public string $status,
        public bool $isAnswered,
        public bool $isFlagged,
        public bool $isCurrent,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'question_id' => $this->questionId,
            'question_number' => $this->questionNumber,
            'section_number' => $this->sectionNumber,
            'status' => $this->status,
            'is_answered' => $this->isAnswered,
            'is_flagged' => $this->isFlagged,
            'is_current' => $this->isCurrent,
            'number' => $this->questionNumber,
        ];
    }
}
