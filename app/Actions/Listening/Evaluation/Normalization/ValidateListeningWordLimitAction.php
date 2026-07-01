<?php

declare(strict_types=1);

namespace App\Actions\Listening\Evaluation\Normalization;

use App\DTOs\Listening\Evaluation\Normalization\WordLimitResultData;
use App\Models\Listening\ListeningQuestion;
use App\Services\Listening\Evaluation\Normalization\ListeningWordLimitService;

class ValidateListeningWordLimitAction
{
    public function __construct(
        private readonly ListeningWordLimitService $wordLimit,
    ) {}

    public function execute(mixed $answer, ListeningQuestion $question): WordLimitResultData
    {
        return $this->wordLimit->check($answer, $question);
    }
}
