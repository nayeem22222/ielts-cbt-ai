<?php

declare(strict_types=1);

namespace App\Enums\Exam;

enum ReadingHighlightColor: string
{
    case Yellow = 'yellow';
    case Green = 'green';
    case Blue = 'blue';

    public function label(): string
    {
        return match ($this) {
            self::Yellow => 'Yellow',
            self::Green => 'Green',
            self::Blue => 'Blue',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
