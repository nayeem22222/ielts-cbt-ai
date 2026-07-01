<?php

declare(strict_types=1);

namespace App\Services\Listening\Evaluation;

use App\DTOs\Listening\Evaluation\ListeningBandScoreData;

class ListeningBandScoreService
{
    public function calculate(float $rawScore, int $totalQuestions = 40): ListeningBandScoreData
    {
        $rounded = (int) round($rawScore);
        $band = $this->bandForRawScore($rounded);

        return new ListeningBandScoreData(
            rawScore: $rawScore,
            bandScore: $band,
            totalQuestions: $totalQuestions,
        );
    }

    public function bandForRawScore(int $rawScore): float
    {
        $map = (array) config('listening.answer_engine.band_score_map', []);

        foreach ($map as $entry) {
            $min = (int) ($entry['min'] ?? 0);
            $max = (int) ($entry['max'] ?? 0);

            if ($rawScore >= $min && $rawScore <= $max) {
                return (float) ($entry['band'] ?? 0);
            }
        }

        return 0.0;
    }
}
