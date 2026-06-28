<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Listening;

use App\Http\Controllers\Admin\Listening\Concerns\InteractsWithListeningQuestionBuilder;
use App\Http\Controllers\Controller;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Services\Listening\Builders\ListeningMatchingQuestionBuilderService;
use App\Support\Listening\ListeningQuestionBuilderRoutes;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminListeningMatchingQuestionController extends Controller
{
    use InteractsWithListeningQuestionBuilder;

    public function __construct(private readonly ListeningMatchingQuestionBuilderService $matching) {}

    public function index(ListeningQuestionGroup $group): View
    {
        $group = $this->matching->loadGroupForBuilder($group);
        abort_unless($group->question_type?->isMatchingBuilderType(), 404);
        $this->authorize('update', $this->matching->listeningTestForGroup($group));

        return view('admin.listening.question-builders.matching.index', [
            'listeningTest' => $group->section->test,
            'section' => $group->section,
            'group' => $group,
            'options' => $this->matching->presentOptions($group),
            'questions' => $this->matching->presentQuestions($group),
            'showPreview' => request()->boolean('preview'),
            'backToGroupUrl' => ListeningQuestionBuilderRoutes::backToGroupUrl($group),
        ]);
    }

    public function storeOption(Request $request, ListeningQuestionGroup $group): RedirectResponse
    {
        $this->authorize('update', $this->matching->listeningTestForGroup($group));
        $this->logListeningQuestionPayload($request, 'matching.store_option');
        $data = $request->validate([
            'option_key' => ['required', 'string', 'max:50'],
            'option_label' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $this->matching->storeOption($group, $data);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with('status', 'Option added successfully.');
    }

    public function storeQuestion(Request $request, ListeningQuestionGroup $group): RedirectResponse
    {
        $this->authorize('update', $this->matching->listeningTestForGroup($group));
        $this->logListeningQuestionPayload($request, 'matching.store_question');
        $choiceKeys = $this->matchingChoiceKeys($group);
        $allowDraft = (bool) config('listening.questions.allow_draft_without_answer', true);

        $data = $request->validate([
            'question_number' => ['required', 'integer', 'min:1', 'max:40'],
            'prompt' => ['required', 'string', 'max:10000'],
            'correct_answer' => array_filter([
                $allowDraft ? 'nullable' : 'required',
                'string',
                'max:50',
                $choiceKeys !== [] ? Rule::in($choiceKeys) : null,
            ]),
            'explanation' => ['nullable', 'string', 'max:10000'],
        ]);

        if ($choiceKeys === []) {
            return back()->withInput()->withErrors([
                'options' => 'Add matching options before saving questions.',
            ]);
        }

        try {
            $this->matching->storeQuestion($group, $data);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with('status', 'Question added successfully.');
    }

    public function updateOption(Request $request, ListeningQuestionGroup $group, int $option): RedirectResponse
    {
        $this->authorize('update', $this->matching->listeningTestForGroup($group));
        $data = $request->validate([
            'option_key' => ['nullable', 'string', 'max:50'],
            'option_label' => ['nullable', 'string', 'max:5000'],
        ]);

        try {
            $this->matching->updateOption($group, $option, $data);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with('status', 'Option updated successfully.');
    }

    public function deleteOption(Request $request, ListeningQuestionGroup $group, int $option): RedirectResponse
    {
        $this->authorize('update', $this->matching->listeningTestForGroup($group));

        try {
            $this->matching->deleteOption($group, $option, $request->boolean('confirm_delete'));
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with('status', 'Option deleted successfully.');
    }

    public function updateQuestion(Request $request, ListeningQuestion $question): RedirectResponse
    {
        $group = $question->group ?? abort(404);
        $this->authorize('update', $this->matching->listeningTestForGroup($group));
        $this->logListeningQuestionPayload($request, 'matching.update_question');
        $choiceKeys = $this->matchingChoiceKeys($group);
        $allowDraft = (bool) config('listening.questions.allow_draft_without_answer', true);

        $data = $request->validate([
            'question_number' => ['required', 'integer', 'min:1', 'max:40'],
            'prompt' => ['required', 'string', 'max:10000'],
            'correct_answer' => array_filter([
                $allowDraft ? 'nullable' : 'required',
                'string',
                'max:50',
                $choiceKeys !== [] ? Rule::in($choiceKeys) : null,
            ]),
            'explanation' => ['nullable', 'string', 'max:10000'],
        ]);

        if ($choiceKeys === []) {
            return back()->withInput()->withErrors([
                'options' => 'Add matching options before saving questions.',
            ]);
        }

        try {
            $this->matching->updateQuestion($question, $data);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with('status', 'Question updated successfully.');
    }

    public function deleteQuestion(ListeningQuestion $question): RedirectResponse
    {
        $group = $question->group;

        if (! $group) {
            abort(404);
        }

        $this->authorize('update', $this->matching->listeningTestForGroup($group));
        $this->matching->deleteQuestion($question);

        return back()->with('status', 'Question deleted successfully.');
    }

    public function bulkImport(Request $request, ListeningQuestionGroup $group): RedirectResponse
    {
        $this->authorize('update', $this->matching->listeningTestForGroup($group));
        $data = $request->validate([
            'options_text' => ['nullable', 'string'],
            'questions_text' => ['nullable', 'string'],
        ]);

        try {
            $result = $this->matching->bulkImport($group, $data);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with(
            'status',
            "Bulk import complete: {$result['options']} option(s) and {$result['questions']} question(s) added.",
        );
    }

    public function reorder(Request $request, ListeningQuestionGroup $group): RedirectResponse
    {
        $this->authorize('update', $this->matching->listeningTestForGroup($group));
        $this->matching->reorder($group, $request->only(['option_ids', 'question_ids']));

        return back()->with('status', 'Question order updated successfully.');
    }

    /**
     * @return list<string>
     */
    private function matchingChoiceKeys(ListeningQuestionGroup $group): array
    {
        $group->loadMissing('questions');
        $choices = is_array($group->options['choices'] ?? null) ? $group->options['choices'] : [];

        return array_values(array_filter(array_map(
            fn (array $choice): string => strtoupper(trim((string) ($choice['key'] ?? ''))),
            $choices,
        )));
    }
}
