<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Review;

final readonly class ListeningReviewVisibilityData
{
    public function __construct(
        public bool $canShowReview,
        public bool $canShowCorrectAnswer,
        public bool $canShowAcceptedAnswers,
        public bool $canShowTranscriptHighlight,
        public bool $canShowAudioReview,
        public bool $canShowExplanation,
        public bool $allowStudentCopyTranscript,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'can_show_review' => $this->canShowReview,
            'can_show_correct_answer' => $this->canShowCorrectAnswer,
            'can_show_accepted_answers' => $this->canShowAcceptedAnswers,
            'can_show_transcript_highlight' => $this->canShowTranscriptHighlight,
            'can_show_audio_review' => $this->canShowAudioReview,
            'can_show_explanation' => $this->canShowExplanation,
            'allow_student_copy_transcript' => $this->allowStudentCopyTranscript,
        ];
    }
}
