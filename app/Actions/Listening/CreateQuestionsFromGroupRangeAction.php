<?php

declare(strict_types=1);

namespace App\Actions\Listening;

use App\Enums\Listening\ListeningAnswerFormat;
use App\Models\Listening\ListeningQuestionGroup;
use App\Repositories\Listening\ListeningQuestionRepository;

class CreateQuestionsFromGroupRangeAction
{
    public function __construct(
        private readonly ListeningQuestionRepository $questions,
    ) {}

    /**
     * @return array{created: int, skipped: int}
     */
    public function execute(ListeningQuestionGroup $group): array
    {
        $group->loadMissing(['test', 'section']);
        $created = 0;
        $skipped = 0;
        $allowDraft = (bool) config('listening.questions.allow_draft_without_answer', true);
        $defaultMarks = (float) config('listening.questions.default_marks', 1);

        for ($number = (int) $group->start_question_number; $number <= (int) $group->end_question_number; $number++) {
            $existing = $this->questions->findByNumberForTest($group->test, $number, withTrashed: true);

            if ($existing !== null) {
                if ((int) $existing->listening_question_group_id === (int) $group->id) {
                    if ($existing->trashed()) {
                        $existing->restore();
                        $skipped++;
                    } else {
                        $skipped++;
                    }

                    continue;
                }

                if (! $existing->trashed()) {
                    $skipped++;

                    continue;
                }

                $existing->restore();
                $this->questions->update($existing, [
                    'listening_section_id' => $group->listening_section_id,
                    'listening_question_group_id' => $group->id,
                    'question_type' => $group->question_type,
                    'answer_format' => $this->defaultAnswerFormat($group->question_type->value),
                    'correct_answer' => $allowDraft ? [] : [['value' => '', 'type' => 'text']],
                    'accepted_answers' => [],
                    'marks' => $defaultMarks,
                    'display_order' => $number,
                    'is_required' => true,
                    'is_active' => true,
                ]);
                $created++;

                continue;
            }

            $this->questions->create([
                'listening_test_id' => $group->listening_test_id,
                'listening_section_id' => $group->listening_section_id,
                'listening_question_group_id' => $group->id,
                'question_number' => $number,
                'question_type' => $group->question_type,
                'answer_format' => $this->defaultAnswerFormat($group->question_type->value),
                'correct_answer' => $allowDraft ? [] : [['value' => '', 'type' => 'text']],
                'accepted_answers' => [],
                'marks' => $defaultMarks,
                'display_order' => $number,
                'is_required' => true,
                'is_active' => true,
            ]);

            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    private function defaultAnswerFormat(string $questionType): string
    {
        return match ($questionType) {
            'mcq', 'multiple_answer', 'matching' => ListeningAnswerFormat::Letter->value,
            'map_labelling', 'plan_labelling', 'diagram_labelling' => ListeningAnswerFormat::MapLabel->value,
            default => ListeningAnswerFormat::Text->value,
        };
    }
}
