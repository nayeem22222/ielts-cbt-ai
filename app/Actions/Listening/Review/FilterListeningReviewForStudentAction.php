<?php

declare(strict_types=1);

namespace App\Actions\Listening\Review;

use App\Models\Listening\ListeningResult;
use App\Models\Listening\ListeningReviewItem;
use App\Services\Listening\Review\ListeningReviewVisibilityService;

class FilterListeningReviewForStudentAction
{
    public function __construct(
        private readonly ListeningReviewVisibilityService $visibility,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(ListeningReviewItem $item, ListeningResult $result): array
    {
        return $this->visibility->filterItemForStudent($item, $result);
    }
}
