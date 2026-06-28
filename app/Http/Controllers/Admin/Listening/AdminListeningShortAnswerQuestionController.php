<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Listening;

use App\Enums\Exam\ReadingCompletionAnswerRule;
use App\Http\Controllers\Admin\Listening\Concerns\InteractsWithListeningQuestionBuilder;
use App\Http\Controllers\Controller;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Services\Listening\Builders\ListeningShortAnswerQuestionBuilderService;
use App\Support\Listening\ListeningQuestionBuilderRoutes;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminListeningShortAnswerQuestionController extends Controller
{
    use InteractsWithListeningQuestionBuilder;

    public function __construct(private readonly ListeningShortAnswerQuestionBuilderService $shortAnswer) {}

    public function edit(ListeningQuestionGroup $group): View
    {
        return $this->index($group);
    }

    public function index(ListeningQuestionGroup $group): View
    {
        $group = $this->shortAnswer->loadGroupForBuilder($group);
        abort_unless($group->question_type?->isShortAnswerBuilderType(), 404);
        $this->authorize('update', $this->shortAnswer->listeningTestForGroup($group));

        return view('admin.listening.question-builders.short-answer.index', [
            'listeningTest' => $group->section->test,
            'section' => $group->section,
            'group' => $group,
            'questions' => $this->shortAnswer->presentQuestions($group),
            'type' => $group->question_type,
            'settings' => $this->shortAnswer->groupBuilderSettings($group),
            'answerRules' => ReadingCompletionAnswerRule::cases(),
            'showPreview' => request()->boolean('preview'),
            'backToGroupUrl' => ListeningQuestionBuilderRoutes::backToGroupUrl($group),
        ]);
    }

    public function preview(ListeningQuestionGroup $group): View
    {
        request()->merge(['preview' => 1]);

        return $this->index($group);
    }

    public function store(Request $request, ListeningQuestionGroup $group): RedirectResponse
    {
        $this->authorize('update', $this->shortAnswer->listeningTestForGroup($group));
        $this->logListeningQuestionPayload($request, 'short_answer.store');
        $data = $request->validate(array_merge([
            'answer_rule' => ['required', 'string'],
            'custom_answer_rule' => ['nullable', 'string'],
            'question_number' => ['required', 'integer', 'min:1', 'max:40'],
            'prompt' => ['required', 'string', 'max:10000'],
            'correct_answer' => $this->textAnswerRules(),
            'case_sensitive' => ['nullable', 'boolean'],
            'explanation' => ['nullable', 'string', 'max:10000'],
            'difficulty' => ['nullable', 'string', 'max:20'],
        ], $this->alternativeAnswerRules()));

        try {
            $this->shortAnswer->storeQuestion($group, $data);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with('status', 'Short answer question created successfully.');
    }

    public function update(Request $request, ListeningQuestion $question): RedirectResponse
    {
        $group = $question->group ?? abort(404);
        $this->authorize('update', $this->shortAnswer->listeningTestForGroup($group));
        $this->logListeningQuestionPayload($request, 'short_answer.update');
        $data = $request->validate(array_merge([
            'question_number' => ['required', 'integer', 'min:1', 'max:40'],
            'prompt' => ['required', 'string', 'max:10000'],
            'correct_answer' => $this->textAnswerRules(),
            'case_sensitive' => ['nullable', 'boolean'],
            'explanation' => ['nullable', 'string', 'max:10000'],
            'difficulty' => ['nullable', 'string', 'max:20'],
        ], $this->alternativeAnswerRules()));

        try {
            $this->shortAnswer->updateQuestion($question, $data);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with('status', 'Short answer question updated successfully.');
    }

    public function destroy(ListeningQuestion $question): RedirectResponse
    {
        $group = $question->group;

        if (! $group) {
            abort(404);
        }

        $this->authorize('update', $this->shortAnswer->listeningTestForGroup($group));
        $this->shortAnswer->deleteQuestion($question);

        return back()->with('status', 'Short answer question deleted successfully.');
    }

    public function reorder(Request $request, ListeningQuestionGroup $group): RedirectResponse
    {
        $this->authorize('update', $this->shortAnswer->listeningTestForGroup($group));
        $data = $request->validate(['question_ids' => ['required', 'array'], 'question_ids.*' => ['integer']]);
        $this->shortAnswer->reorderQuestions($group, array_map('intval', $data['question_ids']));

        return back()->with('status', 'Question order updated successfully.');
    }
}
