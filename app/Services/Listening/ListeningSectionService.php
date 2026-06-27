<?php

declare(strict_types=1);

namespace App\Services\Listening;

use App\Actions\Listening\CreateDefaultListeningSectionsAction;
use App\Actions\Listening\ReorderListeningSectionsAction;
use App\Actions\Listening\ValidateListeningSectionRangeAction;
use App\Enums\Listening\ListeningTestStatus;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Repositories\Listening\ListeningSectionRepository;
use App\Support\Listening\ListeningSectionMap;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListeningSectionService
{
    public function __construct(
        private readonly ListeningSectionRepository $sections,
        private readonly CreateDefaultListeningSectionsAction $createDefaultSections,
        private readonly ReorderListeningSectionsAction $reorderSections,
        private readonly ValidateListeningSectionRangeAction $validateRange,
    ) {}

    /**
     * @return Collection<int, ListeningSection>
     */
    public function listForTest(ListeningTest $test, bool $withTrashed = false): Collection
    {
        return $this->sections->forTest($test, $withTrashed);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(ListeningTest $test, array $data): ListeningSection
    {
        $this->assertTestAllowsSectionChanges($test, allowCreate: true);

        return DB::transaction(function () use ($test, $data): ListeningSection {
            $payload = $this->preparePayload($test, $data);

            if ($this->sections->countActiveSections($test) >= 4 && ($payload['is_active'] ?? true)) {
                throw ValidationException::withMessages([
                    'section_number' => 'Maximum 4 sections are allowed.',
                ]);
            }

            if ($this->sections->sectionNumberExists($test, (int) $payload['section_number'])) {
                throw ValidationException::withMessages([
                    'section_number' => 'Section number already exists for this listening test.',
                ]);
            }

            $section = $this->sections->create($payload);

            $rangeErrors = $this->validateRange->validateSection($section);

            if ($rangeErrors !== []) {
                throw ValidationException::withMessages([
                    'section_number' => $rangeErrors[0],
                ]);
            }

            return $section->load(['audio', 'transcript'])->loadCount(['questionGroups', 'questions']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ListeningTest $test, ListeningSection $section, array $data): ListeningSection
    {
        $this->ensureSectionBelongsToTest($test, $section);

        return DB::transaction(function () use ($test, $section, $data): ListeningSection {
            $payload = $this->preparePayload($test, $data, $section);

            if (
                ($payload['is_active'] ?? $section->is_active)
                && ! $section->is_active
                && $this->sections->countActiveSections($test) >= 4
            ) {
                throw ValidationException::withMessages([
                    'is_active' => 'Maximum 4 sections are allowed.',
                ]);
            }

            if ($this->sections->sectionNumberExists($test, (int) $payload['section_number'], $section->id)) {
                throw ValidationException::withMessages([
                    'section_number' => 'Section number already exists for this listening test.',
                ]);
            }

            $updated = $this->sections->update($section, $payload);

            $rangeErrors = $this->validateRange->validateSection($updated);

            if ($rangeErrors !== []) {
                throw ValidationException::withMessages([
                    'section_number' => $rangeErrors[0],
                ]);
            }

            return $updated->load(['audio', 'transcript'])->loadCount(['questionGroups', 'questions']);
        });
    }

    public function delete(ListeningTest $test, ListeningSection $section): bool
    {
        $this->ensureSectionBelongsToTest($test, $section);
        $this->assertTestAllowsSectionChanges($test, allowDelete: true);

        return DB::transaction(fn (): bool => $this->sections->delete($section));
    }

    public function restore(ListeningTest $test, int $sectionId): ?ListeningSection
    {
        $section = $this->sections->findTrashedForTest($test, $sectionId);

        if ($section === null) {
            return null;
        }

        $this->assertTestAllowsSectionChanges($test, allowCreate: true);

        return DB::transaction(function () use ($test, $section): ListeningSection {
            if ($this->sections->sectionNumberExists($test, (int) $section->section_number, $section->id, false)) {
                throw ValidationException::withMessages([
                    'section' => 'Section number already exists for this listening test.',
                ]);
            }

            if ($this->sections->countActiveSections($test) >= 4 && $section->is_active) {
                throw ValidationException::withMessages([
                    'section' => 'Maximum 4 sections are allowed.',
                ]);
            }

            return $this->sections->restore($section);
        });
    }

    /**
     * @return array{created: int, skipped: int}
     */
    public function createDefaultSections(ListeningTest $test): array
    {
        $this->assertTestAllowsSectionChanges($test, allowCreate: true);

        return $this->createDefaultSections->execute($test);
    }

    /**
     * @param  list<int>  $orderedSectionIds
     */
    public function reorder(ListeningTest $test, array $orderedSectionIds): void
    {
        $this->assertTestAllowsSectionChanges($test);

        $this->reorderSections->execute($test, $orderedSectionIds);
    }

    /**
     * @return array{
     *     has_audio: bool,
     *     has_transcript: bool,
     *     has_valid_range: bool,
     *     groups_count: int,
     *     questions_count: int,
     *     expected_questions: int,
     *     is_ready: bool,
     *     missing: list<string>
     * }
     */
    public function getSectionReadiness(ListeningSection $section): array
    {
        $section->loadCount([
            'questionGroups' => fn ($query) => $query->where('is_active', true),
            'questions' => fn ($query) => $query->where('is_active', true),
        ]);

        $hasAudio = $section->audio_id !== null;
        $hasTranscript = $section->transcript_id !== null;
        $rangeErrors = $this->validateRange->validateSection($section);
        $hasValidRange = $rangeErrors === [];

        $activeQuestions = $section->questions()
            ->where('is_active', true)
            ->get();

        $questionsCount = $activeQuestions->count();
        $expectedQuestions = (int) $section->total_questions;
        $missing = [];

        if (! $hasValidRange) {
            $missing = array_merge($missing, $rangeErrors);
        }

        if (! $hasAudio) {
            $missing[] = 'Section audio is missing.';
        }

        if ($questionsCount < $expectedQuestions) {
            $missing[] = "Section requires {$expectedQuestions} active questions (currently {$questionsCount}).";
        }

        foreach ($activeQuestions as $question) {
            $number = (int) $question->question_number;

            if ($number < (int) $section->start_question_number || $number > (int) $section->end_question_number) {
                $missing[] = "Question {$number} is outside the official section range.";

                break;
            }
        }

        $isReady = $hasValidRange && $hasAudio && $questionsCount === $expectedQuestions && $missing === [];

        return [
            'has_audio' => $hasAudio,
            'has_transcript' => $hasTranscript,
            'has_valid_range' => $hasValidRange,
            'groups_count' => (int) $section->question_groups_count,
            'questions_count' => $questionsCount,
            'expected_questions' => $expectedQuestions,
            'is_ready' => $isReady,
            'missing' => $missing,
        ];
    }

    /**
     * @return array{
     *     sections_count: int,
     *     expected_sections: int,
     *     sections_with_audio: int,
     *     sections_with_transcript: int,
     *     sections_ready: int,
     *     missing_sections: list<int>,
     *     is_complete: bool
     * }
     */
    public function getTestSectionSummary(ListeningTest $test): array
    {
        $sections = $this->sections->forTest($test);
        $existingNumbers = $sections->pluck('section_number')->map(fn ($n) => (int) $n)->all();
        $missingSections = array_values(array_diff(ListeningSectionMap::officialSectionNumbers(), $existingNumbers));

        $sectionsWithAudio = $sections->filter(fn (ListeningSection $section) => $section->audio_id !== null)->count();
        $sectionsWithTranscript = $sections->filter(fn (ListeningSection $section) => $section->transcript_id !== null)->count();
        $sectionsReady = 0;

        foreach ($sections as $section) {
            if ($this->getSectionReadiness($section)['is_ready']) {
                $sectionsReady++;
            }
        }

        return [
            'sections_count' => $sections->count(),
            'expected_sections' => 4,
            'sections_with_audio' => $sectionsWithAudio,
            'sections_with_transcript' => $sectionsWithTranscript,
            'sections_ready' => $sectionsReady,
            'missing_sections' => $missingSections,
            'is_complete' => $sections->count() === 4 && $missingSections === [],
        ];
    }

    public function ensureSectionBelongsToTest(ListeningTest $test, ListeningSection $section): void
    {
        if ((int) $section->listening_test_id !== (int) $test->id) {
            throw ValidationException::withMessages([
                'section' => 'Section does not belong to this test.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePayload(ListeningTest $test, array $data, ?ListeningSection $existing = null): array
    {
        $sectionNumber = (int) ($data['section_number'] ?? $existing?->section_number ?? 0);

        if (! ListeningSectionMap::isValidSectionNumber($sectionNumber)) {
            throw ValidationException::withMessages([
                'section_number' => 'Section number must be between 1 and 4.',
            ]);
        }

        $range = ListeningSectionMap::forSectionNumber($sectionNumber);
        $numberErrors = $this->validateRange->validateSectionNumber($sectionNumber);

        if ($numberErrors !== []) {
            throw ValidationException::withMessages([
                'section_number' => $numberErrors[0],
            ]);
        }

        $payload = [
            'listening_test_id' => $test->id,
            'section_number' => $sectionNumber,
            'title' => $data['title'] ?? ('Section '.$sectionNumber),
            'instruction' => $data['instruction'] ?? null,
            'section_type' => $data['section_type'] ?? $range['default_type']->value,
            'audio_id' => $data['audio_id'] ?? null,
            'transcript_id' => $data['transcript_id'] ?? null,
            'start_question_number' => $range['start'],
            'end_question_number' => $range['end'],
            'total_questions' => $range['total'],
            'display_order' => $data['display_order'] ?? $sectionNumber,
            'duration_seconds' => $data['duration_seconds'] ?? null,
            'preparation_seconds' => $data['preparation_seconds'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'meta' => $data['meta'] ?? null,
        ];

        if ($payload['audio_id'] === '') {
            $payload['audio_id'] = null;
        }

        if ($payload['transcript_id'] === '') {
            $payload['transcript_id'] = null;
        }

        return $payload;
    }

    private function assertTestAllowsSectionChanges(
        ListeningTest $test,
        bool $allowCreate = false,
        bool $allowDelete = false,
    ): void {
        if ($test->status === ListeningTestStatus::Archived) {
            throw ValidationException::withMessages([
                'listening_test' => 'Archived listening tests cannot be modified. Unarchive the test first.',
            ]);
        }

        if ($test->status === ListeningTestStatus::Published && ($allowCreate || $allowDelete)) {
            throw ValidationException::withMessages([
                'listening_test' => 'Unpublish the listening test before changing section structure.',
            ]);
        }
    }
}
