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
    case Dropdown = 'dropdown';
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
            self::Dropdown => 'Dropdown',
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

    /**
     * @return list<self>
     */
    public static function matchingBuilderTypes(): array
    {
        return [
            self::MatchingInformation,
            self::MatchingHeadings,
            self::MatchingFeatures,
            self::MatchingPeople,
            self::MatchingSentenceEndings,
            self::Dropdown,
        ];
    }

    public function isMatchingBuilderType(): bool
    {
        return in_array($this, self::matchingBuilderTypes(), true);
    }

    public function usesRomanOptionKeys(): bool
    {
        return $this === self::MatchingHeadings;
    }

    public function requiresParagraphReference(): bool
    {
        return $this === self::MatchingHeadings;
    }

    /**
     * @return list<self>
     */
    public static function objectiveBuilderTypes(): array
    {
        return [
            self::TrueFalseNotGiven,
            self::YesNoNotGiven,
            self::MultipleChoiceSingle,
            self::MultipleChoiceMultiple,
        ];
    }

    public function isObjectiveBuilderType(): bool
    {
        return in_array($this, self::objectiveBuilderTypes(), true);
    }

    public function usesPerQuestionOptions(): bool
    {
        return in_array($this, [self::MultipleChoiceSingle, self::MultipleChoiceMultiple], true);
    }

    public function allowsMultipleCorrectAnswers(): bool
    {
        return $this === self::MultipleChoiceMultiple;
    }

    /**
     * @return list<string>|null
     */
    public function objectiveAnswerChoices(): ?array
    {
        return match ($this) {
            self::TrueFalseNotGiven => ['TRUE', 'FALSE', 'NOT_GIVEN'],
            self::YesNoNotGiven => ['YES', 'NO', 'NOT_GIVEN'],
            default => null,
        };
    }

    public function objectiveBuilderViewKey(): string
    {
        return match ($this) {
            self::TrueFalseNotGiven => 'true-false',
            self::YesNoNotGiven => 'yes-no',
            self::MultipleChoiceSingle => 'mcq-single',
            self::MultipleChoiceMultiple => 'mcq-multiple',
            default => 'unsupported',
        };
    }

    /**
     * @return list<self>
     */
    public static function completionBuilderTypes(): array
    {
        return [
            self::SummaryCompletion,
            self::SentenceCompletion,
            self::NoteCompletion,
            self::TableCompletion,
            self::FlowChartCompletion,
        ];
    }

    public function isCompletionBuilderType(): bool
    {
        return in_array($this, self::completionBuilderTypes(), true);
    }

    public function usesCompletionTemplate(): bool
    {
        return in_array($this, [
            self::SummaryCompletion,
            self::NoteCompletion,
            self::TableCompletion,
            self::FlowChartCompletion,
        ], true);
    }

    public function completionBuilderViewKey(): string
    {
        return match ($this) {
            self::SummaryCompletion => 'summary',
            self::SentenceCompletion => 'sentence',
            self::NoteCompletion => 'note',
            self::TableCompletion => 'table',
            self::FlowChartCompletion => 'flowchart',
            default => 'unsupported',
        };
    }
}
