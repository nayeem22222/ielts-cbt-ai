<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Listening;

use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningQuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Listening\BulkCreateListeningQuestionsRequest;
use App\Http\Requests\Admin\Listening\ReorderListeningQuestionsRequest;
use App\Http\Requests\Admin\Listening\StoreListeningQuestionRequest;
use App\Http\Requests\Admin\Listening\UpdateListeningQuestionRequest;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Services\Listening\ListeningQuestionService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class ListeningQuestionController extends Controller
{
    public function __construct(
        private readonly ListeningQuestionService $questions,
    ) {}

    public function index(ListeningTest $listeningTest, ListeningSection $section, ListeningQuestionGroup $group): View|RedirectResponse
    {
        $this->authorize('viewAny', [ListeningQuestion::class, $group]);

        if ($redirect = $this->guardHierarchy($listeningTest, $section, $group)) {
            return $redirect;
        }

        if ($group->question_type !== null) {
            return redirect()->route($group->question_type->questionBuilderRouteName(), $group);
        }

        return view('admin.listening.questions.index', $this->sharedViewData($listeningTest, $section, $group, [
            'questions' => $this->questions->listForGroup($group),
        ]));
    }

    public function create(ListeningTest $listeningTest, ListeningSection $section, ListeningQuestionGroup $group): View|RedirectResponse
    {
        $this->authorize('create', [ListeningQuestion::class, $group]);

        if ($redirect = $this->guardHierarchy($listeningTest, $section, $group)) {
            return $redirect;
        }

        return view('admin.listening.questions.create', $this->sharedViewData($listeningTest, $section, $group, [
            'question' => new ListeningQuestion([
                'question_type' => $group->question_type,
                'answer_format' => ListeningAnswerFormat::Text,
                'marks' => config('listening.questions.default_marks', 1),
                'is_active' => true,
                'is_required' => true,
            ]),
        ]));
    }

    public function store(StoreListeningQuestionRequest $request, ListeningTest $listeningTest, ListeningSection $section, ListeningQuestionGroup $group): RedirectResponse
    {
        if ($redirect = $this->guardHierarchy($listeningTest, $section, $group)) {
            return $redirect;
        }

        try {
            $question = $this->questions->create($listeningTest, $section, $group, $request->validated());
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors())->with('error', 'Question could not be saved.');
        }

        return back()->with('status', 'Listening question created successfully.');
    }

    public function show(ListeningTest $listeningTest, ListeningSection $section, ListeningQuestionGroup $group, ListeningQuestion $question): View|RedirectResponse
    {
        $this->authorize('view', $question);

        if ($redirect = $this->guardHierarchy($listeningTest, $section, $group, $question)) {
            return $redirect;
        }

        return view('admin.listening.questions.show', $this->sharedViewData($listeningTest, $section, $group, [
            'question' => $question,
            'readiness' => $this->questions->getQuestionReadiness($question),
        ]));
    }

    public function edit(ListeningTest $listeningTest, ListeningSection $section, ListeningQuestionGroup $group, ListeningQuestion $question): View|RedirectResponse
    {
        $this->authorize('update', $question);

        if ($redirect = $this->guardHierarchy($listeningTest, $section, $group, $question)) {
            return $redirect;
        }

        return view('admin.listening.questions.edit', $this->sharedViewData($listeningTest, $section, $group, [
            'question' => $question,
        ]));
    }

    public function update(UpdateListeningQuestionRequest $request, ListeningTest $listeningTest, ListeningSection $section, ListeningQuestionGroup $group, ListeningQuestion $question): RedirectResponse
    {
        if ($redirect = $this->guardHierarchy($listeningTest, $section, $group, $question)) {
            return $redirect;
        }

        try {
            $this->questions->update($listeningTest, $section, $group, $question, $request->validated());
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors())->with('error', 'Question could not be saved.');
        }

        return back()->with('status', 'Listening question updated successfully.');
    }

    public function destroy(ListeningTest $listeningTest, ListeningSection $section, ListeningQuestionGroup $group, ListeningQuestion $question): RedirectResponse
    {
        $this->authorize('delete', $question);

        if ($redirect = $this->guardHierarchy($listeningTest, $section, $group, $question)) {
            return $redirect;
        }

        $this->questions->delete($question);

        return redirect()
            ->route('admin.listening.tests.sections.groups.questions.index', [$listeningTest, $section, $group])
            ->with('status', 'Listening question deleted successfully.');
    }

    public function bulkCreate(BulkCreateListeningQuestionsRequest $request, ListeningTest $listeningTest, ListeningSection $section, ListeningQuestionGroup $group): RedirectResponse
    {
        if ($redirect = $this->guardHierarchy($listeningTest, $section, $group)) {
            return $redirect;
        }

        try {
            $result = $this->questions->bulkCreateFromGroupRange($group);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        $message = "Listening questions created from group range successfully. Created: {$result['created']}, skipped: {$result['skipped']}.";

        return back()->with('status', $message);
    }

    public function reorder(ReorderListeningQuestionsRequest $request, ListeningTest $listeningTest, ListeningSection $section, ListeningQuestionGroup $group): RedirectResponse
    {
        if ($redirect = $this->guardHierarchy($listeningTest, $section, $group)) {
            return $redirect;
        }

        try {
            $this->questions->reorder($group, array_map('intval', $request->input('questions', [])));
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Listening questions reordered successfully.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sharedViewData(ListeningTest $listeningTest, ListeningSection $section, ListeningQuestionGroup $group, array $data = []): array
    {
        return array_merge([
            'listeningTest' => $listeningTest,
            'section' => $section,
            'group' => $group,
            'routePrefix' => 'admin.listening.tests',
            'sectionsRoutePrefix' => 'admin.listening.tests.sections',
            'builderRoutePrefix' => 'admin.listening.tests.builder',
            'groupsRoutePrefix' => 'admin.listening.tests.sections.groups',
            'questionsRoutePrefix' => 'admin.listening.tests.sections.groups.questions',
            'questionTypes' => ListeningQuestionType::cases(),
            'answerFormats' => ListeningAnswerFormat::cases(),
        ], $data);
    }

    private function guardHierarchy(
        ListeningTest $listeningTest,
        ListeningSection $section,
        ListeningQuestionGroup $group,
        ?ListeningQuestion $question = null,
    ): ?RedirectResponse {
        if ((int) $section->listening_test_id !== (int) $listeningTest->id) {
            return redirect()->route('admin.listening.tests.builder.index', $listeningTest)
                ->with('error', 'Section does not belong to this test.');
        }

        if ((int) $group->listening_section_id !== (int) $section->id) {
            return redirect()->route('admin.listening.tests.sections.groups.index', [$listeningTest, $section])
                ->with('error', 'Group does not belong to this section.');
        }

        if ($question !== null && (int) $question->listening_question_group_id !== (int) $group->id) {
            return redirect()->route('admin.listening.tests.sections.groups.questions.index', [$listeningTest, $section, $group])
                ->with('error', 'Question does not belong to this group.');
        }

        return null;
    }
}
