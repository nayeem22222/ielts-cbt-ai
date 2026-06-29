<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningQuestion;
use App\Services\Listening\Student\ListeningAnswerSaveService;

class SaveListeningAnswerAction
{
    public function __construct(
        private readonly ListeningAnswerSaveService $saveService,
    ) {}

    public function execute(ListeningAttempt $attempt, ListeningQuestion $question, mixed $studentAnswer)
    {
        return $this->saveService->save($attempt, $question, $studentAnswer);
    }
}
