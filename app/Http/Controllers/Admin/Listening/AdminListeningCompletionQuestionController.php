<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Listening;

use App\Enums\Exam\ReadingCompletionAnswerRule;
use App\Http\Controllers\Admin\Listening\Concerns\InteractsWithListeningQuestionBuilder;
use App\Http\Controllers\Controller;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Services\Listening\Builders\ListeningCompletionQuestionBuilderService;
use App\Support\Listening\ListeningQuestionBuilderRoutes;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminListeningCompletionQuestionController extends Controller
{
    use InteractsWithListeningQuestionBuilder;

    public function __construct(private readonly ListeningCompletionQuestionBuilderService $completion) {}

    public function edit(ListeningQuestionGroup $group): View
    {
        return $this->index($group);
    }

    public function index(ListeningQuestionGroup $group): View
    {
        $group = $this->completion->loadGroupForBuilder($group);
        abort_unless($group->question_type?->isCompletionBuilderType(), 404);
        $this->authorize('update', $this->completion->listeningTestForGroup($group));

        $settings = $this->completion->groupBuilderSettings($group);
        $questions = $this->completion->presentQuestions($group);
        $detected = $this->completion->detectBlanks($group, $settings['template_html']);

        return view('admin.listening.question-builders.completion.index', [
            'listeningTest' => $group->section->test,
            'section' => $group->section,
            'group' => $group,
            'questions' => $questions,
            'type' => $group->question_type,
            'settings' => $settings,
            'answerRules' => ReadingCompletionAnswerRule::cases(),
            'showPreview' => request()->boolean('preview'),
            'backToGroupUrl' => ListeningQuestionBuilderRoutes::backToGroupUrl($group),
            'detectedCount' => $detected['count'],
            'expectedCount' => (int) $group->total_questions,
            'existingQuestionNumbers' => $questions->pluck('question_number')->all(),
            'previewHtml' => $this->completion->previewHtml($group),
        ]);
    }

    public function preview(ListeningQuestionGroup $group): View
    {
        request()->merge(['preview' => 1]);

        return $this->index($group);
    }

    public function saveTemplate(Request $request, ListeningQuestionGroup $group): RedirectResponse
    {
        $this->authorize('update', $this->completion->listeningTestForGroup($group));
        $data = $request->validate([
            'template_html' => ['required', 'string'],
            'answer_rule' => ['required', 'string'],
            'custom_answer_rule' => ['nullable', 'string'],
            'confirm_remove' => ['nullable', 'boolean'],
        ]);

        return $this->handleTemplateSave(fn () => $this->completion->saveTemplate($group, $data));
    }

    public function saveTable(Request $request, ListeningQuestionGroup $group): RedirectResponse
    {
        $this->authorize('update', $this->completion->listeningTestForGroup($group));
        $data = $this->validateStructuredBuilderInput($request, 'table_data');

        return $this->handleTemplateSave(fn () => $this->completion->saveTable($group, $data));
    }

    public function saveFlowChart(Request $request, ListeningQuestionGroup $group): RedirectResponse
    {
        $this->authorize('update', $this->completion->listeningTestForGroup($group));
        $data = $this->validateStructuredBuilderInput($request, 'flow_steps');

        return $this->handleTemplateSave(fn () => $this->completion->saveFlowChart($group, $data));
    }

    public function storeSentence(Request $request, ListeningQuestionGroup $group): RedirectResponse
    {
        $this->authorize('update', $this->completion->listeningTestForGroup($group));
        $this->logListeningQuestionPayload($request, 'completion.store_sentence');
        $data = $request->validate(array_merge([
            'question_number' => ['required', 'integer', 'min:1', 'max:40'],
            'prompt' => ['nullable', 'string', 'max:10000'],
            'sentence_before' => ['nullable', 'string', 'max:5000'],
            'sentence_after' => ['nullable', 'string', 'max:5000'],
            'correct_answer' => $this->textAnswerRules(),
            'case_sensitive' => ['nullable', 'boolean'],
            'explanation' => ['nullable', 'string', 'max:10000'],
            'difficulty' => ['nullable', 'string', 'max:20'],
        ], $this->alternativeAnswerRules()));

        try {
            $this->completion->storeSentenceQuestion($group, $data);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with('status', 'Question created successfully.');
    }

    public function update(Request $request, ListeningQuestion $question): RedirectResponse
    {
        $group = $question->group ?? abort(404);
        $this->authorize('update', $this->completion->listeningTestForGroup($group));
        $this->logListeningQuestionPayload($request, 'completion.update');
        $data = $request->validate(array_merge([
            'question_number' => ['required', 'integer', 'min:1', 'max:40'],
            'prompt' => ['nullable', 'string', 'max:10000'],
            'sentence_before' => ['nullable', 'string', 'max:5000'],
            'sentence_after' => ['nullable', 'string', 'max:5000'],
            'correct_answer' => $this->textAnswerRules(),
            'case_sensitive' => ['nullable', 'boolean'],
            'explanation' => ['nullable', 'string', 'max:10000'],
            'difficulty' => ['nullable', 'string', 'max:20'],
        ], $this->alternativeAnswerRules()));

        try {
            $this->completion->updateQuestion($question, $data);
        } catch (ValidationException $exception) {
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

        $this->authorize('update', $this->completion->listeningTestForGroup($group));
        $this->completion->deleteQuestion($question);

        return back()->with('status', 'Question deleted successfully.');
    }

    public function bulkImport(Request $request, ListeningQuestionGroup $group): RedirectResponse
    {
        $this->authorize('update', $this->completion->listeningTestForGroup($group));
        $data = $request->validate([
            'import_text' => ['required', 'string'],
            'confirm_remove' => ['nullable', 'boolean'],
        ]);

        try {
            $count = $this->completion->bulkImport($group, $data);
        } catch (ValidationException $exception) {
            if ($exception->errors()['confirm_remove'] ?? false) {
                return back()
                    ->withInput()
                    ->withErrors($exception->errors())
                    ->with('completion_confirm_remove', true);
            }

            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with('status', "{$count} question(s) imported successfully.");
    }

    public function reorder(Request $request, ListeningQuestionGroup $group): RedirectResponse
    {
        $this->authorize('update', $this->completion->listeningTestForGroup($group));
        $data = $request->validate(['question_ids' => ['required', 'array'], 'question_ids.*' => ['integer']]);
        $this->completion->reorderQuestions($group, array_map('intval', $data['question_ids']));

        return back()->with('status', 'Question order updated successfully.');
    }

    public function detect(Request $request, ListeningQuestionGroup $group): JsonResponse
    {
        $this->authorize('update', $this->completion->listeningTestForGroup($group));
        $content = (string) ($request->input('content') ?? $request->input('template_html', ''));
        $preview = $this->completion->liveDetectPreview($group, $content);

        return response()->json($preview);
    }

    private function handleTemplateSave(callable $callback): RedirectResponse
    {
        try {
            $callback();
        } catch (ValidationException $exception) {
            if ($exception->errors()['confirm_remove'] ?? false) {
                return back()
                    ->withInput()
                    ->withErrors($exception->errors())
                    ->with('completion_confirm_remove', true);
            }

            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with('status', 'Template saved and questions synced successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateStructuredBuilderInput(Request $request, string $structuredField): array
    {
        foreach (['table_data', 'flow_steps'] as $field) {
            $value = $request->input($field);

            if (is_string($value) && $value !== '') {
                $decoded = json_decode($value, true);
                $request->merge([
                    $field => is_array($decoded) ? $decoded : null,
                ]);
            }
        }

        return $request->validate([
            $structuredField => ['required', 'array'],
            'answer_rule' => ['required', 'string'],
            'custom_answer_rule' => ['nullable', 'string'],
            'confirm_remove' => ['nullable', 'boolean'],
            'template_html' => ['nullable', 'string'],
        ]);
    }
}
