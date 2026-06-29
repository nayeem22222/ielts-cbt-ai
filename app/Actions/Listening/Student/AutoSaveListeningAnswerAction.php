<?php

declare(strict_types=1);

namespace App\Actions\Listening\Student;

use App\DTOs\Listening\Student\AutoSaveResultData;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningQuestion;
use App\Services\Listening\Student\ListeningAutoSaveService;

class AutoSaveListeningAnswerAction
{
    public function __construct(
        private readonly ListeningAutoSaveService $autoSave,
    ) {}

    /**
     * @param  array<string, mixed>  $meta
     */
    public function execute(
        ListeningAttempt $attempt,
        ListeningQuestion $question,
        mixed $answer,
        array $meta = [],
    ): AutoSaveResultData {
        return $this->autoSave->saveAnswer($attempt, $question, $answer, $meta);
    }
}
