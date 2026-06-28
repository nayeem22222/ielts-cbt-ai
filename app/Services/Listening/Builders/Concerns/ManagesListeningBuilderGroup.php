<?php

declare(strict_types=1);

namespace App\Services\Listening\Builders\Concerns;

use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use Illuminate\Validation\ValidationException;

trait ManagesListeningBuilderGroup
{
    public function loadGroupForBuilder(ListeningQuestionGroup $group): ListeningQuestionGroup
    {
        return $group->load([
            'section.test',
            'questions' => fn ($query) => $query->orderBy('question_number'),
        ])->loadCount('questions');
    }

    public function listeningTestForGroup(ListeningQuestionGroup $group): ListeningTest
    {
        /** @var ListeningSection $section */
        $section = $group->section()->firstOrFail();

        return $section->test()->firstOrFail();
    }

    protected function assertQuestionNumberIsValid(
        ListeningQuestionGroup $group,
        int $questionNumber,
        ?ListeningQuestion $ignore = null,
    ): void {
        if ($questionNumber < (int) $group->start_question_number || $questionNumber > (int) $group->end_question_number) {
            throw ValidationException::withMessages([
                'question_number' => "Question number must be between {$group->start_question_number} and {$group->end_question_number}.",
            ]);
        }

        $exists = $group->questions()
            ->withTrashed()
            ->when($ignore !== null, fn ($query) => $query->where('id', '!=', $ignore->id))
            ->where('question_number', $questionNumber)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'question_number' => "Question number {$questionNumber} is already used in this group.",
            ]);
        }
    }
}
