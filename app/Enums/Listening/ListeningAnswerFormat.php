<?php

declare(strict_types=1);

namespace App\Enums\Listening;

use App\Enums\Listening\Concerns\ListeningEnum;

enum ListeningAnswerFormat: string
{
    use ListeningEnum;

    case Text = 'text';
    case Number = 'number';
    case Letter = 'letter';
    case Multiple = 'multiple';
    case MapLabel = 'map_label';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Number => 'Number',
            self::Letter => 'Letter',
            self::Multiple => 'Multiple',
            self::MapLabel => 'Map Label',
        };
    }
}
