<?php

declare(strict_types=1);

namespace App\Support\Reading;

use App\Enums\Exam\OfficialReadingQuestionType;

final class ReadingQuestionGroupDefaults
{
    public static function title(int $start, int $end): string
    {
        if ($start === $end) {
            return 'Question '.$start;
        }

        return 'Questions '.$start.'–'.$end;
    }

    public static function instruction(OfficialReadingQuestionType $type, int $passageNumber): string
    {
        return match ($type) {
            OfficialReadingQuestionType::MatchingHeadings => "Reading Passage {$passageNumber} has seven paragraphs, A–G. Choose the correct heading for each paragraph from the list of headings below.",
            OfficialReadingQuestionType::MatchingInformation => "Reading Passage {$passageNumber} has eight sections, A–H. Which section contains the following information?",
            OfficialReadingQuestionType::MatchingFeatures => "Reading Passage {$passageNumber} has a list of features. Which feature matches each statement?",
            OfficialReadingQuestionType::MatchingSentenceEndings => 'Complete each sentence with the correct ending, A–H, below.',
            OfficialReadingQuestionType::MatchingPeople => "Reading Passage {$passageNumber} has a number of statements. Which person makes each statement?",
            OfficialReadingQuestionType::Dropdown => 'Choose the correct answer from the dropdown list.',
            OfficialReadingQuestionType::TrueFalseNotGiven => 'Do the following statements agree with the information in the passage?',
            OfficialReadingQuestionType::YesNoNotGiven => "Do the following statements agree with the views of the writer in Reading Passage {$passageNumber}?",
            OfficialReadingQuestionType::SummaryCompletion => 'Complete the summary below. Choose ONE WORD ONLY from the passage for each answer.',
            OfficialReadingQuestionType::SentenceCompletion => 'Complete the sentences below. Choose ONE WORD ONLY from the passage for each answer.',
            OfficialReadingQuestionType::NoteCompletion => 'Complete the notes below. Choose ONE WORD ONLY from the passage for each answer.',
            OfficialReadingQuestionType::TableCompletion => 'Complete the table below. Choose ONE WORD ONLY from the passage for each answer.',
            OfficialReadingQuestionType::FlowChartCompletion => 'Complete the flow-chart below. Choose ONE WORD ONLY from the passage for each answer.',
            OfficialReadingQuestionType::DiagramLabelCompletion => 'Label the diagram below. Choose ONE WORD ONLY from the passage for each answer.',
            OfficialReadingQuestionType::ShortAnswer => 'Answer the questions below. Choose NO MORE THAN THREE WORDS from the passage for each answer.',
            OfficialReadingQuestionType::MultipleChoiceSingle => 'Choose the correct letter, A, B, C or D.',
            OfficialReadingQuestionType::MultipleChoiceMultiple => 'Choose TWO letters, A–E.',
        };
    }
}
