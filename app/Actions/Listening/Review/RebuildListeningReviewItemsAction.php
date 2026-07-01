<?php

declare(strict_types=1);

namespace App\Actions\Listening\Review;

use App\Models\Listening\ListeningResult;

class RebuildListeningReviewItemsAction
{
    public function __construct(
        private readonly BuildListeningReviewItemsAction $build,
    ) {}

    public function execute(ListeningResult $result): void
    {
        $this->build->execute($result);
    }
}
