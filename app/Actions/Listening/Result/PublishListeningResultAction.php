<?php

declare(strict_types=1);

namespace App\Actions\Listening\Result;

use App\Enums\Listening\ListeningResultStatus;
use App\Models\Listening\ListeningResult;
use App\Repositories\Listening\Result\ListeningResultRepository;

class PublishListeningResultAction
{
    public function __construct(
        private readonly ListeningResultRepository $results,
    ) {}

    public function execute(ListeningResult $result): ListeningResult
    {
        $status = $result->status === ListeningResultStatus::Hidden
            ? ListeningResultStatus::Ready
            : $result->status;

        if ($status === ListeningResultStatus::Pending || $status === ListeningResultStatus::Failed) {
            $status = ListeningResultStatus::Ready;
        }

        return $this->results->update($result, [
            'is_visible_to_student' => true,
            'published_at' => now(),
            'status' => $status->value,
        ]);
    }
}
