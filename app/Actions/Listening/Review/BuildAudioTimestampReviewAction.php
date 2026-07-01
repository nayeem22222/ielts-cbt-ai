<?php

declare(strict_types=1);

namespace App\Actions\Listening\Review;

class BuildAudioTimestampReviewAction
{
    /**
     * @param  array{start: ?float, end: ?float}  $ref
     * @return array{start: ?float, end: ?float}
     */
    public function execute(array $ref): array
    {
        return [
            'start' => $ref['start'] ?? null,
            'end' => $ref['end'] ?? null,
        ];
    }
}
