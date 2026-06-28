<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Listening;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Listening\Builders\Objective\StoreListeningObjectiveQuestionRequest;
use App\Http\Requests\Admin\Listening\Builders\Objective\UpdateListeningObjectiveQuestionRequest;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Services\Listening\Builders\ListeningObjectiveQuestionBuilderService;
use App\Support\Listening\ListeningQuestionBuilderRoutes;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminListeningObjectiveQuestionController extends Controller
{
    public function __construct(private readonly ListeningObjectiveQuestionBuilderService $objective) {}

    public function index(ListeningQuestionGroup $group): View
    {
        $group = $this->objective->loadGroupForBuilder($group);
        abort_unless($group->question_type?->isObjectiveBuilderType(), 404);
        $this->authorize('update', $this->objective->listeningTestForGroup($group));

        return view('admin.listening.question-builders.objective.index', [
            'listeningTest' => $group->section->test,
            'section' => $group->section,
            'group' => $group,
            'questions' => $this->objective->presentQuestions($group),
            'groupOptions' => $this->objective->groupOptions($group),
            'type' => $group->question_type,
            'answerChoices' => [],
            'showPreview' => request()->boolean('preview'),
            'backToGroupUrl' => ListeningQuestionBuilderRoutes::backToGroupUrl($group),
        ]);
    }

    public function store(StoreListeningObjectiveQuestionRequest $request, ListeningQuestionGroup $group): RedirectResponse
    {
        try {
            $this->objective->storeQuestion($group, $request->questionAttributes());
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with('status', 'Question created successfully.');
    }

    public function update(UpdateListeningObjectiveQuestionRequest $request, ListeningQuestion $question): RedirectResponse
    {
        try {
            $this->objective->updateQuestion($question, $request->questionAttributes());
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with('status', 'Question updated successfully.');
    }

    public function destroy(ListeningQuestion $question): RedirectResponse
    {
        $group = $question->group;

        if (! $group) {
            abort(404);
        }

        $this->authorize('update', $this->objective->listeningTestForGroup($group));
        $this->objective->deleteQuestion($question);

        return back()->with('status', 'Question deleted successfully.');
    }

    public function storeOption(Request $request, ListeningQuestionGroup $group): RedirectResponse
    {
        $this->authorize('update', $this->objective->listeningTestForGroup($group));
        $data = $request->validate([
            'option_key' => ['required', 'string', 'max:50'],
            'option_label' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $this->objective->storeGroupOption($group, $data);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with('status', 'Option added successfully.');
    }

    public function updateOption(Request $request, ListeningQuestionGroup $group, int $option): RedirectResponse
    {
        $this->authorize('update', $this->objective->listeningTestForGroup($group));
        $data = $request->validate([
            'option_key' => ['nullable', 'string', 'max:50'],
            'option_label' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $this->objective->updateGroupOption($group, $option, $data);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with('status', 'Option updated successfully.');
    }

    public function deleteOption(Request $request, ListeningQuestionGroup $group, int $option): RedirectResponse
    {
        $this->authorize('update', $this->objective->listeningTestForGroup($group));
        $this->objective->deleteGroupOption($group, $option);

        return back()->with('status', 'Option deleted successfully.');
    }

    public function bulkImport(Request $request, ListeningQuestionGroup $group): RedirectResponse
    {
        $this->authorize('update', $this->objective->listeningTestForGroup($group));
        $data = $request->validate(['import_text' => ['required', 'string']]);

        try {
            $count = $this->objective->bulkImport($group, ['import_text' => $data['import_text']]);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with('status', "{$count} question(s) imported successfully.");
    }

    public function reorder(Request $request, ListeningQuestionGroup $group): RedirectResponse
    {
        $this->authorize('update', $this->objective->listeningTestForGroup($group));
        $data = $request->validate(['question_ids' => ['required', 'array'], 'question_ids.*' => ['integer']]);
        $this->objective->reorderQuestions($group, array_map('intval', $data['question_ids']));

        return back()->with('status', 'Question order updated successfully.');
    }
}
