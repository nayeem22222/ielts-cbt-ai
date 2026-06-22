<?php

declare(strict_types=1);

namespace App\Enums\Exam;

use App\Enums\Concerns\EnumHelpers;

enum TestType: string
{
    use EnumHelpers;

    case ReadingTest = 'reading_test';
    case FullMock = 'full_mock';
    case ModulePractice = 'module_practice';

    public function label(): string
    {
        return match ($this) {
            self::ReadingTest => 'Reading Test',
            self::FullMock => 'Full Mock Test',
            self::ModulePractice => 'Module Practice',
        };
    }
}
