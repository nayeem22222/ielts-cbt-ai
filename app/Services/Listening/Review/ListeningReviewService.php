<?php

declare(strict_types=1);

namespace App\Services\Listening\Review;

use App\Actions\Listening\Review\BuildListeningReviewItemsAction;
use App\Actions\Listening\Review\FilterListeningReviewForStudentAction;
use App\Actions\Listening\Review\RebuildListeningReviewItemsAction;
use App\Enums\Listening\ListeningResultStatus;
use App\Jobs\Listening\Review\BuildListeningReviewItemsJob;
use App\Models\Listening\ListeningResult;
use App\Models\User;
use App\Repositories\Listening\Review\ListeningReviewItemRepository;

class ListeningReviewService
{
    public function __construct(
        private readonly ListeningReviewItemRepository $items,
        private readonly BuildListeningReviewItemsAction $buildAction,
        private readonly RebuildListeningReviewItemsAction $rebuildAction,
        private readonly ListeningReviewVisibilityService $visibility,
        private readonly ListeningAudioReviewService $audioReview,
        private readonly FilterListeningReviewForStudentAction $filterForStudent,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getReviewForStudent(User $user, ListeningResult $result): array
    {
        $this->ensureReviewItemsExist($result);

        $result->loadMissing(['test', 'attempt']);

        $visibility = $this->visibility->resolveVisibilityData($result, forAdmin: false);
        $reviewItems = $this->items->getForResult($result)
            ->map(fn ($item) => $this->filterForStudent->execute($item, $result))
            ->values()
            ->all();

        return [
            'result' => $result,
            'test' => $result->test,
            'visibility' => $visibility->toArray(),
            'items' => $reviewItems,
            'sections' => $this->buildSectionTabs($reviewItems),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getQuestionReviewForStudent(User $user, ListeningResult $result, int $questionNumber): array
    {
        $this->ensureReviewItemsExist($result);

        $item = $this->items->findForResultByQuestionNumber($result, $questionNumber);

        abort_if($item === null, 404);

        $filtered = $this->filterForStudent->execute($item, $result);
        $visibility = $this->visibility->resolveVisibilityData($result, forAdmin: false);

        if ($visibility->canShowAudioReview) {
            $filtered['audio'] = $this->audioReview
                ->buildAudioReviewPayload($item, $this->audioReview->getSafeAudioUrl($result, $item))
                ->toArray();
        }

        $allItems = $this->items->getForResult($result);

        return [
            'result' => $result,
            'test' => $result->test,
            'item' => $filtered,
            'visibility' => $visibility->toArray(),
            'questionNumbers' => $allItems->pluck('question_number')->all(),
            'prevQuestion' => $allItems->where('question_number', '<', $questionNumber)->max('question_number'),
            'nextQuestion' => $allItems->where('question_number', '>', $questionNumber)->min('question_number'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getReviewForAdmin(ListeningResult $result): array
    {
        $this->ensureReviewItemsExist($result);

        $result->loadMissing(['user', 'test', 'attempt', 'evaluation']);

        return [
            'result' => $result,
            'visibility' => $this->visibility->resolveVisibilityData($result, forAdmin: true)->toArray(),
            'items' => $this->items->getForResult($result)
                ->map(fn ($item) => $this->visibility->filterItemForAdmin($item))
                ->values()
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getQuestionReviewForAdmin(ListeningResult $result, int $questionNumber): array
    {
        $this->ensureReviewItemsExist($result);

        $item = $this->items->findForResultByQuestionNumber($result, $questionNumber);

        abort_if($item === null, 404);

        $allItems = $this->items->getForResult($result);

        return [
            'result' => $result,
            'item' => $this->visibility->filterItemForAdmin($item),
            'questionNumbers' => $allItems->pluck('question_number')->all(),
            'prevQuestion' => $allItems->where('question_number', '<', $questionNumber)->max('question_number'),
            'nextQuestion' => $allItems->where('question_number', '>', $questionNumber)->min('question_number'),
        ];
    }

    public function ensureReviewItemsExist(ListeningResult $result): void
    {
        if ($result->status !== ListeningResultStatus::Ready) {
            return;
        }

        if ($this->items->countForResult($result) > 0) {
            return;
        }

        if (config('listening.review.build_after_result', true)) {
            $this->buildAction->execute($result);

            return;
        }

        BuildListeningReviewItemsJob::dispatch($result->id);
    }

    public function rebuildReviewItems(ListeningResult $result): void
    {
        $this->rebuildAction->execute($result);
    }

    public function dispatchBuildForResult(ListeningResult $result): void
    {
        if (! config('listening.review.enabled', true) || ! config('listening.review.build_after_result', true)) {
            return;
        }

        if ($result->status !== ListeningResultStatus::Ready) {
            return;
        }

        BuildListeningReviewItemsJob::dispatch($result->id);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function buildSectionTabs(array $items): array
    {
        $sections = [];

        foreach ($items as $item) {
            $num = (int) ($item['section_number'] ?? 1);
            $sections[$num] = ($sections[$num] ?? 0) + 1;
        }

        ksort($sections);

        return array_map(
            fn (int $section, int $count): array => ['section_number' => $section, 'count' => $count],
            array_keys($sections),
            array_values($sections),
        );
    }
}
