<?php

declare(strict_types=1);

namespace App\Enums\Listening;

/**
 * Enterprise rules for IELTS Listening CBT (Volume 1 constants).
 */
final class ListeningConstants
{
    public const TOTAL_SECTIONS = 4;

    public const TOTAL_QUESTIONS = 40;

    public const DEFAULT_TOTAL_MARKS = 40;

    public const DEFAULT_DURATION_MINUTES = 30;

    public const DEFAULT_TRANSFER_TIME_MINUTES = 10;

    public const MIN_QUESTION_NUMBER = 1;

    public const MAX_QUESTION_NUMBER = 40;

    public const MIN_SECTION_NUMBER = 1;

    public const MAX_SECTION_NUMBER = 4;

    /** @var array<int, array{start: int, end: int}> */
    public const SECTION_QUESTION_RANGES = [
        1 => ['start' => 1, 'end' => 10],
        2 => ['start' => 11, 'end' => 20],
        3 => ['start' => 21, 'end' => 30],
        4 => ['start' => 31, 'end' => 40],
    ];

    public const DEFAULT_AUTO_SAVE_INTERVAL_SECONDS = 10;

    private function __construct()
    {
    }
}
