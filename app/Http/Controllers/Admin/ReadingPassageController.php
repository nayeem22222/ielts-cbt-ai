<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderReadingPassagesRequest;
use App\Http\Requests\Admin\StoreReadingPassageRequest;
use App\Http\Requests\Admin\UpdateReadingPassageRequest;
use App\Models\ReadingPassage;
use App\Models\ReadingTest;
use App\Services\Admin\Exam\ReadingPassageBuilderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReadingPassageController extends Controller
{
    public function __construct(private readonly ReadingPassageBuilderService $passages)
    {
    }

    public function store(ReadingTest $readingTest): RedirectResponse
    {
        $this->authorize('update', $readingTest);

        $passage = $this->passages->createBlank($readingTest);

        return redirect()
            ->route('admin.reading-tests.builder', [
                'readingTest' => $readingTest,
                'passage' => $passage->id,
            ])
            ->with('status', 'Passage created. Add content and save.');
    }

    public function update(UpdateReadingPassageRequest $request, ReadingTest $readingTest, ReadingPassage $passage): RedirectResponse
    {
        $this->passages->ensureBelongsToTest($passage, $readingTest);

        $this->passages->update($passage, $request->passageAttributes());

        return redirect()
            ->route('admin.reading-tests.builder', [
                'readingTest' => $readingTest,
                'passage' => $passage->id,
            ])
            ->with('status', 'Passage saved successfully.');
    }

    public function destroy(ReadingTest $readingTest, ReadingPassage $passage): RedirectResponse
    {
        $this->authorize('update', $readingTest);
        $this->passages->ensureBelongsToTest($passage, $readingTest);

        $this->passages->delete($passage);

        return redirect()
            ->route('admin.reading-tests.builder', $readingTest)
            ->with('status', 'Passage deleted successfully.');
    }

    public function duplicate(Request $request, ReadingTest $readingTest, ReadingPassage $passage): RedirectResponse
    {
        $this->authorize('update', $readingTest);
        $this->passages->ensureBelongsToTest($passage, $readingTest);

        $withGroups = $request->boolean('with_question_groups', false);
        $copy = $this->passages->duplicate($passage, $withGroups);

        return redirect()
            ->route('admin.reading-tests.builder', [
                'readingTest' => $readingTest,
                'passage' => $copy->id,
            ])
            ->with('status', 'Passage duplicated successfully.');
    }

    public function moveUp(ReadingTest $readingTest, ReadingPassage $passage): RedirectResponse
    {
        $this->authorize('update', $readingTest);
        $this->passages->ensureBelongsToTest($passage, $readingTest);

        $this->passages->moveUp($passage);

        return redirect()
            ->route('admin.reading-tests.builder', [
                'readingTest' => $readingTest,
                'passage' => $passage->id,
            ])
            ->with('status', 'Passage moved up.');
    }

    public function moveDown(ReadingTest $readingTest, ReadingPassage $passage): RedirectResponse
    {
        $this->authorize('update', $readingTest);
        $this->passages->ensureBelongsToTest($passage, $readingTest);

        $this->passages->moveDown($passage);

        return redirect()
            ->route('admin.reading-tests.builder', [
                'readingTest' => $readingTest,
                'passage' => $passage->id,
            ])
            ->with('status', 'Passage moved down.');
    }

    public function reorder(ReorderReadingPassagesRequest $request, ReadingTest $readingTest): RedirectResponse
    {
        $this->passages->reorder($readingTest, $request->orderedIds());

        $firstId = $request->orderedIds()[0] ?? null;

        return redirect()
            ->route('admin.reading-tests.builder', array_filter([
                'readingTest' => $readingTest,
                'passage' => $firstId,
            ]))
            ->with('status', 'Passage order updated.');
    }
}
