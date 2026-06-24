<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderReadingQuestionGroupsRequest;
use App\Http\Requests\Admin\UpdateReadingQuestionGroupRequest;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingTest;
use App\Services\Admin\Exam\ReadingQuestionGroupBuilderService;
use Illuminate\Http\RedirectResponse;

class ReadingQuestionGroupController extends Controller
{
    public function __construct(private readonly ReadingQuestionGroupBuilderService $groups)
    {
    }

    public function store(ReadingTest $readingTest, ReadingPassage $passage): RedirectResponse
    {
        $this->authorize('update', $readingTest);
        $this->groups->ensurePassageBelongsToTest($passage, $readingTest);

        $group = $this->groups->createBlank($passage);

        return redirect()
            ->route('admin.reading-tests.builder', [
                'readingTest' => $readingTest,
                'passage' => $passage->id,
                'question_group' => $group->id,
            ])
            ->with('status', 'Question group created.');
    }

    public function update(
        UpdateReadingQuestionGroupRequest $request,
        ReadingTest $readingTest,
        ReadingPassage $passage,
        ReadingQuestionGroup $group,
    ): RedirectResponse {
        $this->groups->ensurePassageBelongsToTest($passage, $readingTest);
        $this->groups->ensureBelongsToPassage($group, $passage);

        $this->groups->update($group, $request->groupAttributes());

        return redirect()
            ->route('admin.reading-tests.builder', [
                'readingTest' => $readingTest,
                'passage' => $passage->id,
                'question_group' => $group->id,
            ])
            ->with('status', 'Question group saved successfully.');
    }

    public function destroy(
        ReadingTest $readingTest,
        ReadingPassage $passage,
        ReadingQuestionGroup $group,
    ): RedirectResponse {
        $this->authorize('update', $readingTest);
        $this->groups->ensurePassageBelongsToTest($passage, $readingTest);
        $this->groups->ensureBelongsToPassage($group, $passage);

        $this->groups->delete($group);

        return redirect()
            ->route('admin.reading-tests.builder', [
                'readingTest' => $readingTest,
                'passage' => $passage->id,
            ])
            ->with('status', 'Question group deleted successfully.');
    }

    public function duplicate(
        ReadingTest $readingTest,
        ReadingPassage $passage,
        ReadingQuestionGroup $group,
    ): RedirectResponse {
        $this->authorize('update', $readingTest);
        $this->groups->ensurePassageBelongsToTest($passage, $readingTest);
        $this->groups->ensureBelongsToPassage($group, $passage);

        $copy = $this->groups->duplicate($group);

        return redirect()
            ->route('admin.reading-tests.builder', [
                'readingTest' => $readingTest,
                'passage' => $passage->id,
                'question_group' => $copy->id,
            ])
            ->with('status', 'Question group duplicated.');
    }

    public function moveUp(
        ReadingTest $readingTest,
        ReadingPassage $passage,
        ReadingQuestionGroup $group,
    ): RedirectResponse {
        $this->authorize('update', $readingTest);
        $this->groups->ensurePassageBelongsToTest($passage, $readingTest);
        $this->groups->ensureBelongsToPassage($group, $passage);

        $this->groups->moveUp($group);

        return redirect()
            ->route('admin.reading-tests.builder', [
                'readingTest' => $readingTest,
                'passage' => $passage->id,
                'question_group' => $group->id,
            ])
            ->with('status', 'Question group moved up.');
    }

    public function moveDown(
        ReadingTest $readingTest,
        ReadingPassage $passage,
        ReadingQuestionGroup $group,
    ): RedirectResponse {
        $this->authorize('update', $readingTest);
        $this->groups->ensurePassageBelongsToTest($passage, $readingTest);
        $this->groups->ensureBelongsToPassage($group, $passage);

        $this->groups->moveDown($group);

        return redirect()
            ->route('admin.reading-tests.builder', [
                'readingTest' => $readingTest,
                'passage' => $passage->id,
                'question_group' => $group->id,
            ])
            ->with('status', 'Question group moved down.');
    }

    public function reorder(
        ReorderReadingQuestionGroupsRequest $request,
        ReadingTest $readingTest,
        ReadingPassage $passage,
    ): RedirectResponse {
        $this->groups->ensurePassageBelongsToTest($passage, $readingTest);
        $this->groups->reorder($passage, $request->orderedIds());

        $firstId = $request->orderedIds()[0] ?? null;

        return redirect()
            ->route('admin.reading-tests.builder', array_filter([
                'readingTest' => $readingTest,
                'passage' => $passage->id,
                'question_group' => $firstId,
            ]))
            ->with('status', 'Question group order updated.');
    }
}
