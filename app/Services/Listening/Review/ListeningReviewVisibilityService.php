<?php

declare(strict_types=1);

namespace App\Services\Listening\Review;

use App\DTOs\Listening\Review\ListeningReviewVisibilityData;
use App\Enums\Listening\ListeningAttemptStatus;
use App\Enums\Listening\ListeningResultStatus;
use App\Models\Listening\ListeningResult;
use App\Models\Listening\ListeningReviewItem;
use App\Models\Listening\ListeningTestSetting;
use App\Models\User;

class ListeningReviewVisibilityService
{
    public function canShowReview(ListeningResult $result, User $user, bool $forAdmin = false): bool
    {
        if ($result->status !== ListeningResultStatus::Ready) {
            return false;
        }

        $attempt = $result->attempt;

        if ($attempt === null || $attempt->status === ListeningAttemptStatus::InProgress) {
            return false;
        }

        if ($forAdmin) {
            return true;
        }

        return (int) $result->user_id === (int) $user->id
            && $result->is_visible_to_student
            && (bool) config('listening.review.enabled', true);
    }

    public function canShowCorrectAnswer(ListeningResult $result, bool $forAdmin = false): bool
    {
        if ($forAdmin) {
            return true;
        }

        $settings = $result->test?->setting;

        if ($settings !== null) {
            return (bool) $settings->show_correct_answer;
        }

        return (bool) config('listening.review.show_correct_answer_default', true);
    }

    public function canShowAcceptedAnswersToStudent(ListeningResult $result): bool
    {
        return (bool) config('listening.review.show_accepted_answers_to_students', false);
    }

    public function canShowTranscriptHighlight(ListeningResult $result, bool $forAdmin = false): bool
    {
        if ($forAdmin) {
            return true;
        }

        $settings = $result->test?->setting;

        if ($settings !== null && ! $settings->show_transcript_after_submit) {
            return false;
        }

        return (bool) config('listening.review.show_transcript_highlight_default', false);
    }

    public function canShowAudioReview(ListeningResult $result, bool $forAdmin = false): bool
    {
        if ($forAdmin) {
            return true;
        }

        $settings = $result->test?->setting;

        if ($settings !== null && ! $settings->show_audio_review) {
            return false;
        }

        return (bool) config('listening.review.show_audio_review_default', false);
    }

    public function canShowExplanation(ListeningResult $result, bool $forAdmin = false): bool
    {
        if ($forAdmin) {
            return true;
        }

        return (bool) config('listening.review.show_explanation_default', true);
    }

    public function resolveVisibilityData(ListeningResult $result, bool $forAdmin = false): ListeningReviewVisibilityData
    {
        return new ListeningReviewVisibilityData(
            canShowReview: true,
            canShowCorrectAnswer: $this->canShowCorrectAnswer($result, $forAdmin),
            canShowAcceptedAnswers: $forAdmin || $this->canShowAcceptedAnswersToStudent($result),
            canShowTranscriptHighlight: $this->canShowTranscriptHighlight($result, $forAdmin),
            canShowAudioReview: $this->canShowAudioReview($result, $forAdmin),
            canShowExplanation: $this->canShowExplanation($result, $forAdmin),
            allowStudentCopyTranscript: (bool) config('listening.review.allow_student_copy_transcript', false),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function filterItemForStudent(ListeningReviewItem $item, ListeningResult $result): array
    {
        $visibility = $this->resolveVisibilityData($result, forAdmin: false);

        $data = [
            'id' => $item->id,
            'question_number' => $item->question_number,
            'section_number' => $item->section_number,
            'question_type' => $item->question_type,
            'student_answer' => $this->stringifySnapshot($item->student_answer_snapshot),
            'match_status' => $item->match_status,
            'marks_awarded' => (float) $item->marks_awarded,
            'marks_available' => (float) $item->marks_available,
            'is_correct' => $item->match_status === 'correct',
        ];

        if ($visibility->canShowCorrectAnswer) {
            $data['correct_answer'] = $this->stringifySnapshot($item->correct_answer_snapshot);
        }

        if ($visibility->canShowExplanation && $item->explanation) {
            $data['explanation'] = $item->explanation;
        }

        if ($visibility->canShowTranscriptHighlight && $item->highlighted_transcript) {
            $data['highlighted_transcript'] = app(ListeningTranscriptHighlightService::class)
                ->sanitizeTranscriptForStudent($item->highlighted_transcript);
            $data['transcript_text_snippet'] = $item->transcript_text_snippet;
        }

        if ($visibility->canShowAudioReview) {
            $data['audio_timestamp_start'] = $item->audio_timestamp_start;
            $data['audio_timestamp_end'] = $item->audio_timestamp_end;
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function filterItemForAdmin(ListeningReviewItem $item): array
    {
        return [
            'id' => $item->id,
            'question_number' => $item->question_number,
            'section_number' => $item->section_number,
            'question_type' => $item->question_type,
            'student_answer_snapshot' => $item->student_answer_snapshot,
            'correct_answer_snapshot' => $item->correct_answer_snapshot,
            'accepted_answers_snapshot' => $item->accepted_answers_snapshot,
            'normalized_answer_snapshot' => $item->normalized_answer_snapshot,
            'match_status' => $item->match_status,
            'match_reason' => $item->match_reason,
            'marks_awarded' => (float) $item->marks_awarded,
            'marks_available' => (float) $item->marks_available,
            'transcript_line_start' => $item->transcript_line_start,
            'transcript_line_end' => $item->transcript_line_end,
            'transcript_text_snippet' => $item->transcript_text_snippet,
            'highlighted_transcript' => $item->highlighted_transcript,
            'audio_timestamp_start' => $item->audio_timestamp_start,
            'audio_timestamp_end' => $item->audio_timestamp_end,
            'explanation' => $item->explanation,
            'visibility_meta' => $item->visibility_meta,
            'admin_meta' => $item->admin_meta,
        ];
    }

  /**
     * @param  array<string, mixed>|list<mixed>|null  $snapshot
     */
    private function stringifySnapshot(?array $snapshot): ?string
    {
        if ($snapshot === null || $snapshot === []) {
            return null;
        }

        if (isset($snapshot['value']) && is_scalar($snapshot['value'])) {
            return (string) $snapshot['value'];
        }

        if (array_is_list($snapshot)) {
            $parts = [];

            foreach ($snapshot as $item) {
                if (is_scalar($item)) {
                    $parts[] = (string) $item;
                } elseif (is_array($item)) {
                    $parts[] = (string) ($item['value'] ?? $item['label'] ?? json_encode($item));
                }
            }

            return $parts === [] ? null : implode(', ', $parts);
        }

        return json_encode($snapshot);
    }
}
