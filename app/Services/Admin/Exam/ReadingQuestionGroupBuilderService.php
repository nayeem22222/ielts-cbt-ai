<?php

declare(strict_types=1);

namespace App\Services\Admin\Exam;

use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\PassageStatus;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingTest;
use App\Support\Reading\ReadingQuestionGroupDefaults;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReadingQuestionGroupBuilderService
{
    /**
     * @return Collection<int, ReadingPassage>
     */
    public function passagesForBuilder(ReadingTest $test): Collection
    {
        return $test->passages()
            ->with(['groups' => fn ($query) => $query->withCount('questions')->ordered()])
            ->withCount('groups')
            ->ordered()
            ->get();
    }

    /**
     * @param  Collection<int, ReadingPassage>  $passageList
     * @return array{0: ?ReadingPassage, 1: ?ReadingQuestionGroup}
     */
    public function resolveBuilderSelection(
        ReadingTest $test,
        Collection $passageList,
        int $passageId,
        int $groupId,
    ): array {
        if ($groupId > 0) {
            foreach ($passageList as $passage) {
                $match = $passage->groups->first(
                    fn (ReadingQuestionGroup $group): bool => (int) $group->getKey() === $groupId,
                );

                if ($match instanceof ReadingQuestionGroup) {
                    return [$passage, $match];
                }
            }

            $selectedGroup = ReadingQuestionGroup::query()
                ->withCount('questions')
                ->whereKey($groupId)
                ->whereHas('passage', fn ($query) => $query->where('reading_test_id', $test->id))
                ->first();

            if ($selectedGroup instanceof ReadingQuestionGroup) {
                $selectedPassage = $passageList->firstWhere('id', (int) $selectedGroup->passage_id)
                    ?? $selectedGroup->passage;

                return [$selectedPassage, $selectedGroup];
            }
        }

        $selectedPassage = null;

        if ($passageId > 0) {
            $selectedPassage = $passageList->firstWhere('id', $passageId)
                ?? $passageList->firstWhere('part_number', $passageId);
        } else {
            $selectedPassage = $passageList->first();
        }

        return [$selectedPassage, null];
    }

    public function createBlank(ReadingPassage $passage): ReadingQuestionGroup
    {
        return DB::transaction(function () use ($passage): ReadingQuestionGroup {
            $range = $this->suggestNextQuestionRange($passage);
            $type = OfficialReadingQuestionType::MatchingInformation;
            $nextOrder = (int) $passage->groups()->max('sort_order') + 1;

            /** @var ReadingQuestionGroup $group */
            $group = $passage->groups()->create([
                'title' => ReadingQuestionGroupDefaults::title($range['start'], $range['end']),
                'instruction' => ReadingQuestionGroupDefaults::instruction($type, (int) $passage->part_number),
                'question_type' => $type,
                'start_question' => $range['start'],
                'end_question' => $range['end'],
                'sort_order' => $nextOrder,
                'status' => PassageStatus::Draft,
                'settings' => [],
            ]);

            return $group->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ReadingQuestionGroup $group, array $data): ReadingQuestionGroup
    {
        return DB::transaction(function () use ($group, $data): ReadingQuestionGroup {
            $this->assertQuestionRangeIsValid(
                $group->passage,
                (int) $data['start_question'],
                (int) $data['end_question'],
                $group,
            );

            $group->forceFill([
                'title' => $data['title'],
                'instruction' => $data['instruction'] ?? null,
                'question_type' => $data['question_type'],
                'start_question' => (int) $data['start_question'],
                'end_question' => (int) $data['end_question'],
                'sort_order' => (int) ($data['sort_order'] ?? $group->sort_order),
                'status' => $data['status'],
                'settings' => $data['settings'] ?? $group->settings ?? [],
            ])->save();

            return $group->refresh();
        });
    }

    public function delete(ReadingQuestionGroup $group): void
    {
        DB::transaction(function () use ($group): void {
            $passage = $group->passage;
            $group->delete();
            $this->renumberGroups($passage);
        });
    }

    public function duplicate(ReadingQuestionGroup $group): ReadingQuestionGroup
    {
        return DB::transaction(function () use ($group): ReadingQuestionGroup {
            $passage = $group->passage;
            $range = $this->suggestNextQuestionRange($passage);
            $nextOrder = (int) $passage->groups()->max('sort_order') + 1;

            /** @var ReadingQuestionGroup $copy */
            $copy = $passage->groups()->create([
                'title' => $group->title,
                'instruction' => $group->instruction,
                'question_type' => $group->question_type,
                'start_question' => $range['start'],
                'end_question' => $range['end'],
                'sort_order' => $nextOrder,
                'status' => PassageStatus::Draft,
                'settings' => $group->settings ?? [],
            ]);

            return $copy->refresh();
        });
    }

    public function moveUp(ReadingQuestionGroup $group): void
    {
        DB::transaction(function () use ($group): void {
            $groups = $this->orderedGroups($group->passage);
            $index = $groups->search(fn (ReadingQuestionGroup $item): bool => $item->id === $group->id);

            if ($index === false || $index === 0) {
                return;
            }

            $this->swapSortOrder($groups[$index - 1], $groups[$index]);
            $this->renumberGroups($group->passage);
        });
    }

    public function moveDown(ReadingQuestionGroup $group): void
    {
        DB::transaction(function () use ($group): void {
            $groups = $this->orderedGroups($group->passage);
            $index = $groups->search(fn (ReadingQuestionGroup $item): bool => $item->id === $group->id);

            if ($index === false || $index >= $groups->count() - 1) {
                return;
            }

            $this->swapSortOrder($groups[$index], $groups[$index + 1]);
            $this->renumberGroups($group->passage);
        });
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(ReadingPassage $passage, array $orderedIds): void
    {
        DB::transaction(function () use ($passage, $orderedIds): void {
            $groups = $passage->groups()->get()->keyBy('id');
            $sortOrder = 1;

            foreach ($orderedIds as $id) {
                if (! $groups->has($id)) {
                    throw ValidationException::withMessages([
                        'group_ids' => 'One or more question groups do not belong to this passage.',
                    ]);
                }

                $groups[$id]->forceFill(['sort_order' => $sortOrder])->save();
                $sortOrder++;
            }

            $this->renumberGroups($passage);
        });
    }

    public function ensureBelongsToPassage(ReadingQuestionGroup $group, ReadingPassage $passage): void
    {
        if ($group->passage_id !== $passage->id) {
            abort(404);
        }
    }

    public function ensurePassageBelongsToTest(ReadingPassage $passage, ReadingTest $test): void
    {
        if ($passage->reading_test_id !== $test->id) {
            abort(404);
        }
    }

    /**
     * @return array{start: int, end: int}
     */
    public function suggestNextQuestionRange(ReadingPassage $passage): array
    {
        $passageStart = (int) $passage->start_question;
        $passageEnd = (int) $passage->end_question;

        $lastEnd = $passage->groups()
            ->whereNotNull('end_question')
            ->max('end_question');

        $start = $lastEnd ? ((int) $lastEnd + 1) : $passageStart;

        if ($start > $passageEnd) {
            $start = $passageStart;
        }

        $remaining = max(1, $passageEnd - $start + 1);
        $span = min(4, $remaining);
        $end = min($start + $span - 1, $passageEnd);

        return ['start' => $start, 'end' => $end];
    }

    public function assertQuestionRangeIsValid(
        ReadingPassage $passage,
        int $start,
        int $end,
        ?ReadingQuestionGroup $except = null,
    ): void {
        if ($start >= $end) {
            throw ValidationException::withMessages([
                'end_question' => 'The end question must be greater than the start question.',
            ]);
        }

        $passageStart = (int) $passage->start_question;
        $passageEnd = (int) $passage->end_question;

        if ($passageStart && $start < $passageStart) {
            throw ValidationException::withMessages([
                'start_question' => "Question range must start within Passage {$passage->part_number} ({$passageStart}-{$passageEnd}).",
            ]);
        }

        if ($passageEnd && $end > $passageEnd) {
            throw ValidationException::withMessages([
                'end_question' => "Question range must end within Passage {$passage->part_number} ({$passageStart}-{$passageEnd}).",
            ]);
        }

        $query = $passage->groups()
            ->whereNotNull('start_question')
            ->whereNotNull('end_question');

        if ($except !== null) {
            $query->whereKeyNot($except->id);
        }

        foreach ($query->get() as $other) {
            if ($this->rangesOverlap($start, $end, (int) $other->start_question, (int) $other->end_question)) {
                throw ValidationException::withMessages([
                    'start_question' => "Question range overlaps with {$other->title} ({$other->question_range_label}).",
                ]);
            }
        }
    }

    /**
     * @return Collection<int, ReadingQuestionGroup>
     */
    private function orderedGroups(ReadingPassage $passage): Collection
    {
        return $passage->groups()->ordered()->get();
    }

    private function swapSortOrder(ReadingQuestionGroup $first, ReadingQuestionGroup $second): void
    {
        $firstOrder = $first->sort_order;
        $first->forceFill(['sort_order' => $second->sort_order])->save();
        $second->forceFill(['sort_order' => $firstOrder])->save();
    }

    private function renumberGroups(ReadingPassage $passage): void
    {
        $groups = $this->orderedGroups($passage);
        $sortOrder = 1;

        foreach ($groups as $group) {
            $group->forceFill(['sort_order' => $sortOrder])->save();
            $sortOrder++;
        }
    }

    private function rangesOverlap(int $startA, int $endA, int $startB, int $endB): bool
    {
        return $startA <= $endB && $startB <= $endA;
    }
}
