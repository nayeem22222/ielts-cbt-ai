<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Student;

final readonly class NavigationStateData
{
    /**
     * @param  list<array<string, mixed>>  $palette
     */
    public function __construct(
        public int $currentSectionNumber,
        public int $currentQuestionNumber,
        public ?int $nextQuestionNumber,
        public ?int $previousQuestionNumber,
        public array $palette,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'current_section_number' => $this->currentSectionNumber,
            'current_question_number' => $this->currentQuestionNumber,
            'next_question_number' => $this->nextQuestionNumber,
            'previous_question_number' => $this->previousQuestionNumber,
            'palette' => $this->palette,
        ];
    }
}
