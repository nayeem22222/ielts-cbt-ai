<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\Models\Listening\ListeningAttempt;
use App\Services\Listening\Student\ListeningAnswerSaveService;

class BulkSaveListeningAnswersAction
{
    public function __construct(
        private readonly ListeningAnswerSaveService $saveService,
    ) {}

    /**
     * @param  list<array{question_id: int, student_answer: mixed}>  $items
     */
    public function execute(ListeningAttempt $attempt, array $items): array
    {
        return $this->saveService->bulkSave($attempt, $items);
    }
}
