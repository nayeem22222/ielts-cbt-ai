<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Review;

final readonly class ListeningReviewItemData
{
    /**
     * @param  array<string, mixed>|list<mixed>|null  $studentAnswerSnapshot
     * @param  array<string, mixed>|list<mixed>|null  $correctAnswerSnapshot
     * @param  array<string, mixed>|list<mixed>|null  $acceptedAnswersSnapshot
     * @param  array<string, mixed>|list<mixed>|null  $normalizedAnswerSnapshot
     * @param  array<string, mixed>|null  $highlightedTranscript
     * @param  array<string, mixed>  $visibilityMeta
     * @param  array<string, mixed>  $adminMeta
     */
    public function __construct(
        public int $resultId,
        public int $attemptId,
        public ?int $evaluationId,
        public ?int $answerEvaluationId,
        public ?int $questionId,
        public ?int $sectionId,
        public ?int $transcriptId,
        public int $questionNumber,
        public int $sectionNumber,
        public string $questionType,
        public ?array $studentAnswerSnapshot,
        public ?array $correctAnswerSnapshot,
        public ?array $acceptedAnswersSnapshot,
        public ?array $normalizedAnswerSnapshot,
        public ?string $matchStatus,
        public ?string $matchReason,
        public float $marksAwarded,
        public float $marksAvailable,
        public ?int $transcriptLineStart,
        public ?int $transcriptLineEnd,
        public ?string $transcriptTextSnippet,
        public ?array $highlightedTranscript,
        public ?float $audioTimestampStart,
        public ?float $audioTimestampEnd,
        public ?string $explanation,
        public array $visibilityMeta = [],
        public array $adminMeta = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'listening_result_id' => $this->resultId,
            'listening_attempt_id' => $this->attemptId,
            'listening_attempt_evaluation_id' => $this->evaluationId,
            'listening_attempt_answer_evaluation_id' => $this->answerEvaluationId,
            'listening_question_id' => $this->questionId,
            'listening_section_id' => $this->sectionId,
            'listening_transcript_id' => $this->transcriptId,
            'question_number' => $this->questionNumber,
            'section_number' => $this->sectionNumber,
            'question_type' => $this->questionType,
            'student_answer_snapshot' => $this->studentAnswerSnapshot,
            'correct_answer_snapshot' => $this->correctAnswerSnapshot,
            'accepted_answers_snapshot' => $this->acceptedAnswersSnapshot,
            'normalized_answer_snapshot' => $this->normalizedAnswerSnapshot,
            'match_status' => $this->matchStatus,
            'match_reason' => $this->matchReason,
            'marks_awarded' => $this->marksAwarded,
            'marks_available' => $this->marksAvailable,
            'transcript_line_start' => $this->transcriptLineStart,
            'transcript_line_end' => $this->transcriptLineEnd,
            'transcript_text_snippet' => $this->transcriptTextSnippet,
            'highlighted_transcript' => $this->highlightedTranscript,
            'audio_timestamp_start' => $this->audioTimestampStart,
            'audio_timestamp_end' => $this->audioTimestampEnd,
            'explanation' => $this->explanation,
            'visibility_meta' => $this->visibilityMeta,
            'admin_meta' => $this->adminMeta,
        ];
    }
}
