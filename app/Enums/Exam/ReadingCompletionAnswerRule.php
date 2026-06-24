<?php

declare(strict_types=1);

namespace App\Enums\Exam;

use App\Enums\Concerns\EnumHelpers;

enum ReadingCompletionAnswerRule: string
{
    use EnumHelpers;

    case OneWord = 'one_word';
    case OneWordOnly = 'one_word_only';
    case OneWordAndOrNumber = 'one_word_and_or_number';
    case TwoWords = 'two_words';
    case ThreeWords = 'three_words';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::OneWord => 'ONE WORD',
            self::OneWordOnly => 'ONE WORD ONLY',
            self::OneWordAndOrNumber => 'ONE WORD AND/OR A NUMBER',
            self::TwoWords => 'TWO WORDS',
            self::ThreeWords => 'THREE WORDS',
            self::Custom => 'CUSTOM',
        };
    }
}
