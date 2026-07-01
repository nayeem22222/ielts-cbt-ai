<?php

declare(strict_types=1);

namespace App\Services\Listening\Result;

use App\Enums\Listening\ListeningAttemptStatus;
use App\Enums\Listening\ListeningMatchStatus;
use App\Enums\Listening\ListeningQuestionType;
use App\Enums\Listening\ListeningResultStatus;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningResult;
use App\Models\Listening\ListeningSection;
use App\Models\User;
use App\Services\Listening\Review\ListeningAudioReviewService;
use App\Services\Listening\Review\ListeningReviewService;
use App\Services\Listening\Review\ListeningReviewVisibilityService;
use App\Services\Listening\Review\ListeningTranscriptHighlightService;
use App\Services\Listening\Student\ListeningAudioAccessService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class ListeningResultPageService
{
    public function __construct(
        private readonly ListeningResultService $results,
        private readonly ListeningResultAnalyticsService $analytics,
        private readonly ListeningReviewService $reviews,
        private readonly ListeningReviewVisibilityService $reviewVisibility,
        private readonly ListeningAudioReviewService $audioReview,
        private readonly ListeningTranscriptHighlightService $transcriptHighlight,
        private readonly ListeningAudioAccessService $audioAccess,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildResultPageData(ListeningAttempt $attempt): array
    {
        $attempt->loadMissing(['test.setting', 'answers']);

        $result = $this->results->ensureResultExistsForAttempt($attempt);

        abort_if($result === null, 404);

        $questionSummary = $this->results->studentViewData($result)['questionSummary'] ?? [];
        $flaggedNumbers = $this->flaggedQuestionNumbers($attempt);
        $reviewParts = $this->buildReviewParts($questionSummary, $attempt->test?->sections ?? collect(), $flaggedNumbers);

        return [
            'attempt' => $attempt,
            'test' => $result->test,
            'result' => $result,
            'summary' => $this->buildSummary($attempt, $result),
            'part_analytics' => $this->analytics->buildPartAnalytics($reviewParts),
            'question_map' => $this->analytics->buildQuestionMap($reviewParts),
            'insights' => $this->analytics->buildWeakAreas($result->question_type_breakdown ?? []),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildReviewPageData(ListeningAttempt $attempt, User $user): array
    {
        $resultData = $this->buildResultPageData($attempt);
        $result = $resultData['result'];

        abort_unless($result instanceof ListeningResult, 404);
        abort_unless($result->status === ListeningResultStatus::Ready, 404);
        abort_unless($this->reviewVisibility->canShowReview($result, $user), 403);

        $reviewPayload = $this->reviews->getReviewForStudent($user, $result);
        $visibility = $reviewPayload['visibility'] ?? [];
        $items = collect($reviewPayload['items'] ?? []);
        $sections = $attempt->test?->sections()->with('transcript')->orderBy('section_number')->get() ?? collect();

        $parts = $this->buildReviewPageParts($items, $sections, $visibility);
        $contextSections = $this->buildContextSections($sections, $items, $result, $visibility);

        return array_merge($resultData, [
            'parts' => $parts,
            'context_sections' => $contextSections,
            'question_map' => $resultData['question_map'],
            'visibility' => $visibility,
        ]);
    }

    public function canViewResult(ListeningAttempt $attempt): bool
    {
        return $attempt->status !== ListeningAttemptStatus::InProgress;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSummary(ListeningAttempt $attempt, ListeningResult $result): array
    {
        $attempted = (int) round((float) $result->total_correct + (float) $result->total_incorrect);
        $durationMinutes = (int) ($result->test?->setting?->duration_minutes
            ?? config('listening.official_flow.total_duration_minutes', 40));

        return [
            'band' => (float) ($result->band_score ?? 0),
            'raw_score' => (float) ($result->raw_score ?? 0),
            'total_questions' => (int) ($result->total_questions ?? 0),
            'time_spent_seconds' => (int) ($result->listening_duration_seconds ?? $attempt->duration_seconds ?? 0),
            'duration_minutes' => $durationMinutes,
            'exam_type' => $result->test?->test_type?->label() ?? 'Academic',
            'attempted' => $attempted,
            'correct' => (int) round((float) $result->total_correct),
            'incorrect' => (int) round((float) $result->total_incorrect),
            'unanswered' => (int) ($result->total_unanswered ?? 0),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $questionSummary
     * @param  Collection<int, ListeningSection>  $sections
     * @param  list<int>  $flaggedNumbers
     * @return list<array<string, mixed>>
     */
    private function buildReviewParts(array $questionSummary, Collection $sections, array $flaggedNumbers): array
    {
        $sectionTitles = $sections->keyBy('section_number')->map(
            fn (ListeningSection $section): string => (string) ($section->title ?: 'Section '.(int) $section->section_number),
        );

        $grouped = collect($questionSummary)->groupBy('section_number');
        $parts = [];

        foreach ($sections->sortBy('section_number') as $section) {
            $sectionNumber = (int) $section->section_number;
            $questions = [];

            foreach ($grouped->get($sectionNumber, collect()) as $row) {
                $questionNumber = (int) ($row['question_number'] ?? 0);
                $questions[] = [
                    'question_id' => $row['question_id'] ?? null,
                    'question_number' => $questionNumber,
                    'section_id' => $section->id,
                    'question_type' => $row['question_type'] ?? 'unknown',
                    'question_type_label' => $this->questionTypeLabel((string) ($row['question_type'] ?? '')),
                    'status' => $this->normalizeStatus((string) ($row['match_status'] ?? 'unanswered')),
                    'flagged' => in_array($questionNumber, $flaggedNumbers, true),
                ];
            }

            if ($questions === []) {
                continue;
            }

            $parts[] = [
                'section_id' => $section->id,
                'part_number' => $sectionNumber,
                'title' => $sectionTitles->get($sectionNumber, 'Section '.$sectionNumber),
                'questions' => $questions,
            ];
        }

        if ($parts === [] && $questionSummary !== []) {
            $questions = array_map(function (array $row) use ($flaggedNumbers): array {
                $questionNumber = (int) ($row['question_number'] ?? 0);

                return [
                    'question_id' => $row['question_id'] ?? null,
                    'question_number' => $questionNumber,
                    'section_id' => null,
                    'question_type' => $row['question_type'] ?? 'unknown',
                    'question_type_label' => $this->questionTypeLabel((string) ($row['question_type'] ?? '')),
                    'status' => $this->normalizeStatus((string) ($row['match_status'] ?? 'unanswered')),
                    'flagged' => in_array($questionNumber, $flaggedNumbers, true),
                ];
            }, $questionSummary);

            $parts[] = [
                'section_id' => null,
                'part_number' => 1,
                'title' => 'Listening',
                'questions' => $questions,
            ];
        }

        return $parts;
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @param  Collection<int, ListeningSection>  $sections
     * @param  array<string, mixed>  $visibility
     * @return list<array<string, mixed>>
     */
    private function buildReviewPageParts(Collection $items, Collection $sections, array $visibility): array
    {
        $grouped = $items->groupBy('section_number');
        $parts = [];

        foreach ($sections->sortBy('section_number') as $section) {
            $sectionNumber = (int) $section->section_number;
            $questions = [];

            foreach ($grouped->get($sectionNumber, collect()) as $item) {
                $item = is_array($item) ? $item : (array) $item;
                $status = $this->normalizeStatus((string) ($item['match_status'] ?? 'unanswered'));

                $questions[] = [
                    'question_number' => (int) ($item['question_number'] ?? 0),
                    'question_type' => $item['question_type'] ?? 'unknown',
                    'question_type_label' => $this->questionTypeLabel((string) ($item['question_type'] ?? '')),
                    'status' => $status,
                    'student_answer_display' => $item['student_answer'] ?? '—',
                    'correct_answer_display' => ($visibility['can_show_correct_answer'] ?? false)
                        ? ($item['correct_answer'] ?? '—')
                        : '—',
                    'explanation' => ($visibility['can_show_explanation'] ?? false)
                        ? ($item['explanation'] ?? null)
                        : null,
                    'section_id' => $section->id,
                    'highlighted_transcript' => $item['highlighted_transcript'] ?? null,
                    'transcript_text_snippet' => $item['transcript_text_snippet'] ?? null,
                    'audio_timestamp_start' => $item['audio_timestamp_start'] ?? null,
                    'audio_timestamp_end' => $item['audio_timestamp_end'] ?? null,
                ];
            }

            if ($questions === []) {
                continue;
            }

            $parts[] = [
                'section_id' => $section->id,
                'part_number' => $sectionNumber,
                'title' => $section->title ?: 'Section '.$sectionNumber,
                'questions' => $questions,
            ];
        }

        return $parts;
    }

    /**
     * @param  Collection<int, ListeningSection>  $sections
     * @param  Collection<int, mixed>  $items
     * @param  array<string, mixed>  $visibility
     * @return list<array<string, mixed>>
     */
    private function buildContextSections(
        Collection $sections,
        Collection $items,
        ListeningResult $result,
        array $visibility,
    ): array {
        $canShowTranscript = (bool) ($visibility['can_show_transcript_highlight'] ?? false);
        $canShowAudio = (bool) ($visibility['can_show_audio_review'] ?? false);
        $context = [];

        foreach ($sections->sortBy('section_number') as $section) {
            $sectionItems = $items->where('section_number', $section->section_number);
            $firstItem = $sectionItems->first();
            $firstItem = is_array($firstItem) ? $firstItem : (is_object($firstItem) ? (array) $firstItem : []);

            $transcriptHtml = null;
            if ($canShowTranscript) {
                if (! empty($firstItem['highlighted_transcript'])) {
                    $transcriptHtml = $this->renderHighlightedTranscript($firstItem['highlighted_transcript']);
                } elseif ($section->transcript !== null) {
                    $transcriptHtml = nl2br(e((string) $section->transcript->content));
                }
            }

            $audioUrl = null;
            if ($canShowAudio && $this->audioReview->canStudentReviewAudio($result) && $result->attempt !== null) {
                if ($this->audioAccess->sectionHasPlayableAudio($section)) {
                    $ttl = (int) config('listening.review.audio_review_signed_url_ttl_minutes', 30);
                    $audioUrl = URL::temporarySignedRoute(
                        'student.listening.results.review.audio',
                        now()->addMinutes($ttl),
                        [
                            'result' => $result->id,
                            'section' => $section->section_number,
                        ],
                    );
                }
            }

            $context[] = [
                'id' => $section->id,
                'part_number' => (int) $section->section_number,
                'title' => $section->title,
                'transcript_html' => $transcriptHtml,
                'audio_url' => $audioUrl,
            ];
        }

        return $context;
    }

    /**
     * @param  array<string, mixed>  $highlighted
     */
    private function renderHighlightedTranscript(array $highlighted): string
    {
        $sanitized = $this->transcriptHighlight->sanitizeTranscriptForStudent($highlighted);
        $lines = $sanitized['lines'] ?? [];
        $html = '';

        foreach ($lines as $line) {
            $classes = ! empty($line['highlighted']) ? 'bg-yellow-100' : '';
            $speaker = ! empty($line['speaker']) ? '<span class="font-medium">'.e((string) $line['speaker']).':</span> ' : '';
            $text = e((string) ($line['text'] ?? ''));
            $html .= '<p class="rounded px-2 py-1 '.$classes.'">'.$speaker.$text.'</p>';
        }

        return $html;
    }

    /**
     * @return list<int>
     */
    private function flaggedQuestionNumbers(ListeningAttempt $attempt): array
    {
        return $attempt->answers
            ->filter(fn ($answer): bool => (bool) ($answer->meta['is_flagged'] ?? false))
            ->map(fn ($answer): int => (int) $answer->question_number)
            ->sort()
            ->values()
            ->all();
    }

    private function normalizeStatus(string $matchStatus): string
    {
        return match ($matchStatus) {
            ListeningMatchStatus::Correct->value, 'correct' => 'correct',
            ListeningMatchStatus::Incorrect->value, 'incorrect', 'partial' => 'incorrect',
            default => 'unanswered',
        };
    }

    private function questionTypeLabel(string $questionType): string
    {
        return ListeningQuestionType::tryFrom($questionType)?->label() ?? ucfirst(str_replace('_', ' ', $questionType));
    }
}
