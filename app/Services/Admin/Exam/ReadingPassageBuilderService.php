<?php

declare(strict_types=1);

namespace App\Services\Admin\Exam;

use App\Enums\Exam\PassageStatus;
use App\Models\ReadingPassage;
use App\Models\ReadingTest;
use App\Support\Reading\ReadingPassageContentRenderer;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReadingPassageBuilderService
{
    /**
     * @return Collection<int, ReadingPassage>
     */
    public function listForBuilder(ReadingTest $test): Collection
    {
        return $test->passages()
            ->withCount('groups')
            ->ordered()
            ->get();
    }

    public function createBlank(ReadingTest $test): ReadingPassage
    {
        return DB::transaction(function () use ($test): ReadingPassage {
            $nextOrder = (int) $test->passages()->max('sort_order') + 1;
            $range = $this->suggestNextQuestionRange($test);

            /** @var ReadingPassage $passage */
            $passage = $test->passages()->create([
                'part_number' => $nextOrder,
                'title' => 'Passage '.$nextOrder,
                'subtitle' => null,
                'instruction' => null,
                'start_question' => $range['start'],
                'end_question' => $range['end'],
                'content_html' => '',
                'content_text' => '',
                'status' => PassageStatus::Draft,
                'settings' => ['auto_paragraph_labels' => true],
                'sort_order' => $nextOrder,
            ]);

            return $passage->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ReadingPassage $passage, array $data): ReadingPassage
    {
        return DB::transaction(function () use ($passage, $data): ReadingPassage {
            $this->assertQuestionRangeIsValid(
                $passage->test,
                (int) $data['start_question'],
                (int) $data['end_question'],
                $passage,
            );

            $settings = $passage->settings ?? [];
            $settings['auto_paragraph_labels'] = (bool) ($data['auto_paragraph_labels'] ?? false);

            $contentHtml = (string) ($data['content_html'] ?? '');

            $passage->forceFill([
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'instruction' => $data['instruction'] ?? null,
                'start_question' => (int) $data['start_question'],
                'end_question' => (int) $data['end_question'],
                'content_html' => $contentHtml,
                'content_text' => ReadingPassageContentRenderer::htmlToPlainText($contentHtml),
                'status' => $data['status'],
                'settings' => $settings,
                'sort_order' => (int) ($data['sort_order'] ?? $passage->sort_order),
            ])->save();

            return $passage->refresh();
        });
    }

    public function delete(ReadingPassage $passage): void
    {
        DB::transaction(function () use ($passage): void {
            $test = $passage->test;
            $passage->delete();
            $this->renumberPassages($test);
        });
    }

    public function duplicate(ReadingPassage $passage, bool $withQuestionGroups = false): ReadingPassage
    {
        return DB::transaction(function () use ($passage, $withQuestionGroups): ReadingPassage {
            $test = $passage->test;
            $nextOrder = (int) $test->passages()->max('sort_order') + 1;
            $range = $this->suggestNextQuestionRange($test);

            /** @var ReadingPassage $copy */
            $copy = $test->passages()->create([
                'part_number' => $nextOrder,
                'title' => 'Copy of '.$passage->title,
                'subtitle' => $passage->subtitle,
                'instruction' => $passage->instruction,
                'start_question' => $range['start'],
                'end_question' => $range['end'],
                'content_html' => $passage->content_html,
                'content_text' => $passage->content_text,
                'status' => PassageStatus::Draft,
                'settings' => $passage->settings,
                'sort_order' => $nextOrder,
            ]);

            if ($withQuestionGroups) {
                $passage->load('groups');

                foreach ($passage->groups as $group) {
                    $copy->groups()->create($group->only([
                        'title',
                        'instruction',
                        'question_type',
                        'start_question',
                        'end_question',
                        'sort_order',
                        'settings',
                    ]));
                }
            }

            return $copy->refresh();
        });
    }

    public function moveUp(ReadingPassage $passage): void
    {
        DB::transaction(function () use ($passage): void {
            $passages = $this->orderedPassages($passage->test);
            $index = $passages->search(fn (ReadingPassage $item): bool => $item->id === $passage->id);

            if ($index === false || $index === 0) {
                return;
            }

            $this->swapSortOrder($passages[$index - 1], $passages[$index]);
            $this->renumberPassages($passage->test);
        });
    }

    public function moveDown(ReadingPassage $passage): void
    {
        DB::transaction(function () use ($passage): void {
            $passages = $this->orderedPassages($passage->test);
            $index = $passages->search(fn (ReadingPassage $item): bool => $item->id === $passage->id);

            if ($index === false || $index >= $passages->count() - 1) {
                return;
            }

            $this->swapSortOrder($passages[$index], $passages[$index + 1]);
            $this->renumberPassages($passage->test);
        });
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(ReadingTest $test, array $orderedIds): void
    {
        DB::transaction(function () use ($test, $orderedIds): void {
            $passages = $test->passages()->get()->keyBy('id');
            $sortOrder = 1;

            foreach ($orderedIds as $id) {
                if (! $passages->has($id)) {
                    throw ValidationException::withMessages([
                        'passage_ids' => 'One or more passages do not belong to this reading test.',
                    ]);
                }

                $passages[$id]->forceFill(['sort_order' => $sortOrder])->save();
                $sortOrder++;
            }

            $this->renumberPassages($test);
        });
    }

    public function ensureBelongsToTest(ReadingPassage $passage, ReadingTest $test): void
    {
        if ($passage->reading_test_id !== $test->id) {
            abort(404);
        }
    }

    /**
     * @return array{start: int, end: int}
     */
    public function suggestNextQuestionRange(ReadingTest $test): array
    {
        $lastEnd = $test->passages()
            ->whereNotNull('end_question')
            ->max('end_question');

        $start = $lastEnd ? ((int) $lastEnd + 1) : 1;
        $span = $test->exam_type?->value === 'general' ? 14 : 13;
        $end = $start + $span - 1;

        return ['start' => $start, 'end' => $end];
    }

    public function assertQuestionRangeIsValid(
        ReadingTest $test,
        int $start,
        int $end,
        ?ReadingPassage $except = null,
    ): void {
        if ($start >= $end) {
            throw ValidationException::withMessages([
                'end_question' => 'The end question must be greater than the start question.',
            ]);
        }

        $query = $test->passages()
            ->whereNotNull('start_question')
            ->whereNotNull('end_question');

        if ($except !== null) {
            $query->whereKeyNot($except->id);
        }

        foreach ($query->get() as $other) {
            if ($this->rangesOverlap($start, $end, (int) $other->start_question, (int) $other->end_question)) {
                throw ValidationException::withMessages([
                    'start_question' => "Question range overlaps with Passage {$other->part_number} ({$other->question_range_label}).",
                ]);
            }
        }
    }

    /**
     * @return Collection<int, ReadingPassage>
     */
    private function orderedPassages(ReadingTest $test): Collection
    {
        return $test->passages()->ordered()->get();
    }

    private function swapSortOrder(ReadingPassage $first, ReadingPassage $second): void
    {
        $firstOrder = $first->sort_order;
        $first->forceFill(['sort_order' => $second->sort_order])->save();
        $second->forceFill(['sort_order' => $firstOrder])->save();
    }

    private function renumberPassages(ReadingTest $test): void
    {
        $passages = $this->orderedPassages($test);
        $partNumber = 1;

        foreach ($passages as $passage) {
            $passage->forceFill([
                'part_number' => $partNumber,
                'sort_order' => $partNumber,
            ])->save();
            $partNumber++;
        }
    }

    private function rangesOverlap(int $startA, int $endA, int $startB, int $endB): bool
    {
        return $startA <= $endB && $startB <= $endA;
    }
}
