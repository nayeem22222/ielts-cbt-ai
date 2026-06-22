<?php

declare(strict_types=1);

namespace App\Enums\Exam;

use App\Enums\Concerns\EnumHelpers;

enum AnswerType: string
{
    use EnumHelpers;

    case Text = 'text';
    case Option = 'option';
    case OptionIds = 'option_ids';
    case Json = 'json';
    case Ordered = 'ordered';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Option => 'Single option',
            self::OptionIds => 'Multiple options',
            self::Json => 'Structured JSON',
            self::Ordered => 'Ordered list',
        };
    }
}
