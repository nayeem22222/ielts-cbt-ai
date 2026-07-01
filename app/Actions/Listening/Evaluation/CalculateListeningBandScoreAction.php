<?php

declare(strict_types=1);

namespace App\Actions\Listening\Evaluation;

use App\DTOs\Listening\Evaluation\ListeningBandScoreData;
use App\Services\Listening\Evaluation\ListeningBandScoreService;

class CalculateListeningBandScoreAction
{
    public function __construct(
        private readonly ListeningBandScoreService $bandScoreService,
    ) {}

    public function execute(float $rawScore, int $totalQuestions = 40): ListeningBandScoreData
    {
        return $this->bandScoreService->calculate($rawScore, $totalQuestions);
    }
}
