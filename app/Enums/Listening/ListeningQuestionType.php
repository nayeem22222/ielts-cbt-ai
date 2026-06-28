<?php

declare(strict_types=1);

namespace App\Enums\Listening;

use App\Enums\Listening\Concerns\ListeningEnum;

enum ListeningQuestionType: string
{
    use ListeningEnum;

    case MCQ = 'mcq';
    case MultipleAnswer = 'multiple_answer';
    case Matching = 'matching';
    case MapLabelling = 'map_labelling';
    case PlanLabelling = 'plan_labelling';
    case DiagramLabelling = 'diagram_labelling';
    case FormCompletion = 'form_completion';
    case NoteCompletion = 'note_completion';
    case TableCompletion = 'table_completion';
    case FlowchartCompletion = 'flowchart_completion';
    case SentenceCompletion = 'sentence_completion';
    case SummaryCompletion = 'summary_completion';
    case ShortAnswer = 'short_answer';

    public function isMatchingBuilderType(): bool
    {
        return $this === self::Matching;
    }

    public function isObjectiveBuilderType(): bool
    {
        return in_array($this, [self::MCQ, self::MultipleAnswer], true);
    }

    public function isLabellingBuilderType(): bool
    {
        return in_array($this, [self::MapLabelling, self::PlanLabelling, self::DiagramLabelling], true);
    }

    public function isCompletionBuilderType(): bool
    {
        return in_array($this, [
            self::FormCompletion,
            self::NoteCompletion,
            self::TableCompletion,
            self::FlowchartCompletion,
            self::SentenceCompletion,
            self::SummaryCompletion,
        ], true);
    }

    public function isShortAnswerBuilderType(): bool
    {
        return $this === self::ShortAnswer;
    }

    public function questionBuilderRouteName(): string
    {
        return match (true) {
            $this->isMatchingBuilderType() => 'admin.listening-question-groups.matching-questions.index',
            $this->isObjectiveBuilderType() => 'admin.listening-question-groups.objective-questions.index',
            $this->isLabellingBuilderType() => 'admin.listening-question-groups.labelling-questions.index',
            $this->isShortAnswerBuilderType() => 'admin.listening-question-groups.short-answer-questions.index',
            $this->isCompletionBuilderType() => 'admin.listening-question-groups.completion-questions.index',
            default => 'admin.listening-question-groups.completion-questions.index',
        };
    }

    public function questionBuilderFamilyLabel(): string
    {
        return match (true) {
            $this->isMatchingBuilderType() => 'Matching',
            $this->isObjectiveBuilderType() => 'Objective',
            $this->isLabellingBuilderType() => 'Labelling',
            $this->isShortAnswerBuilderType() => 'Short Answer',
            $this->isCompletionBuilderType() => 'Completion',
            default => 'Question',
        };
    }

    public function usesPerQuestionOptions(): bool
    {
        return false;
    }

    public function allowsMultipleCorrectAnswers(): bool
    {
        return $this === self::MultipleAnswer;
    }

    public function usesRomanOptionKeys(): bool
    {
        return false;
    }

    public function usesCompletionTemplate(): bool
    {
        return in_array($this, [
            self::FormCompletion,
            self::SummaryCompletion,
            self::SentenceCompletion,
            self::NoteCompletion,
            self::TableCompletion,
            self::FlowchartCompletion,
        ], true);
    }

    public function requiresParagraphReference(): bool
    {
        return false;
    }

    public function label(): string
    {
        return match ($this) {
            self::MCQ => 'Multiple Choice',
            self::MultipleAnswer => 'Multiple Answer',
            self::Matching => 'Matching',
            self::MapLabelling => 'Map Labelling',
            self::PlanLabelling => 'Plan Labelling',
            self::DiagramLabelling => 'Diagram Labelling',
            self::FormCompletion => 'Form Completion',
            self::NoteCompletion => 'Note Completion',
            self::TableCompletion => 'Table Completion',
            self::FlowchartCompletion => 'Flowchart Completion',
            self::SentenceCompletion => 'Sentence Completion',
            self::SummaryCompletion => 'Summary Completion',
            self::ShortAnswer => 'Short Answer',
        };
    }
}
