<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Objective\BulkImportObjectiveRequest;
use App\Http\Requests\Admin\Objective\ReorderObjectiveQuestionsRequest;
use App\Http\Requests\Admin\Objective\StoreObjectiveOptionRequest;
use App\Http\Requests\Admin\Objective\StoreObjectiveQuestionRequest;
use App\Http\Requests\Admin\Objective\UpdateObjectiveOptionRequest;
use App\Http\Requests\Admin\Objective\UpdateObjectiveQuestionRequest;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingQuestionOption;
use App\Services\Admin\Exam\ReadingObjectiveQuestionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminReadingObjectiveQuestionController extends Controller
{
    public function __construct(private readonly ReadingObjectiveQuestionService $objective)
    {
    }

    public function index(ReadingQuestionGroup $group): View
    {
        $group = $this->objective->loadGroupForBuilder($group);
        abort_unless($group->question_type?->isObjectiveBuilderType(), 404);
        $this->authorize('update', $this->objective->readingTestForGroup($group));

        return view('pages.admin.reading-tests.objective.index', [
            'test' => $group->passage->test,
            'passage' => $group->passage,
            'group' => $group,
            'questions' => $group->questions,
            'type' => $group->question_type,
            'answerChoices' => $group->question_type->objectiveAnswerChoices() ?? [],
            'showPreview' => request()->boolean('preview'),
        ]);
    }

    public function store(StoreObjectiveQuestionRequest $request, ReadingQuestionGroup $group): RedirectResponse
    {
        $this->objective->storeQuestion($group, $request->questionAttributes());

        return back()->with('status', 'Question created successfully.');
    }

    public function update(UpdateObjectiveQuestionRequest $request, ReadingQuestion $question): RedirectResponse
    {
        $this->objective->updateQuestion($question, $request->questionAttributes());

        return back()->with('status', 'Question updated successfully.');
    }

    public function destroy(ReadingQuestion $question): RedirectResponse
    {
        $group = $question->group;

        if (! $group) {
            abort(404);
        }

        $this->authorize('update', $this->objective->readingTestForGroup($group));
        $this->objective->deleteQuestion($question);

        return back()->with('status', 'Question deleted successfully.');
    }

    public function duplicate(ReadingQuestion $question): RedirectResponse
    {
        $group = $question->group;

        if (! $group) {
            abort(404);
        }

        $this->authorize('update', $this->objective->readingTestForGroup($group));
        $copy = $this->objective->duplicateQuestion($question);

        return back()->with('status', 'Question duplicated. Assign a question number and save.');
    }

    public function storeOption(StoreObjectiveOptionRequest $request, ReadingQuestion $question): RedirectResponse
    {
        $this->objective->storeOption($question, $request->optionAttributes());

        return back()->with('status', 'Option added successfully.');
    }

    public function updateOption(UpdateObjectiveOptionRequest $request, ReadingQuestionOption $option): RedirectResponse
    {
        $this->objective->updateOption($option, $request->optionAttributes());

        return back()->with('status', 'Option updated successfully.');
    }

    public function deleteOption(Request $request, ReadingQuestionOption $option): RedirectResponse
    {
        $question = $option->question;

        if (! $question?->group) {
            abort(404);
        }

        $this->authorize('update', $this->objective->readingTestForGroup($question->group));
        $this->objective->deleteOption($option, $request->boolean('confirm_delete'));

        return back()->with('status', 'Option deleted successfully.');
    }

    public function bulkImport(BulkImportObjectiveRequest $request, ReadingQuestionGroup $group): RedirectResponse
    {
        $count = $this->objective->bulkImport($group, ['import_text' => $request->importText()]);

        return back()->with('status', "{$count} question(s) imported successfully.");
    }

    public function reorder(ReorderObjectiveQuestionsRequest $request, ReadingQuestionGroup $group): RedirectResponse
    {
        $this->objective->reorderQuestions($group, $request->questionIds());

        return back()->with('status', 'Question order updated successfully.');
    }
}
