<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ShortAnswer\ReorderShortAnswerQuestionsRequest;
use App\Http\Requests\Admin\ShortAnswer\StoreShortAnswerQuestionRequest;
use App\Http\Requests\Admin\ShortAnswer\UpdateShortAnswerQuestionRequest;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Services\Admin\Exam\ReadingShortAnswerQuestionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AdminReadingShortAnswerQuestionController extends Controller
{
    public function __construct(private readonly ReadingShortAnswerQuestionService $shortAnswer)
    {
    }

    public function edit(ReadingQuestionGroup $group): View
    {
        return $this->index($group);
    }

    public function index(ReadingQuestionGroup $group): View
    {
        $group = $this->shortAnswer->loadGroupForBuilder($group);
        abort_unless($group->question_type?->isShortAnswerBuilderType(), 404);
        $this->authorize('update', $this->shortAnswer->readingTestForGroup($group));

        $settings = $this->shortAnswer->groupBuilderSettings($group);

        return view('pages.admin.reading-tests.short-answer.index', [
            'test' => $group->passage->test,
            'passage' => $group->passage,
            'group' => $group,
            'questions' => $group->questions,
            'type' => $group->question_type,
            'settings' => $settings,
            'answerRules' => \App\Enums\Exam\ReadingCompletionAnswerRule::cases(),
            'showPreview' => request()->boolean('preview'),
        ]);
    }

    public function preview(ReadingQuestionGroup $group): View
    {
        request()->merge(['preview' => 1]);

        return $this->index($group);
    }

    public function store(StoreShortAnswerQuestionRequest $request, ReadingQuestionGroup $group): RedirectResponse
    {
        $this->shortAnswer->storeQuestion($group, $request->questionAttributes());

        return back()->with('status', 'Short answer question created successfully.');
    }

    public function update(UpdateShortAnswerQuestionRequest $request, ReadingQuestion $question): RedirectResponse
    {
        $this->shortAnswer->updateQuestion($question, $request->questionAttributes());

        return back()->with('status', 'Short answer question updated successfully.');
    }

    public function destroy(ReadingQuestion $question): RedirectResponse
    {
        $group = $question->group;

        if (! $group) {
            abort(404);
        }

        $this->authorize('update', $this->shortAnswer->readingTestForGroup($group));
        $this->shortAnswer->deleteQuestion($question);

        return back()->with('status', 'Short answer question deleted successfully.');
    }

    public function reorder(ReorderShortAnswerQuestionsRequest $request, ReadingQuestionGroup $group): RedirectResponse
    {
        $this->shortAnswer->reorderQuestions($group, $request->questionIds());

        return back()->with('status', 'Question order updated successfully.');
    }
}
