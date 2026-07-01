<?php

declare(strict_types=1);

namespace App\Repositories\Listening\Evaluation;

use App\Enums\Listening\ListeningEvaluationStatus;
use App\Enums\Listening\ListeningEvaluationType;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAttemptEvaluation;
use Illuminate\Support\Collection;

class ListeningAttemptEvaluationRepository
{
    public function create(array $attributes): ListeningAttemptEvaluation
    {
        return ListeningAttemptEvaluation::query()->create($attributes);
    }

    public function update(ListeningAttemptEvaluation $evaluation, array $attributes): ListeningAttemptEvaluation
    {
        $evaluation->fill($attributes)->save();

        return $evaluation->refresh();
    }

    public function find(int $id): ?ListeningAttemptEvaluation
    {
        return ListeningAttemptEvaluation::query()->find($id);
    }

    public function getLatestForAttempt(ListeningAttempt $attempt): ?ListeningAttemptEvaluation
    {
        return ListeningAttemptEvaluation::query()
            ->where('listening_attempt_id', $attempt->id)
            ->latest('id')
            ->first();
    }

    public function hasActiveEvaluation(ListeningAttempt $attempt): bool
    {
        return ListeningAttemptEvaluation::query()
            ->where('listening_attempt_id', $attempt->id)
            ->whereIn('status', [
                ListeningEvaluationStatus::Pending->value,
                ListeningEvaluationStatus::Processing->value,
            ])
            ->exists();
    }

    public function findCompletedSystemEvaluation(ListeningAttempt $attempt, string $version): ?ListeningAttemptEvaluation
    {
        return ListeningAttemptEvaluation::query()
            ->where('listening_attempt_id', $attempt->id)
            ->where('evaluation_type', ListeningEvaluationType::System->value)
            ->where('evaluation_version', $version)
            ->where('status', ListeningEvaluationStatus::Completed->value)
            ->latest('id')
            ->first();
    }

    /**
     * @return Collection<int, ListeningAttempt>
     */
    public function findAttemptsNeedingEvaluation(int $limit = 100): Collection
    {
        return ListeningAttempt::query()
            ->whereIn('status', ['submitted', 'auto_submitted', 'expired'])
            ->where(function ($query): void {
                $query->whereNull('evaluation_status')
                    ->orWhereIn('evaluation_status', [
                        ListeningEvaluationStatus::Pending->value,
                        ListeningEvaluationStatus::Failed->value,
                    ]);
            })
            ->orderBy('submitted_at')
            ->limit($limit)
            ->get();
    }
}
