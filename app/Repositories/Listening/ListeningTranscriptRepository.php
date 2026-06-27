<?php

declare(strict_types=1);

namespace App\Repositories\Listening;

use App\Enums\Listening\ListeningTranscriptVisibility;
use App\Models\Listening\ListeningTranscript;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ListeningTranscriptRepository
{
    public function query(): Builder
    {
        return ListeningTranscript::query();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function adminQuery(array $filters): Builder
    {
        $query = $this->query()
            ->with(['audio', 'createdBy'])
            ->withCount('sections');

        if (! empty($filters['search'])) {
            $query->where(fn (Builder $builder) => $this->applySearch($builder, (string) $filters['search']));
        }

        if (! empty($filters['audio_id'])) {
            $query->where('listening_audio_id', (int) $filters['audio_id']);
        }

        if (! empty($filters['visibility'])) {
            $query->where('visibility', $filters['visibility']);
        }

        if (array_key_exists('is_official', $filters) && $filters['is_official'] !== '' && $filters['is_official'] !== null) {
            $query->where('is_official', filter_var($filters['is_official'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['language'])) {
            $query->where('language', $filters['language']);
        }

        if (! empty($filters['source_type'])) {
            $query->where('source_type', $filters['source_type']);
        }

        if (! empty($filters['created_by'])) {
            $query->where('created_by', (int) $filters['created_by']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $sortBy = (string) ($filters['sort_by'] ?? 'created_at');
        $allowedSorts = ['id', 'title', 'language', 'visibility', 'created_at', 'is_official'];

        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }

        $sortDirection = strtolower((string) ($filters['sort_direction'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortBy, $sortDirection);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdmin(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->adminQuery($filters)->paginate($perPage)->withQueryString();
    }

    public function findWithRelations(int $id): ?ListeningTranscript
    {
        return $this->query()
            ->with(['audio', 'createdBy', 'sections.audio', 'sections.test'])
            ->withCount('sections')
            ->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ListeningTranscript
    {
        return $this->query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ListeningTranscript $transcript, array $data): ListeningTranscript
    {
        $transcript->update($data);

        return $transcript->refresh();
    }

    public function delete(ListeningTranscript $transcript): bool
    {
        return (bool) $transcript->delete();
    }

    /**
     * @return Collection<int, ListeningTranscript>
     */
    public function getByAudio(?int $audioId): Collection
    {
        $query = $this->query()->orderBy('title');

        if ($audioId === null) {
            return $query->whereNull('listening_audio_id')->get();
        }

        return $query->where('listening_audio_id', $audioId)->get();
    }

    /**
     * @return Collection<int, ListeningTranscript>
     */
    public function getOfficial(): Collection
    {
        return $this->query()
            ->where('is_official', true)
            ->orderBy('title')
            ->get();
    }

    /**
     * @return Collection<int, ListeningTranscript>
     */
    public function getReviewVisible(): Collection
    {
        return $this->query()
            ->where('visibility', ListeningTranscriptVisibility::ReviewVisible)
            ->orderBy('title')
            ->get();
    }

    /**
     * @return Collection<int, ListeningTranscript>
     */
    public function search(string $term): Collection
    {
        return $this->query()
            ->where(fn (Builder $builder) => $this->applySearch($builder, $term))
            ->orderBy('title')
            ->limit(50)
            ->get();
    }

    /**
     * @return Collection<int, ListeningTranscript>
     */
    public function getAvailableForSection(?int $audioId = null): Collection
    {
        return $this->query()
            ->when($audioId !== null, function (Builder $query) use ($audioId): void {
                $query->where(function (Builder $builder) use ($audioId): void {
                    $builder->where('listening_audio_id', $audioId)
                        ->orWhereNull('listening_audio_id');
                });
            })
            ->orderByRaw('CASE WHEN listening_audio_id IS NULL THEN 1 ELSE 0 END')
            ->orderBy('title')
            ->get();
    }

    private function applySearch(Builder $query, string $term): void
    {
        $like = '%'.$term.'%';

        $query->where(function (Builder $builder) use ($like): void {
            $builder->where('title', 'like', $like)
                ->orWhere('passage_title', 'like', $like)
                ->orWhere('transcript_text', 'like', $like)
                ->orWhere('formatted_transcript', 'like', $like)
                ->orWhere('passage_note', 'like', $like);
        });
    }
}
