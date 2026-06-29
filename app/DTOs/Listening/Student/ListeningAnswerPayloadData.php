<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Student;

final readonly class ListeningAnswerPayloadData
{
    /**
     * @param  list<array<string, mixed>>|null  $studentAnswer
     */
    public function __construct(
        public int $questionId,
        public int $questionNumber,
        public ?array $studentAnswer,
        public string $answerStatus,
        public bool $isFlagged,
        public ?string $answeredAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'question_id' => $this->questionId,
            'question_number' => $this->questionNumber,
            'student_answer' => $this->studentAnswer,
            'answer_status' => $this->answerStatus,
            'is_flagged' => $this->isFlagged,
            'answered_at' => $this->answeredAt,
        ];
    }
}
