<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Result;

final readonly class ListeningQuestionResultItemData
{
    /**
     * @param  list<mixed>  $acceptedAnswers
     * @param  list<mixed>|null  $normalizationSteps
     */
    public function __construct(
        public int $questionId,
        public int $questionNumber,
        public int $sectionNumber,
        public string $questionType,
        public ?string $studentAnswer,
        public ?string $normalizedAnswer,
        public bool $isCorrect,
        public float $marksAwarded,
        public float $marksAvailable,
        public string $matchStatus,
        public ?string $matchReason,
        public ?string $correctAnswer = null,
        public array $acceptedAnswers = [],
        public ?array $normalizationSteps = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toStudentArray(bool $showCorrectAnswer, bool $showAcceptedAnswers): array
    {
        $item = [
            'question_id' => $this->questionId,
            'question_number' => $this->questionNumber,
            'section_number' => $this->sectionNumber,
            'question_type' => $this->questionType,
            'student_answer' => $this->studentAnswer,
            'is_correct' => $this->isCorrect,
            'marks_awarded' => $this->marksAwarded,
            'marks_available' => $this->marksAvailable,
            'match_status' => $this->matchStatus,
        ];

        if ($showCorrectAnswer) {
            $item['correct_answer'] = $this->correctAnswer;
        }

        if ($showAcceptedAnswers) {
            $item['accepted_answers'] = $this->acceptedAnswers;
        }

        return $item;
    }

    /**
     * @return array<string, mixed>
     */
    public function toAdminArray(): array
    {
        return [
            'question_id' => $this->questionId,
            'question_number' => $this->questionNumber,
            'section_number' => $this->sectionNumber,
            'question_type' => $this->questionType,
            'student_answer' => $this->studentAnswer,
            'normalized_answer' => $this->normalizedAnswer,
            'is_correct' => $this->isCorrect,
            'marks_awarded' => $this->marksAwarded,
            'marks_available' => $this->marksAvailable,
            'match_status' => $this->matchStatus,
            'match_reason' => $this->matchReason,
            'correct_answer' => $this->correctAnswer,
            'accepted_answers' => $this->acceptedAnswers,
            'normalization_steps' => $this->normalizationSteps,
        ];
    }
}
