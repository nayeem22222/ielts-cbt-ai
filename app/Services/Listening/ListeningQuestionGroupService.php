<?php

declare(strict_types=1);

namespace App\Services\Listening;

use App\Actions\Listening\QuestionTypes\NormalizeQuestionTypePayloadAction;
use App\Actions\Listening\QuestionTypes\ValidateQuestionTypePayloadAction;
use App\Actions\Listening\ValidateListeningQuestionGroupRangeAction;
use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Enums\Listening\ListeningTestStatus;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Repositories\Listening\ListeningQuestionGroupRepository;
use App\Repositories\Listening\ListeningQuestionRepository;
use App\Support\Listening\ListeningQuestionGroupDefaults;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListeningQuestionGroupService
{
    public function __construct(
        private readonly ListeningQuestionGroupRepository $groups,
        private readonly ListeningQuestionRepository $questions,
        private readonly ValidateListeningQuestionGroupRangeAction $validateGroupRangeAction,
        private readonly NormalizeQuestionTypePayloadAction $normalizeQuestionType,
        private readonly ValidateQuestionTypePayloadAction $validateQuestionType,
    ) {}

    /**
     * @return Collection<int, ListeningQuestionGroup>
     */
    public function listForSection(ListeningSection $section): Collection
    {
        return $this->groups->forSection($section);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(ListeningTest $test, ListeningSection $section, array $data): ListeningQuestionGroup
    {
        $this->ensureSectionBelongsToTest($test, $section);
        $this->assertTestAllowsQuestionChanges($test);

        return DB::transaction(function () use ($test, $section, $data): ListeningQuestionGroup {
            $start = (int) $data['start_question_number'];
            $end = (int) $data['end_question_number'];
            $rangeErrors = $this->validateGroupRangeAction->execute($section, $start, $end);

            if ($rangeErrors !== []) {
                throw ValidationException::withMessages($this->mapRangeErrors($rangeErrors));
            }

            $payload = $this->preparePayload($test, $section, $data);

            $type = $payload['question_type'] instanceof ListeningQuestionType
                ? $payload['question_type']
                : ListeningQuestionType::from((string) $payload['question_type']);
            $payload = $this->normalizeQuestionType->execute($payload, $type);
            $this->validateQuestionType->executeOrFail('group', $payload, $type);

            return $this->groups->create($payload)->loadCount('questions');
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(
        ListeningTest $test,
        ListeningSection $section,
        ListeningQuestionGroup $group,
        array $data,
    ): ListeningQuestionGroup {
        $this->ensureSectionBelongsToTest($test, $section);
        $this->ensureGroupBelongsToSection($section, $group);
        $this->assertTestAllowsQuestionChanges($test);

        return DB::transaction(function () use ($section, $group, $data, $test): ListeningQuestionGroup {
            $start = (int) ($data['start_question_number'] ?? $group->start_question_number);
            $end = (int) ($data['end_question_number'] ?? $group->end_question_number);
            $rangeErrors = $this->validateGroupRangeAction->execute($section, $start, $end, $group->id);

            if ($rangeErrors !== []) {
                throw ValidationException::withMessages($this->mapRangeErrors($rangeErrors));
            }

            $payload = $this->preparePayload($test, $section, $data, $group);
            $type = $payload['question_type'] instanceof ListeningQuestionType
                ? $payload['question_type']
                : ListeningQuestionType::from((string) $payload['question_type']);
            $group->load('questions');
            $payload = $this->normalizeQuestionType->execute($payload, $type, $group);

            if ($this->includesGroupTypePayload($data)) {
                $this->validateQuestionType->executeOrFail('group', $payload, $type, $group, null, $group->questions);
            }

            return $this->groups->update($group, $payload);
        });
    }

    /**
     * Persist builder-only group state without re-running strict type validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateBuilderState(
        ListeningTest $test,
        ListeningSection $section,
        ListeningQuestionGroup $group,
        array $data,
    ): ListeningQuestionGroup {
        $this->ensureSectionBelongsToTest($test, $section);
        $this->ensureGroupBelongsToSection($section, $group);
        $this->assertTestAllowsQuestionChanges($test);

        $allowed = array_intersect_key($data, array_flip([
            'content',
            'settings',
            'options',
            'image_path',
            'image_alt',
            'validation_rules',
        ]));

        if ($allowed === []) {
            return $group;
        }

        return DB::transaction(function () use ($test, $section, $group, $allowed): ListeningQuestionGroup {
            $payload = $this->preparePayload($test, $section, $allowed, $group);
            $type = $payload['question_type'] instanceof ListeningQuestionType
                ? $payload['question_type']
                : ListeningQuestionType::from((string) $payload['question_type']);
            $payload = $this->normalizeQuestionType->execute($payload, $type, $group);

            return $this->groups->update($group, $payload);
        });
    }

    public function createBlank(ListeningTest $test, ListeningSection $section): ListeningQuestionGroup
    {
        $range = $this->suggestNextQuestionRange($section);

        if ($range === null) {
            throw ValidationException::withMessages([
                'start_question_number' => 'No available question range in this section.',
            ]);
        }

        $type = ListeningQuestionType::FormCompletion;
        $contentLines = [];

        for ($i = $range['start']; $i <= $range['end']; $i++) {
            $contentLines[] = "[blank:{$i}]";
        }

        return $this->create($test, $section, [
            'title' => ListeningQuestionGroupDefaults::title($range['start'], $range['end']),
            'instruction' => ListeningQuestionGroupDefaults::instruction($type, (int) $section->section_number),
            'question_type' => $type->value,
            'start_question_number' => $range['start'],
            'end_question_number' => $range['end'],
            'layout_type' => ListeningLayoutType::Form->value,
            'content' => implode("\n", $contentLines),
            'settings' => ['word_limit' => 2, 'template_type' => 'form'],
            'is_active' => true,
        ]);
    }

    public function duplicate(
        ListeningTest $test,
        ListeningSection $section,
        ListeningQuestionGroup $group,
    ): ListeningQuestionGroup {
        $this->ensureSectionBelongsToTest($test, $section);
        $this->ensureGroupBelongsToSection($section, $group);
        $this->assertTestAllowsQuestionChanges($test);

        $range = $this->suggestNextQuestionRange($section);

        if ($range === null) {
            throw ValidationException::withMessages([
                'start_question_number' => 'No available question range in this section.',
            ]);
        }

        return DB::transaction(function () use ($test, $section, $group, $range): ListeningQuestionGroup {
            $type = $group->question_type ?? ListeningQuestionType::FormCompletion;
            $content = $group->content;

            if (in_array($type, [
                ListeningQuestionType::FormCompletion,
                ListeningQuestionType::NoteCompletion,
                ListeningQuestionType::SentenceCompletion,
                ListeningQuestionType::SummaryCompletion,
                ListeningQuestionType::TableCompletion,
                ListeningQuestionType::FlowchartCompletion,
            ], true)) {
                $content = $this->defaultCompletionContent($range['start'], $range['end']);
            }

            $copy = $this->groups->create([
                'listening_test_id' => $test->id,
                'listening_section_id' => $section->id,
                'title' => $group->title,
                'instruction' => $group->instruction,
                'question_type' => $group->question_type,
                'start_question_number' => $range['start'],
                'end_question_number' => $range['end'],
                'total_questions' => $range['total'],
                'display_order' => $this->groups->countForSection($section) + 1,
                'layout_type' => $group->layout_type,
                'audio_id' => $group->audio_id,
                'transcript_reference' => $group->transcript_reference,
                'image_path' => $group->image_path,
                'image_alt' => $group->image_alt,
                'content' => $content,
                'options' => $group->options,
                'settings' => $group->settings,
                'validation_rules' => $group->validation_rules,
                'is_active' => true,
                'meta' => $group->meta,
            ]);

            return $copy->loadCount('questions');
        });
    }

    public function delete(ListeningTest $test, ListeningSection $section, ListeningQuestionGroup $group): bool
    {
        $this->ensureSectionBelongsToTest($test, $section);
        $this->ensureGroupBelongsToSection($section, $group);
        $this->assertTestAllowsQuestionChanges($test, allowDelete: true);

        return DB::transaction(function () use ($group): bool {
            $group->questions()->each(fn (ListeningQuestion $question) => $question->delete());

            return $this->groups->delete($group);
        });
    }

    /**
     * @param  list<int>  $orderedGroupIds
     */
    public function reorder(ListeningSection $section, array $orderedGroupIds): void
    {
        DB::transaction(function () use ($section, $orderedGroupIds): void {
            foreach ($orderedGroupIds as $index => $groupId) {
                $this->groups->query()
                    ->where('listening_section_id', $section->id)
                    ->whereKey($groupId)
                    ->update(['display_order' => $index + 1]);
            }
        });
    }

    public function moveUp(ListeningQuestionGroup $group): void
    {
        $section = $group->section ?? $group->section()->first();

        if ($section === null) {
            return;
        }

        $groups = $this->groups->forSection($section)->values();
        $index = $groups->search(fn (ListeningQuestionGroup $item): bool => (int) $item->id === (int) $group->id);

        if ($index === false || $index === 0) {
            return;
        }

        $previous = $groups[$index - 1];
        $currentOrder = (int) $group->display_order;
        $previousOrder = (int) $previous->display_order;

        $this->groups->update($group, ['display_order' => $previousOrder]);
        $this->groups->update($previous, ['display_order' => $currentOrder]);
    }

    public function moveDown(ListeningQuestionGroup $group): void
    {
        $section = $group->section ?? $group->section()->first();

        if ($section === null) {
            return;
        }

        $groups = $this->groups->forSection($section)->values();
        $index = $groups->search(fn (ListeningQuestionGroup $item): bool => (int) $item->id === (int) $group->id);

        if ($index === false || $index >= $groups->count() - 1) {
            return;
        }

        $next = $groups[$index + 1];
        $currentOrder = (int) $group->display_order;
        $nextOrder = (int) $next->display_order;

        $this->groups->update($group, ['display_order' => $nextOrder]);
        $this->groups->update($next, ['display_order' => $currentOrder]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getGroupReadiness(ListeningQuestionGroup $group): array
    {
        $group->loadCount(['questions' => fn ($q) => $q->where('is_active', true)]);
        $section = $group->section ?? $group->section()->first();
        $rangeErrors = $section
            ? $this->validateGroupRangeAction->execute($section, (int) $group->start_question_number, (int) $group->end_question_number, $group->id)
            : [];
        $expected = (int) $group->total_questions;
        $questionsCount = (int) $group->questions_count;
        $existingNumbers = $group->questions()->where('is_active', true)->pluck('question_number')->map(fn ($n) => (int) $n)->all();
        $missing = [];

        for ($i = (int) $group->start_question_number; $i <= (int) $group->end_question_number; $i++) {
            if (! in_array($i, $existingNumbers, true)) {
                $missing[] = $i;
            }
        }

        $readyCount = $group->questions()->where('is_active', true)->get()
            ->filter(fn (ListeningQuestion $q) => app(ListeningQuestionService::class)->getQuestionReadiness($q)['is_ready'])
            ->count();

        $missingMessages = $rangeErrors;

        if ($questionsCount < $expected) {
            $missingMessages[] = "Group requires {$expected} active questions (currently {$questionsCount}).";
        }

        return [
            'has_valid_range' => $rangeErrors === [],
            'has_no_overlap' => $rangeErrors === [] || ! str_contains($rangeErrors[0] ?? '', 'overlap'),
            'questions_count' => $questionsCount,
            'expected_questions' => $expected,
            'missing_question_numbers' => $missing,
            'questions_ready_count' => $readyCount,
            'is_ready' => $rangeErrors === [] && $questionsCount === $expected && $missing === [],
            'missing' => $missingMessages,
        ];
    }

    public function ensureGroupBelongsToSection(ListeningSection $section, ListeningQuestionGroup $group): void
    {
        if ((int) $group->listening_section_id !== (int) $section->id) {
            throw ValidationException::withMessages(['group' => 'Group does not belong to this section.']);
        }
    }

    /**
     * @return array{start: int, end: int, total: int}|null
     */
    public function suggestNextQuestionRange(ListeningSection $section): ?array
    {
        $gaps = $this->getAvailableQuestionRanges($section);

        if ($gaps === []) {
            return null;
        }

        $gap = $gaps[0];
        $start = (int) $gap['start'];
        $span = min(4, (int) $gap['total']);
        $end = min($start + $span - 1, (int) $gap['end']);

        return [
            'start' => $start,
            'end' => $end,
            'total' => ($end - $start) + 1,
        ];
    }

    /**
     * @return list<array{start: int, end: int, total: int}>
     */
    public function getAvailableQuestionRanges(ListeningSection $section): array
    {
        $groups = $this->groups->forSection($section)->sortBy('start_question_number')->values();
        $ranges = [];
        $cursor = (int) $section->start_question_number;

        foreach ($groups as $group) {
            $start = (int) $group->start_question_number;

            if ($start > $cursor) {
                $ranges[] = ['start' => $cursor, 'end' => $start - 1, 'total' => ($start - $cursor)];
            }

            $cursor = max($cursor, (int) $group->end_question_number + 1);
        }

        if ($cursor <= (int) $section->end_question_number) {
            $ranges[] = [
                'start' => $cursor,
                'end' => (int) $section->end_question_number,
                'total' => ((int) $section->end_question_number - $cursor) + 1,
            ];
        }

        return $ranges;
    }

    /**
     * @return list<string>
     */
    public function validateRange(ListeningSection $section, int $start, int $end, ?int $ignoreGroupId = null): array
    {
        return $this->validateGroupRangeAction->execute($section, $start, $end, $ignoreGroupId);
    }

    public function ensureSectionBelongsToTest(ListeningTest $test, ListeningSection $section): void
    {
        if ((int) $section->listening_test_id !== (int) $test->id) {
            throw ValidationException::withMessages(['section' => 'Section does not belong to this test.']);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePayload(
        ListeningTest $test,
        ListeningSection $section,
        array $data,
        ?ListeningQuestionGroup $existing = null,
    ): array {
        $start = (int) ($data['start_question_number'] ?? $existing?->start_question_number ?? 0);
        $end = (int) ($data['end_question_number'] ?? $existing?->end_question_number ?? 0);

        return [
            'listening_test_id' => $test->id,
            'listening_section_id' => $section->id,
            'title' => $data['title'] ?? $existing?->title,
            'instruction' => $data['instruction'] ?? $existing?->instruction,
            'question_type' => $data['question_type'] ?? $existing?->question_type,
            'start_question_number' => $start,
            'end_question_number' => $end,
            'total_questions' => ($end - $start) + 1,
            'display_order' => $data['display_order'] ?? $existing?->display_order ?? $this->groups->countForSection($section) + 1,
            'layout_type' => $data['layout_type'] ?? $existing?->layout_type,
            'audio_id' => $data['audio_id'] ?? $existing?->audio_id,
            'transcript_reference' => $data['transcript_reference'] ?? $existing?->transcript_reference,
            'image_path' => $data['image_path'] ?? $existing?->image_path,
            'image_alt' => $data['image_alt'] ?? $existing?->image_alt,
            'content' => $data['content'] ?? $existing?->content,
            'options' => $data['options'] ?? $existing?->options,
            'settings' => $data['settings'] ?? $existing?->settings,
            'validation_rules' => $data['validation_rules'] ?? $existing?->validation_rules,
            'is_active' => (bool) ($data['is_active'] ?? $existing?->is_active ?? true),
            'meta' => $data['meta'] ?? $existing?->meta,
        ];
    }

    /**
     * @param  list<string>  $errors
     * @return array<string, string>
     */
    private function mapRangeErrors(array $errors): array
    {
        $messages = [];

        foreach ($errors as $error) {
            if (str_contains($error, 'overlap')) {
                $messages['end_question_number'] = $error;
            } elseif (str_contains($error, 'greater')) {
                $messages['end_question_number'] = $error;
            } elseif (str_contains($error, 'section range')) {
                $messages['start_question_number'] = $error;
            } else {
                $messages['start_question_number'] = $messages['start_question_number'] ?? $error;
            }
        }

        return $messages;
    }

    private function defaultCompletionContent(int $start, int $end): string
    {
        $lines = [];

        for ($i = $start; $i <= $end; $i++) {
            $lines[] = "[blank:{$i}]";
        }

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function includesGroupTypePayload(array $data): bool
    {
        foreach (['content', 'options', 'settings', 'image_path', 'validation_rules'] as $field) {
            if (array_key_exists($field, $data)) {
                return true;
            }
        }

        return false;
    }

    private function assertTestAllowsQuestionChanges(
        ListeningTest $test,
        bool $allowDelete = false,
    ): void {
        if ($test->status === ListeningTestStatus::Archived) {
            throw ValidationException::withMessages([
                'listening_test' => 'Archived listening tests cannot be modified.',
            ]);
        }

        if ($test->status === ListeningTestStatus::Published) {
            throw ValidationException::withMessages([
                'listening_test' => 'Unpublish the listening test before changing questions.',
            ]);
        }
    }
}
