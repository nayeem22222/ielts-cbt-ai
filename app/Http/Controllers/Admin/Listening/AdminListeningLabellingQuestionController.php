<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Listening;

use App\Enums\Exam\ReadingCompletionAnswerRule;
use App\Http\Controllers\Admin\Listening\Concerns\InteractsWithListeningQuestionBuilder;
use App\Http\Controllers\Controller;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Services\Listening\Builders\ListeningLabellingQuestionBuilderService;
use App\Support\Listening\ListeningQuestionBuilderRoutes;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminListeningLabellingQuestionController extends Controller
{
    use InteractsWithListeningQuestionBuilder;

    public function __construct(private readonly ListeningLabellingQuestionBuilderService $labelling) {}

    public function edit(ListeningQuestionGroup $group): View
    {
        return $this->index($group);
    }

    public function index(ListeningQuestionGroup $group): View
    {
        $group = $this->labelling->loadGroupForBuilder($group);
        abort_unless($group->question_type?->isLabellingBuilderType(), 404);
        $this->authorize('update', $this->labelling->listeningTestForGroup($group));

        $settings = $this->labelling->groupBuilderSettings($group);
        $questions = $this->labelling->presentQuestions($group);

        return view('admin.listening.question-builders.labelling.index', [
            'listeningTest' => $group->section->test,
            'section' => $group->section,
            'group' => $group,
            'questions' => $questions,
            'type' => $group->question_type,
            'settings' => $settings,
            'answerRules' => ReadingCompletionAnswerRule::cases(),
            'showPreview' => request()->boolean('preview'),
            'backToGroupUrl' => ListeningQuestionBuilderRoutes::backToGroupUrl($group),
            'diagramImageUrl' => $settings['diagram_image']
                ? route('admin.listening-question-groups.labelling-questions.image', $group)
                : null,
            'labelCount' => count($settings['labels']),
            'expectedCount' => (int) $group->total_questions,
        ]);
    }

    public function preview(ListeningQuestionGroup $group): View
    {
        request()->merge(['preview' => 1]);

        return $this->index($group);
    }

    public function showImage(ListeningQuestionGroup $group): StreamedResponse
    {
        $group = $this->labelling->loadGroupForBuilder($group);
        abort_unless($group->question_type?->isLabellingBuilderType(), 404);
        $this->authorize('update', $this->labelling->listeningTestForGroup($group));

        return $this->labelling->streamDiagramImage($group);
    }

    public function uploadDiagram(Request $request, ListeningQuestionGroup $group): RedirectResponse
    {
        $this->authorize('update', $this->labelling->listeningTestForGroup($group));
        $this->logListeningQuestionPayload($request, 'labelling.upload_diagram');
        $request->validate(['diagram_image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120']]);

        try {
            $this->labelling->uploadDiagramImage($group, $request->file('diagram_image'));
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with('status', 'Diagram image uploaded successfully.');
    }

    public function saveLabels(Request $request, ListeningQuestionGroup $group): RedirectResponse
    {
        $this->authorize('update', $this->labelling->listeningTestForGroup($group));
        $this->logListeningQuestionPayload($request, 'labelling.save_labels');
        $data = $request->validate([
            'answer_rule' => ['required', 'string'],
            'custom_answer_rule' => ['nullable', 'string'],
            'labels' => ['required', 'array'],
            'labels.*.question_number' => ['required', 'integer'],
            'labels.*.x' => ['nullable', 'numeric'],
            'labels.*.y' => ['nullable', 'numeric'],
            'labels.*.label' => ['nullable', 'string'],
            'labels.*.correct_answer' => ['nullable', 'string'],
            'labels.*.alternative_answers' => ['nullable', 'array'],
            'labels.*.case_sensitive' => ['nullable', 'boolean'],
            'labels.*.explanation' => ['nullable', 'string'],
            'labels.*.difficulty' => ['nullable', 'string'],
            'confirm_remove' => ['nullable', 'boolean'],
        ]);

        try {
            $this->labelling->saveLabels($group, $data);
        } catch (ValidationException $exception) {
            if ($exception->errors()['confirm_remove'] ?? false) {
                return back()
                    ->withInput()
                    ->withErrors($exception->errors())
                    ->with('diagram_confirm_remove', true);
            }

            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with('status', 'Diagram labels saved and questions synced successfully.');
    }

    public function updateAnswer(Request $request, ListeningQuestion $question): RedirectResponse
    {
        $group = $question->group ?? abort(404);
        $this->authorize('update', $this->labelling->listeningTestForGroup($group));
        $this->logListeningQuestionPayload($request, 'labelling.update_answer');
        $data = $request->validate(array_merge([
            'question_number' => ['required', 'integer', 'min:1', 'max:40'],
            'correct_answer' => $this->textAnswerRules(),
            'case_sensitive' => ['nullable', 'boolean'],
            'explanation' => ['nullable', 'string'],
        ], $this->alternativeAnswerRules()));

        try {
            $this->labelling->updateQuestion($question, $data);
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return back()->with('status', 'Label answer updated successfully.');
    }

    public function destroy(ListeningQuestion $question): RedirectResponse
    {
        $group = $question->group;

        if (! $group) {
            abort(404);
        }

        $this->authorize('update', $this->labelling->listeningTestForGroup($group));
        $this->labelling->deleteQuestion($question);

        return back()->with('status', 'Question deleted successfully.');
    }
}
