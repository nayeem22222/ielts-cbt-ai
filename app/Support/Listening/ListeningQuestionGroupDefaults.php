<?php

declare(strict_types=1);

namespace App\Support\Listening;

use App\Enums\Listening\ListeningQuestionType;

final class ListeningQuestionGroupDefaults
{
    public static function title(int $start, int $end): string
    {
        if ($start === $end) {
            return 'Question '.$start;
        }

        return 'Questions '.$start.'–'.$end;
    }

    public static function instruction(ListeningQuestionType $type, int $sectionNumber): string
    {
        return match ($type) {
            ListeningQuestionType::MCQ => 'Choose the correct letter, A, B or C.',
            ListeningQuestionType::MultipleAnswer => 'Choose TWO letters, A–E.',
            ListeningQuestionType::Matching => 'Match each item with the correct option.',
            ListeningQuestionType::MapLabelling => 'Label the map below. Write NO MORE THAN TWO WORDS AND/OR A NUMBER for each answer.',
            ListeningQuestionType::PlanLabelling => 'Label the plan below. Write NO MORE THAN TWO WORDS AND/OR A NUMBER for each answer.',
            ListeningQuestionType::DiagramLabelling => 'Label the diagram below. Write NO MORE THAN TWO WORDS AND/OR A NUMBER for each answer.',
            ListeningQuestionType::FormCompletion => 'Complete the form below. Write ONE WORD AND/OR A NUMBER for each answer.',
            ListeningQuestionType::NoteCompletion => 'Complete the notes below. Write ONE WORD AND/OR A NUMBER for each answer.',
            ListeningQuestionType::TableCompletion => 'Complete the table below. Write ONE WORD AND/OR A NUMBER for each answer.',
            ListeningQuestionType::FlowchartCompletion => 'Complete the flow-chart below. Write ONE WORD AND/OR A NUMBER for each answer.',
            ListeningQuestionType::SentenceCompletion => 'Complete the sentences below. Write ONE WORD AND/OR A NUMBER for each answer.',
            ListeningQuestionType::SummaryCompletion => 'Complete the summary below. Write ONE WORD AND/OR A NUMBER for each answer.',
            ListeningQuestionType::ShortAnswer => 'Answer the questions below. Write NO MORE THAN THREE WORDS AND/OR A NUMBER for each answer.',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function instructionMap(): array
    {
        $map = [];

        foreach (ListeningQuestionType::cases() as $type) {
            $map[$type->value] = self::instruction($type, 1);
        }

        return $map;
    }
}
