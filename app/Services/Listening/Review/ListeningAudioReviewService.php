<?php

declare(strict_types=1);

namespace App\Services\Listening\Review;

use App\DTOs\Listening\Review\AudioTimestampReviewData;
use App\Models\Listening\ListeningResult;
use App\Models\Listening\ListeningReviewItem;
use App\Services\Listening\Student\ListeningAudioAccessService;
use Illuminate\Support\Facades\URL;

class ListeningAudioReviewService
{
    public function __construct(
        private readonly ListeningReviewVisibilityService $visibility,
        private readonly ListeningAudioAccessService $audioAccess,
    ) {}

    public function canStudentReviewAudio(ListeningResult $result): bool
    {
        return $this->visibility->canShowAudioReview($result, forAdmin: false);
    }

    public function buildAudioReviewPayload(ListeningReviewItem $item, ?string $safeUrl = null): AudioTimestampReviewData
    {
        [$start, $end] = $this->getTimestampRange($item);

        return new AudioTimestampReviewData(
            sectionNumber: (int) $item->section_number,
            startSeconds: $start,
            endSeconds: $end,
            safeAudioUrl: $safeUrl,
            enabled: $start !== null || $safeUrl !== null,
        );
    }

    public function getSafeAudioUrl(ListeningResult $result, ListeningReviewItem $item): ?string
    {
        if (! $this->canStudentReviewAudio($result)) {
            return null;
        }

        $attempt = $result->attempt;

        if ($attempt === null) {
            return null;
        }

        $section = $this->audioAccess->resolveSection($attempt, (int) $item->section_number);

        if ($section === null || ! $this->audioAccess->sectionHasPlayableAudio($section)) {
            return null;
        }

        $ttl = (int) config('listening.review.audio_review_signed_url_ttl_minutes', 30);

        return URL::temporarySignedRoute(
            'student.listening.results.review.audio',
            now()->addMinutes($ttl),
            [
                'result' => $result->id,
                'section' => $item->section_number,
            ],
        );
    }

    /**
     * @return array{0: ?float, 1: ?float}
     */
    public function getTimestampRange(ListeningReviewItem $item): array
    {
        $start = $item->audio_timestamp_start !== null ? (float) $item->audio_timestamp_start : null;
        $end = $item->audio_timestamp_end !== null ? (float) $item->audio_timestamp_end : null;

        return [$start, $end];
    }
}
