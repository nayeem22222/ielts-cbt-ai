<?php

declare(strict_types=1);

namespace App\Actions\Listening\Review;

use App\Models\Listening\ListeningResult;
use App\Models\Listening\ListeningReviewItem;
use App\Repositories\Listening\Review\ListeningReviewItemRepository;
use App\Services\Listening\Review\ListeningReviewBuilderService;
use App\Services\Listening\Review\ListeningReviewVisibilityService;
use Illuminate\Support\Facades\DB;

class BuildListeningReviewItemsAction
{
    public function __construct(
        private readonly ListeningReviewBuilderService $builder,
        private readonly ListeningReviewItemRepository $items,
        private readonly ListeningReviewVisibilityService $visibility,
    ) {}

    public function execute(ListeningResult $result): void
    {
        DB::transaction(function () use ($result): void {
            $this->items->deleteForResult($result);

            $built = $this->builder->buildForResult($result);

            if ($built === []) {
                return;
            }

            foreach ($built as $item) {
                $this->items->create($item->toAttributes());
            }
        });
    }
}
