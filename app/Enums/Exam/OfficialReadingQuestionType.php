<?php

declare(strict_types=1);

namespace App\Enums\Exam;

use App\Enums\Concerns\EnumHelpers;

enum OfficialReadingQuestionType: string
{
    use EnumHelpers;

    case MatchingInformation = 'matching_information';
    case MatchingHeadings = 'matching_headings';
    case MatchingFeatures = 'matching_features';
    case MatchingSentenceEndings = 'matching_sentence_endings';
    case MatchingPeople = 'matching_people';
    case TrueFalseNotGiven = 'true_false_not_given';
    case YesNoNotGiven = 'yes_no_not_given';
    case MultipleChoiceSingle = 'multiple_choice_single';
    case MultipleChoiceMultiple = 'multiple_choice_multiple';
    case SentenceCompletion = 'sentence_completion';
    case SummaryCompletion = 'summary_completion';
    case FlowChartCompletion = 'flow_chart_completion';
    case DiagramLabelCompletion = 'diagram_label_completion';
    case ShortAnswer = 'short_answer';
    case TableCompletion = 'table_completion';
    case NoteCompletion = 'note_completion';

    public function label(): string
    {
        return match ($this) {
            self::MatchingInformation => 'Matching Information',
            self::MatchingHeadings => 'Matching Headings',
            self::MatchingFeatures => 'Matching Features',
            self::MatchingSentenceEndings => 'Matching Sentence Endings',
            self::MatchingPeople => 'Matching People',
            self::TrueFalseNotGiven => 'True / False / Not Given',
            self::YesNoNotGiven => 'Yes / No / Not Given',
            self::MultipleChoiceSingle => 'Multiple Choice Single',
            self::MultipleChoiceMultiple => 'Multiple Choice Multiple',
            self::SentenceCompletion => 'Sentence Completion',
            self::SummaryCompletion => 'Summary Completion',
            self::FlowChartCompletion => 'Flow Chart Completion',
            self::DiagramLabelCompletion => 'Diagram Label Completion',
            self::ShortAnswer => 'Short Answer',
            self::TableCompletion => 'Table Completion',
            self::NoteCompletion => 'Note Completion',
        };
    }
}
