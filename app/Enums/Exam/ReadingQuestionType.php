<?php

declare(strict_types=1);

namespace App\Enums\Exam;

use App\Enums\Concerns\EnumHelpers;

enum ReadingQuestionType: string
{
    use EnumHelpers;

    case MultipleChoiceSingle = 'multiple_choice_single';
    case MultipleChoiceMultiple = 'multiple_choice_multiple';
    case TrueFalseNg = 'true_false_ng';
    case YesNoNg = 'yes_no_ng';
    case MatchingHeadings = 'matching_headings';
    case MatchingInformation = 'matching_information';
    case MatchingFeatures = 'matching_features';
    case MatchingSentenceEndings = 'matching_sentence_endings';
    case SentenceCompletion = 'sentence_completion';
    case SummaryCompletion = 'summary_completion';
    case NoteCompletion = 'note_completion';
    case TableCompletion = 'table_completion';
    case FlowChartCompletion = 'flow_chart_completion';
    case DiagramLabelCompletion = 'diagram_label_completion';
    case ShortAnswer = 'short_answer';

    public function label(): string
    {
        return match ($this) {
            self::MultipleChoiceSingle => 'Multiple Choice (Single Answer)',
            self::MultipleChoiceMultiple => 'Multiple Choice (Multiple Answers)',
            self::TrueFalseNg => 'True / False / Not Given',
            self::YesNoNg => 'Yes / No / Not Given',
            self::MatchingHeadings => 'Matching Headings',
            self::MatchingInformation => 'Matching Information',
            self::MatchingFeatures => 'Matching Features',
            self::MatchingSentenceEndings => 'Matching Sentence Endings',
            self::SentenceCompletion => 'Sentence Completion',
            self::SummaryCompletion => 'Summary Completion',
            self::NoteCompletion => 'Note Completion',
            self::TableCompletion => 'Table Completion',
            self::FlowChartCompletion => 'Flow Chart Completion',
            self::DiagramLabelCompletion => 'Diagram Label Completion',
            self::ShortAnswer => 'Short Answer Questions',
        };
    }

    public function usesOptions(): bool
    {
        return in_array($this, [
            self::MultipleChoiceSingle,
            self::MultipleChoiceMultiple,
            self::TrueFalseNg,
            self::YesNoNg,
            self::MatchingHeadings,
            self::MatchingInformation,
            self::MatchingFeatures,
            self::MatchingSentenceEndings,
        ], true);
    }

    public function defaultOptions(): array
    {
        return match ($this) {
            self::TrueFalseNg => ['True', 'False', 'Not Given'],
            self::YesNoNg => ['Yes', 'No', 'Not Given'],
            default => [],
        };
    }

    public function allowsPartialMarks(): bool
    {
        return $this === self::MultipleChoiceMultiple;
    }

    public function isTextCompletion(): bool
    {
        return in_array($this, [
            self::SentenceCompletion,
            self::SummaryCompletion,
            self::NoteCompletion,
            self::TableCompletion,
            self::FlowChartCompletion,
            self::DiagramLabelCompletion,
            self::ShortAnswer,
        ], true);
    }
}
