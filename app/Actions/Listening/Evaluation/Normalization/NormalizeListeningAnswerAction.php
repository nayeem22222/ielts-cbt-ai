<?php

declare(strict_types=1);

namespace App\Actions\Listening\Evaluation\Normalization;

use App\DTOs\Listening\Evaluation\Normalization\NormalizedListeningAnswerData;
use App\Models\Listening\ListeningQuestion;
use App\Services\Listening\Evaluation\Normalization\ListeningNormalizationPipeline;

class NormalizeListeningAnswerAction
{
    public function __construct(
        private readonly ListeningNormalizationPipeline $pipeline,
    ) {}

    public function execute(mixed $answer, ListeningQuestion $question): NormalizedListeningAnswerData
    {
        return $this->pipeline->normalize($answer, $question);
    }
}
