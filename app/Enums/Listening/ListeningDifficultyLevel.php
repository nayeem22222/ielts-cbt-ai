<?php

declare(strict_types=1);

namespace App\Enums\Listening;

use App\Enums\Listening\Concerns\ListeningEnum;

enum ListeningDifficultyLevel: string
{
    use ListeningEnum;

    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';
    case Official = 'official';

    public function label(): string
    {
        return match ($this) {
            self::Easy => 'Easy',
            self::Medium => 'Medium',
            self::Hard => 'Hard',
            self::Official => 'Official',
        };
    }
}
