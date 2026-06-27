<?php

declare(strict_types=1);

namespace App\Repositories\Listening;

use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Support\Listening\ListeningSectionMap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ListeningSectionRepository
{
    public function query(): Builder
    {
        return ListeningSection::query();
    }

    /**
     * @return Collection<int, ListeningSection>
     */
    public function forTest(ListeningTest $test, bool $withTrashed = false): Collection
    {
        $query = $this->query()
            ->where('listening_test_id', $test->id)
            ->with(['audio', 'transcript'])
            ->withCount(['questionGroups', 'questions'])
            ->ordered();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->get();
    }

    public function findForTest(ListeningTest $test, int $sectionId): ?ListeningSection
    {
        return $this->query()
            ->where('listening_test_id', $test->id)
            ->with(['audio', 'transcript'])
            ->withCount(['questionGroups', 'questions'])
            ->find($sectionId);
    }

    public function findTrashedForTest(ListeningTest $test, int $sectionId): ?ListeningSection
    {
        return $this->query()
            ->onlyTrashed()
            ->where('listening_test_id', $test->id)
            ->find($sectionId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ListeningSection
    {
        return ListeningSection::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ListeningSection $section, array $data): ListeningSection
    {
        $section->fill($data);
        $section->save();

        return $section->refresh();
    }

    public function delete(ListeningSection $section): bool
    {
        return (bool) $section->delete();
    }

    public function restore(ListeningSection $section): ListeningSection
    {
        $section->restore();

        return $section->refresh();
    }

    public function countActiveSections(ListeningTest $test): int
    {
        return $this->query()
            ->where('listening_test_id', $test->id)
            ->where('is_active', true)
            ->count();
    }

    public function sectionNumberExists(ListeningTest $test, int $sectionNumber, ?int $ignoreId = null, bool $includeTrashed = true): bool
    {
        $query = $this->query();

        if ($includeTrashed) {
            $query->withTrashed();
        }

        $query->where('listening_test_id', $test->id)
            ->where('section_number', $sectionNumber);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    /**
     * @return list<int>
     */
    public function availableSectionNumbersForCreate(ListeningTest $test): array
    {
        return array_values(array_diff(
            ListeningSectionMap::officialSectionNumbers(),
            $this->existingSectionNumbers($test),
        ));
    }

    /**
     * @return list<int>
     */
    public function availableSectionNumbersForEdit(ListeningTest $test, ListeningSection $section): array
    {
        $takenByOthers = $this->query()
            ->withTrashed()
            ->where('listening_test_id', $test->id)
            ->whereKeyNot($section->id)
            ->pluck('section_number')
            ->map(fn ($number) => (int) $number)
            ->all();

        return array_values(array_filter(
            ListeningSectionMap::officialSectionNumbers(),
            fn (int $number) => ! in_array($number, $takenByOthers, true),
        ));
    }

    /**
     * @return list<int>
     */
    public function existingSectionNumbers(ListeningTest $test): array
    {
        return $this->query()
            ->withTrashed()
            ->where('listening_test_id', $test->id)
            ->pluck('section_number')
            ->map(fn ($number) => (int) $number)
            ->all();
    }
}
