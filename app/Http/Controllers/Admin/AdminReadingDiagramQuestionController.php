<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Diagram\SaveDiagramLabelsRequest;
use App\Http\Requests\Admin\Diagram\UpdateDiagramQuestionRequest;
use App\Http\Requests\Admin\Diagram\UploadDiagramImageRequest;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Services\Admin\Exam\ReadingDiagramQuestionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminReadingDiagramQuestionController extends Controller
{
    public function __construct(private readonly ReadingDiagramQuestionService $diagram)
    {
    }

    public function edit(ReadingQuestionGroup $group): View
    {
        return $this->index($group);
    }

    public function index(ReadingQuestionGroup $group): View
    {
        $group = $this->diagram->loadGroupForBuilder($group);
        abort_unless($group->question_type?->isDiagramBuilderType(), 404);
        $this->authorize('update', $this->diagram->readingTestForGroup($group));

        $settings = $this->diagram->groupBuilderSettings($group);

        return view('pages.admin.reading-tests.diagram.index', [
            'test' => $group->passage->test,
            'passage' => $group->passage,
            'group' => $group,
            'questions' => $group->questions,
            'type' => $group->question_type,
            'settings' => $settings,
            'answerRules' => \App\Enums\Exam\ReadingCompletionAnswerRule::cases(),
            'showPreview' => request()->boolean('preview'),
            'diagramImageUrl' => $settings['diagram_image']
                ? route('admin.reading-question-groups.diagram-questions.image', $group)
                : null,
            'labelCount' => count($settings['labels']),
            'expectedCount' => $group->expected_questions_count,
        ]);
    }

    public function preview(ReadingQuestionGroup $group): View
    {
        request()->merge(['preview' => 1]);

        return $this->index($group);
    }

    public function showImage(ReadingQuestionGroup $group): StreamedResponse
    {
        $group = $this->diagram->loadGroupForBuilder($group);
        abort_unless($group->question_type?->isDiagramBuilderType(), 404);
        $this->authorize('update', $this->diagram->readingTestForGroup($group));

        return $this->diagram->streamDiagramImage($group);
    }

    public function uploadDiagram(UploadDiagramImageRequest $request, ReadingQuestionGroup $group): RedirectResponse
    {
        $this->diagram->uploadDiagramImage($group, $request->file('diagram_image'));

        return back()->with('status', 'Diagram image uploaded successfully.');
    }

    public function saveLabels(SaveDiagramLabelsRequest $request, ReadingQuestionGroup $group): RedirectResponse
    {
        try {
            $this->diagram->saveLabels($group, $request->labelAttributes());
        } catch (ValidationException $exception) {
            if ($exception->errors()['confirm_remove'] ?? false) {
                return back()
                    ->withInput()
                    ->withErrors($exception->errors())
                    ->with('diagram_confirm_remove', true);
            }

            throw $exception;
        }

        return back()->with('status', 'Diagram labels saved and questions synced successfully.');
    }

    public function updateAnswer(UpdateDiagramQuestionRequest $request, ReadingQuestion $question): RedirectResponse
    {
        $this->diagram->updateQuestion($question, $request->questionAttributes());

        return back()->with('status', 'Label answer updated successfully.');
    }

    public function deleteLabel(ReadingQuestion $question): RedirectResponse
    {
        $group = $question->group;

        if (! $group) {
            abort(404);
        }

        $this->authorize('update', $this->diagram->readingTestForGroup($group));
        $this->diagram->deleteLabel($question);

        return back()->with('status', 'Diagram label deleted successfully.');
    }
}
