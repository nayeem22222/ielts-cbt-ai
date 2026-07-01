<?php

declare(strict_types=1);

namespace App\Actions\Listening\Result;

use App\Enums\Listening\ListeningResultStatus;
use App\Models\Listening\ListeningResult;
use App\Repositories\Listening\Result\ListeningResultRepository;

class HideListeningResultAction
{
    public function __construct(
        private readonly ListeningResultRepository $results,
    ) {}

    public function execute(ListeningResult $result): ListeningResult
    {
        return $this->results->update($result, [
            'is_visible_to_student' => false,
            'status' => ListeningResultStatus::Hidden->value,
        ]);
    }
}
