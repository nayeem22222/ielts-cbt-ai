<?php

declare(strict_types=1);

namespace App\Services\Listening;

use App\DTOs\Listening\TimestampedTranscriptLineData;
use App\Enums\Listening\ListeningTranscriptVisibility;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTranscript;

class ListeningPassageService
{
    /**
     * @return array<string, mixed>
     */
    public function buildAdminPassagePreview(ListeningTranscript $transcript): array
    {
        $transcript->loadMissing(['audio', 'sections.test']);

        return [
            'title' => $transcript->passage_title ?: $transcript->title,
            'note' => $transcript->passage_note,
            'plain_text' => $this->extractPlainText($transcript),
            'formatted_text' => $transcript->formatted_transcript,
            'timestamp_blocks' => $this->extractTimestampMap($transcript),
            'visibility' => $transcript->visibility?->label(),
            'is_official' => $transcript->is_official,
            'audio' => $transcript->audio?->original_name,
            'attached_sections' => $transcript->sections->map(fn (ListeningSection $section): array => [
                'id' => $section->id,
                'title' => $section->title,
                'section_number' => $section->section_number,
                'test_title' => $section->test?->title,
            ])->all(),
            'admin_warning' => 'This transcript/passage is for admin reference, question creation, review, and future audio synchronization. It must not be visible during the live Listening test.',
            'question_builder_note' => config('listening.passage.allow_question_builder_reference', true)
                ? 'This passage reference can be used by the future question builder.'
                : null,
        ];
    }

    public function extractPlainText(ListeningTranscript $transcript): string
    {
        $text = trim((string) $transcript->transcript_text);

        if ($text !== '') {
            return $text;
        }

        $lines = [];

        foreach ($transcript->timestamped_transcript ?? [] as $entry) {
            $line = TimestampedTranscriptLineData::fromArray($entry);
            $prefix = $line->speaker ? "{$line->speaker}: " : '';

            $lines[] = $prefix.$line->text;
        }

        return implode("\n", $lines);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function extractTimestampMap(ListeningTranscript $transcript): array
    {
        $map = [];

        foreach ($transcript->timestamped_transcript ?? [] as $entry) {
            $line = TimestampedTranscriptLineData::fromArray($entry);
            $map[] = $line->toArray();
        }

        return $map;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function extractSectionReference(ListeningSection $section): ?array
    {
        $section->loadMissing(['transcript.audio', 'audio', 'test']);

        if ($section->transcript === null) {
            return null;
        }

        return [
            'section' => [
                'id' => $section->id,
                'number' => $section->section_number,
                'title' => $section->title,
                'test_title' => $section->test?->title,
            ],
            'transcript' => [
                'id' => $section->transcript->id,
                'title' => $section->transcript->title,
                'visibility' => $section->transcript->visibility?->value,
            ],
            'passage_preview' => $this->buildAdminPassagePreview($section->transcript),
            'audio_match' => $this->audioMatches($section),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function prepareForQuestionBuilder(ListeningSection $section): array
    {
        $reference = $this->extractSectionReference($section);

        return [
            'section_id' => $section->id,
            'has_transcript' => $section->transcript_id !== null,
            'reference' => $reference,
            'plain_text' => $reference ? $reference['passage_preview']['plain_text'] : null,
            'timestamps' => $reference ? $reference['passage_preview']['timestamp_blocks'] : [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function prepareForFutureReview(ListeningTranscript $transcript): array
    {
        $allowReview = config('listening.transcript.allow_review_visibility', true);

        return [
            'transcript_id' => $transcript->id,
            'visibility' => $transcript->visibility?->value,
            'may_show_after_submit' => $allowReview
                && $transcript->visibility === ListeningTranscriptVisibility::ReviewVisible,
            'never_visible_during_live_test' => true,
            'plain_text' => $this->extractPlainText($transcript),
            'timestamp_map' => $this->extractTimestampMap($transcript),
            'readiness' => app(ListeningTranscriptService::class)->getTranscriptReadiness($transcript),
        ];
    }

    private function audioMatches(ListeningSection $section): bool
    {
        if ($section->transcript === null) {
            return true;
        }

        if ($section->audio_id === null || $section->transcript->listening_audio_id === null) {
            return true;
        }

        return (int) $section->audio_id === (int) $section->transcript->listening_audio_id;
    }
}
