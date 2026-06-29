<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Student;

final readonly class ListeningQuestionPayloadData
{
    /**
     * @param  array<string, mixed>|null  $options
     * @param  array<string, mixed>|null  $studentAnswer
     */
    public function __construct(
        public int $id,
        public int $questionNumber,
        public string $questionType,
        public ?string $questionText,
        public ?string $questionHtml,
        public ?string $instruction,
        public ?array $options,
        public ?int $wordLimit,
        public ?array $studentAnswer,
        public string $answerStatus,
        public bool $isFlagged,
        public int $groupId,
        public int $sectionNumber,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'question_number' => $this->questionNumber,
            'question_type' => $this->questionType,
            'question_text' => $this->questionText,
            'question_html' => $this->questionHtml,
            'instruction' => $this->instruction,
            'options' => $this->options,
            'word_limit' => $this->wordLimit,
            'student_answer' => $this->studentAnswer,
            'answer_status' => $this->answerStatus,
            'is_flagged' => $this->isFlagged,
            'group_id' => $this->groupId,
            'section_number' => $this->sectionNumber,
        ];
    }
}
