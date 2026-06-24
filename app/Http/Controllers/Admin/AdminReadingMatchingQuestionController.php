<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Matching\BulkImportMatchingRequest;
use App\Http\Requests\Admin\Matching\ReorderMatchingRequest;
use App\Http\Requests\Admin\Matching\StoreMatchingOptionRequest;
use App\Http\Requests\Admin\Matching\StoreMatchingQuestionRequest;
use App\Http\Requests\Admin\Matching\UpdateScopedMatchingOptionRequest;
use App\Http\Requests\Admin\Matching\UpdateScopedMatchingQuestionRequest;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingQuestionOption;
use App\Services\Admin\Exam\ReadingMatchingQuestionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminReadingMatchingQuestionController extends Controller
{
    public function __construct(private readonly ReadingMatchingQuestionService $matching)
    {
    }

    public function index(ReadingQuestionGroup $group): View
    {
        $group = $this->matching->loadGroupForBuilder($group);
        abort_unless($group->question_type?->isMatchingBuilderType(), 404);
        $this->authorize('update', $this->matching->readingTestForGroup($group));

        $test = $group->passage->test;
        $passage = $group->passage;

        return view('pages.admin.reading-tests.matching.index', [
            'test' => $test,
            'passage' => $passage,
            'group' => $group,
            'options' => $group->groupOptions,
            'questions' => $group->questions,
            'showPreview' => request()->boolean('preview'),
        ]);
    }

    public function storeOption(StoreMatchingOptionRequest $request, ReadingQuestionGroup $group): RedirectResponse
    {
        $this->matching->storeOption($group, $request->optionAttributes());

        return back()->with('status', 'Option added successfully.');
    }

    public function storeQuestion(StoreMatchingQuestionRequest $request, ReadingQuestionGroup $group): RedirectResponse
    {
        $this->matching->storeQuestion($group, $request->questionAttributes());

        return back()->with('status', 'Question added successfully.');
    }

    public function updateOption(UpdateScopedMatchingOptionRequest $request, ReadingQuestionOption $option): RedirectResponse
    {
        $this->matching->updateOption($option, $request->optionAttributes());

        return back()->with('status', 'Option updated successfully.');
    }

    public function deleteOption(Request $request, ReadingQuestionOption $option): RedirectResponse
    {
        $group = $option->group;

        if (! $group) {
            abort(404);
        }

        $test = $this->matching->readingTestForGroup($group);
        $this->authorize('update', $test);

        $this->matching->deleteOption($option, $request->boolean('confirm_delete'));

        return back()->with('status', 'Option deleted successfully.');
    }

    public function updateQuestion(UpdateScopedMatchingQuestionRequest $request, ReadingQuestion $question): RedirectResponse
    {
        $this->matching->updateQuestion($question, $request->questionAttributes());

        return back()->with('status', 'Question updated successfully.');
    }

    public function deleteQuestion(ReadingQuestion $question): RedirectResponse
    {
        $group = $question->group;

        if (! $group) {
            abort(404);
        }

        $test = $this->matching->readingTestForGroup($group);
        $this->authorize('update', $test);

        $this->matching->deleteQuestion($question);

        return back()->with('status', 'Question deleted successfully.');
    }

    public function bulkImport(BulkImportMatchingRequest $request, ReadingQuestionGroup $group): RedirectResponse
    {
        $result = $this->matching->bulkImport($group, $request->importPayload());

        return back()->with(
            'status',
            "Bulk import complete: {$result['options']} option(s) and {$result['questions']} question(s) added."
        );
    }

    public function reorder(ReorderMatchingRequest $request, ReadingQuestionGroup $group): RedirectResponse
    {
        $this->matching->reorder($group, $request->reorderPayload());

        return back()->with('status', 'Order updated successfully.');
    }
}
