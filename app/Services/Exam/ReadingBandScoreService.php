<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Enums\Course\ExamType;

class ReadingBandScoreService
{
    /**
     * @return array<int, float>
     */
    private function tableForExamType(string $examType): array
    {
        $key = match ($examType) {
            ExamType::General->value, 'general_training', 'general' => 'general',
            default => 'academic',
        };

        /** @var array<int, float> $table */
        $table = config("reading.band_tables.{$key}", config('reading.band_tables.academic', []));

        return $table;
    }

    public function getBand(int $rawScore, string $examType): float
    {
        $raw = max(0, min(40, $rawScore));
        $table = $this->tableForExamType($examType);

        return $table[$raw] ?? 0.0;
    }

    public function getBandFromRatio(float $rawScore, int $totalQuestions, string $examType): float
    {
        if ($totalQuestions <= 0) {
            return 0.0;
        }

        $scaledRaw = (int) round(($rawScore / $totalQuestions) * 40);

        return $this->getBand($scaledRaw, $examType);
    }
}
