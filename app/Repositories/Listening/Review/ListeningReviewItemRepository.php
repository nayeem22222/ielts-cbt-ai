<?php

declare(strict_types=1);

namespace App\Repositories\Listening\Review;

use App\Models\Listening\ListeningResult;
use App\Models\Listening\ListeningReviewItem;
use Illuminate\Support\Collection;

class ListeningReviewItemRepository
{
    public function create(array $attributes): ListeningReviewItem
    {
        return ListeningReviewItem::query()->create($attributes);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function insertMany(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $now = now();

        foreach ($rows as &$row) {
            $row['created_at'] = $now;
            $row['updated_at'] = $now;
        }

        ListeningReviewItem::query()->insert($rows);
    }

    public function deleteForResult(ListeningResult $result): int
    {
        return ListeningReviewItem::query()
            ->where('listening_result_id', $result->id)
            ->delete();
    }

    public function countForResult(ListeningResult $result): int
    {
        return ListeningReviewItem::query()
            ->where('listening_result_id', $result->id)
            ->count();
    }

    /**
     * @return Collection<int, ListeningReviewItem>
     */
    public function getForResult(ListeningResult $result): Collection
    {
        return ListeningReviewItem::query()
            ->where('listening_result_id', $result->id)
            ->orderBy('question_number')
            ->get();
    }

    public function findForResultByQuestionNumber(ListeningResult $result, int $questionNumber): ?ListeningReviewItem
    {
        return ListeningReviewItem::query()
            ->where('listening_result_id', $result->id)
            ->where('question_number', $questionNumber)
            ->first();
    }
}
