<?php

declare(strict_types=1);

namespace App\Repositories\Listening\Result;

use App\Enums\Listening\ListeningResultStatus;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningResult;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ListeningResultRepository
{
    public function create(array $attributes): ListeningResult
    {
        return ListeningResult::query()->create($attributes);
    }

    public function update(ListeningResult $result, array $attributes): ListeningResult
    {
        $result->fill($attributes)->save();

        return $result->refresh();
    }

    public function find(int $id): ?ListeningResult
    {
        return ListeningResult::query()->find($id);
    }

    public function findForStudent(User $user, int $resultId): ?ListeningResult
    {
        return ListeningResult::query()
            ->where('user_id', $user->id)
            ->whereKey($resultId)
            ->first();
    }

    public function findLatestByAttempt(ListeningAttempt $attempt): ?ListeningResult
    {
        return $this->findLatestByAttemptId((int) $attempt->id);
    }

    public function findLatestByAttemptId(int $attemptId): ?ListeningResult
    {
        return ListeningResult::query()
            ->where('listening_attempt_id', $attemptId)
            ->latest('id')
            ->first();
    }

    public function findByEvaluationId(int $evaluationId): ?ListeningResult
    {
        return ListeningResult::query()
            ->where('listening_attempt_evaluation_id', $evaluationId)
            ->latest('id')
            ->first();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForStudent(User $user, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->applyStudentFilters(
            ListeningResult::query()
                ->where('user_id', $user->id)
                ->where('is_visible_to_student', true)
                ->where('status', '!=', ListeningResultStatus::Hidden->value),
            $filters,
        )
            ->with(['test:id,title,slug'])
            ->latest('submitted_at')
            ->paginate($perPage);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForAdmin(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->applyAdminFilters(ListeningResult::query(), $filters)
            ->with(['user:id,name,email', 'test:id,title,slug'])
            ->latest('evaluated_at')
            ->paginate($perPage);
    }

    public function nextSequenceForYear(int $year): int
    {
        return (int) ListeningResult::query()
            ->withTrashed()
            ->whereYear('created_at', $year)
            ->count() + 1;
    }

    public function resultCodeExists(string $code): bool
    {
        return ListeningResult::query()
            ->withTrashed()
            ->where('result_code', $code)
            ->exists();
    }

    /**
     * @param  Builder<ListeningResult>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<ListeningResult>
     */
    private function applyStudentFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query;
    }

    /**
     * @param  Builder<ListeningResult>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<ListeningResult>
     */
    private function applyAdminFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function (Builder $q) use ($search): void {
                $q->where('result_code', 'like', "%{$search}%")
                    ->orWhereHas('user', fn (Builder $u) => $u
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('test', fn (Builder $t) => $t->where('title', 'like', "%{$search}%"));
            });
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['listening_test_id'])) {
            $query->where('listening_test_id', (int) $filters['listening_test_id']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['band_score']) && $filters['band_score'] !== '') {
            $query->where('band_score', (float) $filters['band_score']);
        }

        if (isset($filters['is_visible_to_student']) && $filters['is_visible_to_student'] !== '') {
            $query->where('is_visible_to_student', filter_var($filters['is_visible_to_student'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('submitted_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('submitted_at', '<=', $filters['date_to']);
        }

        return $query;
    }
}
