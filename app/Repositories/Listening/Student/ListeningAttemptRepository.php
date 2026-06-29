<?php

declare(strict_types=1);

namespace App\Repositories\Listening\Student;

use App\Enums\Listening\ListeningAttemptStatus;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAttemptAnswer;
use Illuminate\Support\Collection;

class ListeningAttemptRepository
{
    public function findForUser(int $attemptId, int $userId): ?ListeningAttempt
    {
        return ListeningAttempt::query()
            ->whereKey($attemptId)
            ->where('user_id', $userId)
            ->first();
    }

    public function findInProgressForUserAndTest(int $userId, int $testId): ?ListeningAttempt
    {
        return ListeningAttempt::query()
            ->forUser($userId)
            ->where('listening_test_id', $testId)
            ->where('status', ListeningAttemptStatus::InProgress)
            ->latest('id')
            ->first();
    }

    public function create(array $attributes): ListeningAttempt
    {
        return ListeningAttempt::query()->create($attributes);
    }

    public function update(ListeningAttempt $attempt, array $attributes): ListeningAttempt
    {
        $attempt->fill($attributes)->save();

        return $attempt->refresh();
    }

    /**
     * @return Collection<int, ListeningAttemptAnswer>
     */
    public function answersForAttempt(ListeningAttempt $attempt): Collection
    {
        return $attempt->answers()->orderBy('question_number')->get();
    }
}
