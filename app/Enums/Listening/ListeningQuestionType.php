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
