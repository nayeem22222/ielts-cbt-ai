<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Completion\BulkImportCompletionRequest;
use App\Http\Requests\Admin\Completion\ReorderCompletionQuestionsRequest;
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

class AdminReadingCompletionQuestionController extends Controller
{
    public function __construct(private readonly ReadingCompletionQuestionService $completion)
    {
    }

    public function index(ReadingQuestionGroup $group): View
    {
        $group = $this->completion->loadGroupForBuilder($group);
        abort_unless($group->question_type?->isCompletionBuilderType(), 404);
        $this->authorize('update', $this->completion->readingTestForGroup($group));

        $settings = $this->completion->groupBuilderSettings($group);

        return view('pages.admin.reading-tests.completion.index', [
            'test' => $group->passage->test,
            'passage' => $group->passage,
            'group' => $group,
            'questions' => $group->questions,
            'type' => $group->question_type,
            'settings' => $settings,
            'answerRules' => \App\Enums\Exam\ReadingCompletionAnswerRule::cases(),
            'showPreview' => request()->boolean('preview'),
            'previewHtml' => CompletionPlaceholderParser::renderPreviewHtml($settings['template_html']),
        ]);
    }

    public function saveTemplate(SaveCompletionTemplateRequest $request, ReadingQuestionGroup $group): RedirectResponse
    {
        try {
            $this->completion->saveTemplate($group, $request->templateAttributes());
        } catch (\Illuminate\Validation\ValidationException $exception) {
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

    public function storeSentence(StoreCompletionSentenceRequest $request, ReadingQuestionGroup $group): RedirectResponse
    {
        $this->completion->storeSentenceQuestion($group, $request->questionAttributes());

        return back()->with('status', 'Question created successfully.');
    }

    public function update(UpdateCompletionQuestionRequest $request, ReadingQuestion $question): RedirectResponse
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
        } catch (\Illuminate\Validation\ValidationException $exception) {
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

        $content = (string) $request->input('content', '');
        $numbers = $this->completion->detectPlaceholders($content);

        return response()->json([
            'placeholders' => $numbers,
            'count' => count($numbers),
        ]);
    }
}
