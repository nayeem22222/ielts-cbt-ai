<?php

declare(strict_types=1);

namespace App\Repositories\Listening;

use App\Models\Listening\ListeningTest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListeningTestRepository
{
    public function query(): Builder
    {
        return ListeningTest::query();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function adminQuery(array $filters): Builder
    {
        $query = $this->query()
            ->with(['createdBy'])
            ->withCount(['sections', 'questions', 'questionGroups']);

        $trashed = (string) ($filters['trashed'] ?? '');

        if ($trashed === 'only') {
            $query->onlyTrashed();
        } elseif ($trashed === 'with') {
            $query->withTrashed();
        }

        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('test_code', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['test_type'])) {
            $query->where('test_type', $filters['test_type']);
        }

        if (! empty($filters['difficulty_level'])) {
            $query->where('difficulty_level', $filters['difficulty_level']);
        }

        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== '' && $filters['is_active'] !== null) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (array_key_exists('is_featured', $filters) && $filters['is_featured'] !== '' && $filters['is_featured'] !== null) {
            $query->where('is_featured', filter_var($filters['is_featured'], FILTER_VALIDATE_BOOLEAN));
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
        $allowedSorts = ['id', 'title', 'test_code', 'status', 'created_at', 'published_at', 'duration_minutes'];

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

    public function findWithRelations(int $id): ?ListeningTest
    {
        return $this->query()
            ->with(['setting', 'createdBy', 'updatedBy'])
            ->withCount(['sections', 'questions', 'questionGroups'])
            ->find($id);
    }

    public function findTrashed(int $id): ?ListeningTest
    {
        return $this->query()->onlyTrashed()->find($id);
    }

    public function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $query = $this->query()->where('slug', $slug);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    public function testCodeExists(string $testCode, ?int $ignoreId = null): bool
    {
        $query = $this->query()->where('test_code', $testCode);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        return $query->exists();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ListeningTest
    {
        return ListeningTest::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(ListeningTest $test, array $data): ListeningTest
    {
        $test->fill($data);
        $test->save();

        return $test->refresh();
    }

    public function delete(ListeningTest $test): bool
    {
        return (bool) $test->delete();
    }

    public function restore(int $id): ?ListeningTest
    {
        $test = $this->findTrashed($id);

        if ($test === null) {
            return null;
        }

        $test->restore();

        return $test->refresh();
    }
}
