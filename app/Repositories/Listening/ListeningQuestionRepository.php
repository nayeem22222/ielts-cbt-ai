<?php

declare(strict_types=1);

namespace App\Repositories\Listening;

use App\Enums\Listening\ListeningConstants;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ListeningQuestionRepository
{
    public function query(): Builder
    {
        return ListeningQuestion::query();
    }

    /**
     * @return Collection<int, ListeningQuestion>
     */
    public function forGroup(ListeningQuestionGroup $group): Collection
    {
        return $this->query()
            ->where('listening_question_group_id', $group->id)
            ->ordered()
            ->get();
    }

    /**
     * @return Collection<int, ListeningQuestion>
     */
    public function forGroupIncludingTrashed(ListeningQuestionGroup $group): Collection
    {
        return $this->query()
            ->withTrashed()
            ->where('listening_question_group_id', $group->id)
            ->ordered()
            ->get();
    }

    public function findByNumberForGroup(
        ListeningQuestionGroup $group,
        int $questionNumber,
        bool $withTrashed = false,
    ): ?ListeningQuestion {
        $query = $this->query()
            ->where('listening_question_group_id', $group->id)
            ->where('question_number', $questionNumber);

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->first();
    }

    public function findByNumberForTest(
        ListeningTest $test,
        int $questionNumber,
        bool $withTrashed = false,
    ): ?ListeningQuestion {
        $query = $this->query()
            ->where('listening_test_id', $test->id)
            ->where('question_number', $questionNumber);

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->first();
    }

    /**
     * @return Collection<int, ListeningQuestion>
     */
    public function forSection(ListeningSection $section): Collection
    {
        return $this->query()
            ->where('listening_section_id', $section->id)
            ->ordered()
            ->get();
    }

    /**
     * @return Collection<int, ListeningQuestion>
     */
    public function forTest(ListeningTest $test, bool $activeOnly = false): Collection
    {
        $query = $this->query()->where('listening_test_id', $test->id)->ordered();

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->get();
    }

    public function findForGroup(ListeningQuestionGroup $group, int $questionId): ?ListeningQuestion
    {
        return $this->query()
            ->where('listening_question_group_id', $group->id)
            ->find($questionId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ListeningQuestion
    {
        return $this->query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ListeningQuestion $question, array $data): ListeningQuestion
    {
        $question->update($data);

        return $question->refresh();
    }

    public function delete(ListeningQuestion $question): bool
    {
        return (bool) $question->delete();
    }

    public function questionNumberExists(ListeningTest $test, int $questionNumber, ?int $ignoreQuestionId = null): bool
    {
        $query = $this->query()
            ->withTrashed()
            ->where('listening_test_id', $test->id)
            ->where('question_number', $questionNumber);

        if ($ignoreQuestionId !== null) {
            $query->whereKeyNot($ignoreQuestionId);
        }

        return $query->exists();
    }

    public function countForSection(ListeningSection $section, bool $activeOnly = false): int
    {
        $query = $this->query()->where('listening_section_id', $section->id);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->count();
    }

    public function countForTest(ListeningTest $test, bool $activeOnly = false): int
    {
        $query = $this->query()->where('listening_test_id', $test->id);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        return $query->count();
    }

    /**
     * @return list<int>
     */
    public function missingNumbersForSection(ListeningSection $section, bool $activeOnly = true): array
    {
        $query = $this->query()->where('listening_section_id', $section->id);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $existing = $query->pluck('question_number')->map(fn ($n) => (int) $n)->all();
        $missing = [];

        for ($i = (int) $section->start_question_number; $i <= (int) $section->end_question_number; $i++) {
            if (! in_array($i, $existing, true)) {
                $missing[] = $i;
            }
        }

        return $missing;
    }

    /**
     * @return list<int>
     */
    public function missingNumbersForTest(ListeningTest $test, bool $activeOnly = true): array
    {
        $query = $this->query()->where('listening_test_id', $test->id);

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $existing = $query->pluck('question_number')->map(fn ($n) => (int) $n)->all();
        $missing = [];

        for ($i = ListeningConstants::MIN_QUESTION_NUMBER; $i <= ListeningConstants::MAX_QUESTION_NUMBER; $i++) {
            if (! in_array($i, $existing, true)) {
                $missing[] = $i;
            }
        }

        return $missing;
    }

    /**
     * @return list<int>
     */
    public function duplicateNumbersForTest(ListeningTest $test): array
    {
        return $this->query()
            ->where('listening_test_id', $test->id)
            ->select('question_number')
            ->groupBy('question_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('question_number')
            ->map(fn ($n) => (int) $n)
            ->values()
            ->all();
    }
}
