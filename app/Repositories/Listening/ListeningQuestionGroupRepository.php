<?php

declare(strict_types=1);

namespace App\Repositories\Listening;

use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ListeningQuestionGroupRepository
{
    public function query(): Builder
    {
        return ListeningQuestionGroup::query();
    }

    /**
     * @return Collection<int, ListeningQuestionGroup>
     */
    public function forSection(ListeningSection $section, bool $withTrashed = false): Collection
    {
        $query = $this->query()
            ->where('listening_section_id', $section->id)
            ->withCount('questions')
            ->ordered();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->get();
    }

    public function findForSection(ListeningSection $section, int $groupId): ?ListeningQuestionGroup
    {
        return $this->query()
            ->where('listening_section_id', $section->id)
            ->withCount('questions')
            ->find($groupId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ListeningQuestionGroup
    {
        return $this->query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ListeningQuestionGroup $group, array $data): ListeningQuestionGroup
    {
        $group->update($data);

        return $group->refresh();
    }

    public function delete(ListeningQuestionGroup $group): bool
    {
        return (bool) $group->delete();
    }

    public function rangeOverlaps(ListeningSection $section, int $start, int $end, ?int $ignoreGroupId = null): bool
    {
        $query = $this->query()
            ->where('listening_section_id', $section->id)
            ->where(function (Builder $builder) use ($start, $end): void {
                $builder->whereBetween('start_question_number', [$start, $end])
                    ->orWhereBetween('end_question_number', [$start, $end])
                    ->orWhere(function (Builder $nested) use ($start, $end): void {
                        $nested->where('start_question_number', '<=', $start)
                            ->where('end_question_number', '>=', $end);
                    });
            });

        if ($ignoreGroupId !== null) {
            $query->whereKeyNot($ignoreGroupId);
        }

        return $query->exists();
    }

    public function countForSection(ListeningSection $section): int
    {
        return $this->query()->where('listening_section_id', $section->id)->count();
    }
}
