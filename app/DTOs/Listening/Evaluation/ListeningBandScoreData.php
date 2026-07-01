<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Evaluation;

final readonly class ListeningBandScoreData
{
    public function __construct(
        public float $rawScore,
        public float $bandScore,
        public int $totalQuestions,
    ) {}
}
