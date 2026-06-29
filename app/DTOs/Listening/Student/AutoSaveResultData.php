<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Student;

final readonly class AutoSaveResultData
{
    /**
     * @param  list<array<string, mixed>>  $palette
     * @param  array<string, mixed>  $navigation
     */
    public function __construct(
        public bool $success,
        public bool $skipped,
        public int $questionId,
        public int $questionNumber,
        public string $answerStatus,
        public int $totalAnswered,
        public array $palette,
        public array $navigation,
        public string $savedAt,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'skipped' => $this->skipped,
            'question_id' => $this->questionId,
            'question_number' => $this->questionNumber,
            'answer_status' => $this->answerStatus,
            'total_answered' => $this->totalAnswered,
            'palette' => $this->palette,
            'navigation' => $this->navigation,
            'saved_at' => $this->savedAt,
        ];
    }
}
