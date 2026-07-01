<?php

declare(strict_types=1);

namespace App\Services\Listening\Review;

use App\Actions\Listening\Review\BuildAudioTimestampReviewAction;
use App\Actions\Listening\Review\BuildTranscriptHighlightAction;
use App\DTOs\Listening\Review\ListeningReviewItemData;
use App\Enums\Listening\ListeningReviewVisibility;
use App\Models\Listening\ListeningAttemptAnswerEvaluation;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningResult;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTranscript;
use Illuminate\Support\Collection;

class ListeningReviewBuilderService
{
    public function __construct(
        private readonly BuildTranscriptHighlightAction $buildTranscriptHighlight,
        private readonly BuildAudioTimestampReviewAction $buildAudioTimestamp,
        private readonly ListeningReviewVisibilityService $visibility,
    ) {}

    /**
     * @return list<ListeningReviewItemData>
     */
    public function buildForResult(ListeningResult $result): array
    {
        $result->loadMissing([
            'evaluation.answerEvaluations.question.section',
            'evaluation.answerEvaluations.question.group',
            'test.setting',
        ]);

        $evaluation = $result->evaluation;

        if ($evaluation === null) {
            return [];
        }

        $items = [];

        foreach ($evaluation->answerEvaluations->sortBy('question_number') as $answerEvaluation) {
            $items[] = $this->buildItem($result, $answerEvaluation);
        }

        return $items;
    }

    public function buildItem(
        ListeningResult $result,
        ListeningAttemptAnswerEvaluation $answerEvaluation,
    ): ListeningReviewItemData {
        $question = $answerEvaluation->question;
        $section = $question ? $this->resolveSection($question) : null;
        $sectionNumber = $section?->section_number ?? $this->guessSectionNumber((int) $answerEvaluation->question_number, $result);

        $transcriptRef = $question ? $this->resolveTranscriptReference($question, $section) : [];
        $audioRef = $question ? $this->resolveAudioTimestamp($question, $transcriptRef) : ['start' => null, 'end' => null];

        $transcript = isset($transcriptRef['transcript_id'])
            ? ListeningTranscript::query()->find((int) $transcriptRef['transcript_id'])
            : ($section?->transcript_id ? ListeningTranscript::query()->find((int) $section->transcript_id) : null);

        $highlight = $this->buildTranscriptHighlight->execute(
            $transcript,
            $transcriptRef['line_start'] ?? null,
            $transcriptRef['line_end'] ?? null,
            $transcriptRef['text_snippet'] ?? null,
        );

        $audioTimestamps = $this->buildAudioTimestamp->execute($audioRef);

        $visibilityMeta = $this->visibility->resolveVisibilityData($result, forAdmin: false)->toArray();

        return new ListeningReviewItemData(
            resultId: (int) $result->id,
            attemptId: (int) $result->listening_attempt_id,
            evaluationId: (int) $result->listening_attempt_evaluation_id,
            answerEvaluationId: (int) $answerEvaluation->id,
            questionId: $question?->id,
            sectionId: $section?->id,
            transcriptId: $transcript?->id,
            questionNumber: (int) $answerEvaluation->question_number,
            sectionNumber: $sectionNumber,
            questionType: (string) $answerEvaluation->question_type,
            studentAnswerSnapshot: $answerEvaluation->student_answer_snapshot,
            correctAnswerSnapshot: $answerEvaluation->correct_answer_snapshot,
            acceptedAnswersSnapshot: $answerEvaluation->accepted_answers_snapshot,
            normalizedAnswerSnapshot: $answerEvaluation->normalized_student_answer,
            matchStatus: $answerEvaluation->match_status?->value,
            matchReason: $answerEvaluation->match_reason,
            marksAwarded: (float) $answerEvaluation->marks_awarded,
            marksAvailable: (float) $answerEvaluation->marks_available,
            transcriptLineStart: $highlight->lineStart,
            transcriptLineEnd: $highlight->lineEnd,
            transcriptTextSnippet: $highlight->textSnippet ?? $transcriptRef['text_snippet'] ?? null,
            highlightedTranscript: $highlight->highlightedJson ?: null,
            audioTimestampStart: $audioTimestamps['start'],
            audioTimestampEnd: $audioTimestamps['end'],
            explanation: $question ? $this->resolveExplanation($question, $answerEvaluation) : null,
            visibilityMeta: array_merge($visibilityMeta, [
                'transcript_visibility' => $highlight->warning
                    ? ListeningReviewVisibility::Hidden->value
                    : ($highlight->highlightedJson !== []
                        ? ListeningReviewVisibility::StudentVisible->value
                        : ListeningReviewVisibility::AdminOnly->value),
            ]),
            adminMeta: [
                'normalization_steps' => $answerEvaluation->normalization_steps,
                'evaluator_meta' => $answerEvaluation->evaluator_meta,
                'transcript_reference' => $transcriptRef,
                'highlight_warning' => $highlight->warning,
                'evaluation_id' => $answerEvaluation->listening_attempt_evaluation_id,
            ],
        );
    }

    public function resolveSection(ListeningQuestion $question): ?ListeningSection
    {
        if ($question->relationLoaded('section') && $question->section !== null) {
            return $question->section;
        }

        return $question->section()->first();
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveTranscriptReference(ListeningQuestion $question, ?ListeningSection $section): array
    {
        $location = $question->transcript_location;

        if (is_array($location) && $location !== []) {
            return $this->normalizeTranscriptRef($location, $section);
        }

        $group = $question->relationLoaded('group') ? $question->group : $question->group()->first();
        $groupRef = $group?->transcript_reference;

        if (is_array($groupRef) && $groupRef !== []) {
            return $this->normalizeTranscriptRef($groupRef, $section);
        }

        if ($section?->transcript_id) {
            return [
                'transcript_id' => (int) $section->transcript_id,
                'source' => 'section',
            ];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $ref
     * @return array{start: ?float, end: ?float}
     */
    public function resolveAudioTimestamp(ListeningQuestion $question, array $ref): array
    {
        if ($question->audio_timestamp_start !== null) {
            return [
                'start' => (float) $question->audio_timestamp_start,
                'end' => $question->audio_timestamp_end !== null ? (float) $question->audio_timestamp_end : null,
            ];
        }

        if (isset($ref['start']) && is_numeric($ref['start'])) {
            return [
                'start' => (float) $ref['start'],
                'end' => isset($ref['end']) && is_numeric($ref['end']) ? (float) $ref['end'] : null,
            ];
        }

        return ['start' => null, 'end' => null];
    }

    public function resolveExplanation(ListeningQuestion $question, ListeningAttemptAnswerEvaluation $evaluation): ?string
    {
        if (filled($question->explanation)) {
            return (string) $question->explanation;
        }

        $meta = $evaluation->evaluator_meta;

        if (is_array($meta) && filled($meta['explanation'] ?? null)) {
            return (string) $meta['explanation'];
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $ref
     * @return array<string, mixed>
     */
    private function normalizeTranscriptRef(array $ref, ?ListeningSection $section): array
    {
        $lineStart = $ref['line_start'] ?? $ref['start_line'] ?? $ref['line'] ?? null;
        $lineEnd = $ref['line_end'] ?? $ref['end_line'] ?? $lineStart;
        $transcriptId = $ref['transcript_id'] ?? $section?->transcript_id;
        $snippet = $ref['text_snippet'] ?? $ref['snippet'] ?? $ref['text'] ?? null;

        $normalized = [
            'transcript_id' => $transcriptId !== null ? (int) $transcriptId : null,
            'line_start' => $lineStart !== null ? (int) $lineStart : null,
            'line_end' => $lineEnd !== null ? (int) $lineEnd : null,
            'text_snippet' => is_string($snippet) ? $snippet : null,
            'source' => $ref['source'] ?? 'question',
        ];

        if (isset($ref['start']) && is_numeric($ref['start'])) {
            $normalized['start'] = (float) $ref['start'];
        }

        if (isset($ref['end']) && is_numeric($ref['end'])) {
            $normalized['end'] = (float) $ref['end'];
        }

        return array_filter($normalized, fn (mixed $v): bool => $v !== null);
    }

    private function guessSectionNumber(int $questionNumber, ListeningResult $result): int
    {
        $sections = $result->test?->sections ?? collect();

        /** @var Collection<int, ListeningSection> $sections */
        foreach ($sections as $section) {
            if ($questionNumber >= (int) $section->start_question_number
                && $questionNumber <= (int) $section->end_question_number) {
                return (int) $section->section_number;
            }
        }

        return max(1, (int) ceil($questionNumber / 10));
    }
}
