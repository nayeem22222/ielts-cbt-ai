<?php

declare(strict_types=1);

namespace App\Services\Exam\Scoring;

class ReadingBandConverter
{
    /**
     * Official-style Academic Reading conversion keyed by raw score out of 40.
     *
     * @var array<int, float>
     */
    private const ACADEMIC_BAND_TABLE = [
        40 => 9.0,
        39 => 9.0,
        38 => 8.5,
        37 => 8.5,
        36 => 8.0,
        35 => 8.0,
        34 => 7.5,
        33 => 7.5,
        32 => 7.0,
        31 => 7.0,
        30 => 7.0,
        29 => 6.5,
        28 => 6.5,
        27 => 6.5,
        26 => 6.0,
        25 => 6.0,
        24 => 6.0,
        23 => 6.0,
        22 => 5.5,
        21 => 5.5,
        20 => 5.5,
        19 => 5.5,
        18 => 5.0,
        17 => 5.0,
        16 => 5.0,
        15 => 5.0,
        14 => 4.5,
        13 => 4.5,
        12 => 4.0,
        11 => 4.0,
        10 => 4.0,
        9 => 3.5,
        8 => 3.5,
        7 => 3.0,
        6 => 3.0,
        5 => 2.5,
        4 => 2.5,
        3 => 2.0,
        2 => 2.0,
        1 => 1.0,
        0 => 0.0,
    ];

    public function bandFromScores(float $rawScore, float $maxScore): float
    {
        if ($maxScore <= 0) {
            return 0.0;
        }

        $equivalentOutOf40 = ($rawScore / $maxScore) * 40;

        return $this->bandFromRawOutOf40($equivalentOutOf40);
    }

    public function bandFromRawOutOf40(float $rawOutOf40): float
    {
        $raw = (int) round(max(0, min(40, $rawOutOf40)));

        return self::ACADEMIC_BAND_TABLE[$raw] ?? 0.0;
    }
}
