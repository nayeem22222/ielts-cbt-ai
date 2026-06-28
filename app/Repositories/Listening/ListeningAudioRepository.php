<?php

declare(strict_types=1);

namespace App\Repositories\Listening;

use App\Enums\Listening\ListeningAudioProcessingStatus;
use App\Enums\Listening\ListeningAudioValidationStatus;
use App\Models\Listening\ListeningAudio;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ListeningAudioRepository
{
    public function query(): Builder
    {
        return ListeningAudio::query();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function adminQuery(array $filters): Builder
    {
        $query = $this->query()->with('uploadedBy');

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('original_name', 'like', "%{$search}%")
                    ->orWhere('stored_name', 'like', "%{$search}%")
                    ->orWhere('format', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['processing_status'])) {
            $query->where('processing_status', $filters['processing_status']);
        }

        if (! empty($filters['validation_status'])) {
            $query->where('validation_status', $filters['validation_status']);
        }

        if (! empty($filters['format'])) {
            $query->where('format', $filters['format']);
        }

        if (! empty($filters['uploaded_by'])) {
            $query->where('uploaded_by', (int) $filters['uploaded_by']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderByDesc('created_at');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdmin(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->adminQuery($filters)->paginate($perPage)->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ListeningAudio
    {
        return ListeningAudio::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ListeningAudio $audio, array $data): ListeningAudio
    {
        $audio->fill($data);
        $audio->save();

        return $audio->refresh();
    }

    public function delete(ListeningAudio $audio): bool
    {
        return (bool) $audio->delete();
    }

    public function findByChecksum(string $checksum): ?ListeningAudio
    {
        return $this->query()->where('checksum', $checksum)->first();
    }

    /**
     * @return Collection<int, ListeningAudio>
     */
    public function selectableForSections(bool $includeAll = false): Collection
    {
        $query = $this->query()->orderBy('original_name');

        if (! $includeAll) {
            $query->where('processing_status', ListeningAudioProcessingStatus::Completed)
                ->where('validation_status', ListeningAudioValidationStatus::Valid);
        }

        return $query->get([
            'id',
            'original_name',
            'duration_seconds',
            'processing_status',
            'validation_status',
            'format',
        ]);
    }
}
