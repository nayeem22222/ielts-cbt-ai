<?php

declare(strict_types=1);

namespace App\Enums\Listening;

use App\Enums\Listening\Concerns\ListeningEnum;

enum ListeningMarkerType: string
{
    use ListeningEnum;

    case QuestionStart = 'question_start';
    case QuestionEnd = 'question_end';
    case GroupStart = 'group_start';
    case GroupEnd = 'group_end';
    case Instruction = 'instruction';
    case AnswerLocation = 'answer_location';

    public function label(): string
    {
        return match ($this) {
            self::QuestionStart => 'Question Start',
            self::QuestionEnd => 'Question End',
            self::GroupStart => 'Group Start',
            self::GroupEnd => 'Group End',
            self::Instruction => 'Instruction',
            self::AnswerLocation => 'Answer Location',
        };
    }
}
