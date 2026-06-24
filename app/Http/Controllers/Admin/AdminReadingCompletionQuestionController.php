<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Completion\BulkImportCompletionRequest;
use App\Http\Requests\Admin\Completion\ReorderCompletionQuestionsRequest;
use App\Http\Requests\Admin\Completion\SaveCompletionFlowChartRequest;
use App\Http\Requests\Admin\Completion\SaveCompletionTableRequest;
use App\Http\Requests\Admin\Completion\SaveCompletionTemplateRequest;
use App\Http\Requests\Admin\Completion\StoreCompletionSentenceRequest;
use App\Http\Requests\Admin\Completion\UpdateCompletionQuestionRequest;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Services\Admin\Exam\ReadingCompletionQuestionService;
use App\Support\Reading\CompletionPlaceholderParser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AdminReadingCompletionQuestionController extends Controller
{
    public function __construct(private readonly ReadingCompletionQuestionService $completion)
    {
    }

    public function edit(ReadingQuestionGroup $group): View
    {
        return $this->index($group);
    }

    public function index(ReadingQuestionGroup $group): View
    {
        $group = $this->completion->loadGroupForBuilder($group);
        abort_unless($group->question_type?->isCompletionBuilderType(), 404);
        $this->authorize('update', $this->completion->readingTestForGroup($group));

        $settings = $this->completion->groupBuilderSettings($group);

        return view('pages.admin.reading-tests.completion.index', $this->builderViewData($group, $settings, false));
    }

    public function preview(ReadingQuestionGroup $group): View
    {
        $group = $this->completion->loadGroupForBuilder($group);
        abort_unless($group->question_type?->isCompletionBuilderType(), 404);
        $this->authorize('update', $this->completion->readingTestForGroup($group));

        $settings = $this->completion->groupBuilderSettings($group);

        return view('pages.admin.reading-tests.completion.index', $this->builderViewData($group, $settings, true));
    }

    public function saveTemplate(SaveCompletionTemplateRequest $request, ReadingQuestionGroup $group): RedirectResponse
    {
        return $this->handleTemplateSave(fn () => $this->completion->saveTemplate($group, $request->templateAttributes()));
    }

    public function saveTable(SaveCompletionTableRequest $request, ReadingQuestionGroup $group): RedirectResponse
    {
        return $this->handleTemplateSave(fn () => $this->completion->saveTable($group, $request->templateAttributes()));
    }

    public function saveFlowChart(SaveCompletionFlowChartRequest $request, ReadingQuestionGroup $group): RedirectResponse
    {
        return $this->handleTemplateSave(fn () => $this->completion->saveFlowChart($group, $request->templateAttributes()));
    }

    public function storeSentence(StoreCompletionSentenceRequest $request, ReadingQuestionGroup $group): RedirectResponse
    {
        $this->completion->storeSentenceQuestion($group, $request->questionAttributes());

        return back()->with('status', 'Question created successfully.');
    }

    public function update(UpdateCompletionQuestionRequest $request, ReadingQuestion $question): RedirectResponse
    {
        return $this->updateAnswer($request, $question);
    }

    public function updateAnswer(UpdateCompletionQuestionRequest $request, ReadingQuestion $question): RedirectResponse
    {
        $this->completion->updateQuestion($question, $request->questionAttributes());

        return back()->with('status', 'Question updated successfully.');
    }

    public function destroy(ReadingQuestion $question): RedirectResponse
    {
        $group = $question->group;

        if (! $group) {
            abort(404);
        }

        $this->authorize('update', $this->completion->readingTestForGroup($group));
        $this->completion->deleteQuestion($question);

        return back()->with('status', 'Question deleted successfully.');
    }

    public function bulkImport(BulkImportCompletionRequest $request, ReadingQuestionGroup $group): RedirectResponse
    {
        try {
            $count = $this->completion->bulkImport($group, [
                'import_text' => $request->importText(),
                'confirm_remove' => $request->boolean('confirm_remove'),
            ]);
        } catch (ValidationException $exception) {
            if ($exception->errors()['confirm_remove'] ?? false) {
                return back()
                    ->withInput()
                    ->withErrors($exception->errors())
                    ->with('completion_confirm_remove', true);
            }

            throw $exception;
        }

        return back()->with('status', "{$count} blank(s) imported successfully.");
    }

    public function reorder(ReorderCompletionQuestionsRequest $request, ReadingQuestionGroup $group): RedirectResponse
    {
        $this->completion->reorderQuestions($group, $request->questionIds());

        return back()->with('status', 'Question order updated successfully.');
    }

    public function detect(Request $request, ReadingQuestionGroup $group): JsonResponse
    {
        $group = $this->completion->loadGroupForBuilder($group);
        abort_unless($group->question_type?->isCompletionBuilderType(), 404);
        $this->authorize('update', $this->completion->readingTestForGroup($group));

        $preview = $this->completion->liveDetectPreview($group, (string) $request->input('content', ''));

        return response()->json($preview);
    }

  /**
     * @param  array{answer_rule: string, custom_answer_rule: ?string, template_html: string, table_data: ?array, flow_steps: ?array}  $settings
     * @return array<string, mixed>
     */
    private function builderViewData(ReadingQuestionGroup $group, array $settings, bool $showPreview): array
    {
        $existingNumbers = $group->questions
            ->pluck('question_number')
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value) => $value > 0)
            ->values()
            ->all();

        return [
            'test' => $group->passage->test,
            'passage' => $group->passage,
            'group' => $group,
            'questions' => $group->questions,
            'type' => $group->question_type,
            'settings' => $settings,
            'answerRules' => \App\Enums\Exam\ReadingCompletionAnswerRule::cases(),
            'showPreview' => $showPreview,
            'previewHtml' => CompletionPlaceholderParser::renderPreviewHtml($settings['template_html']),
            'existingQuestionNumbers' => $existingNumbers,
            'detectedCount' => $group->questions->where('question_number', '>', 0)->count(),
            'expectedCount' => $group->expected_questions_count,
        ];
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

            throw $exception;
        }

        return back()->with('status', 'Template saved and questions synced successfully.');
    }
}
