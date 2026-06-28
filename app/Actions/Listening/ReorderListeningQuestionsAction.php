<?php

declare(strict_types=1);

namespace App\Actions\Listening;

use App\Models\Listening\ListeningQuestionGroup;
use App\Repositories\Listening\ListeningQuestionRepository;
use Illuminate\Validation\ValidationException;

class ReorderListeningQuestionsAction
{
    public function __construct(
        private readonly ListeningQuestionRepository $questions,
    ) {}

    /**
     * @param  list<int>  $orderedQuestionIds
     */
    public function execute(ListeningQuestionGroup $group, array $orderedQuestionIds): void
    {
        $groupQuestions = $this->questions->forGroup($group);
        $groupIds = $groupQuestions->pluck('id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $requested = collect($orderedQuestionIds)->map(fn ($id) => (int) $id)->sort()->values()->all();

        if ($groupIds !== $requested) {
            throw ValidationException::withMessages([
                'questions' => 'All questions for this group must be included in the reorder request.',
            ]);
        }

        foreach ($orderedQuestionIds as $index => $questionId) {
            $question = $groupQuestions->firstWhere('id', (int) $questionId);

            if ($question === null) {
                continue;
            }

            $question->update(['display_order' => $index + 1]);
        }
    }
}
