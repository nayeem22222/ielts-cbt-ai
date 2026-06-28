<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Listening;

use App\Actions\Listening\QuestionTypes\BuildQuestionTypeSchemaAction;
use App\Actions\Listening\QuestionTypes\GenerateQuestionTypePreviewAction;
use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Listening\ReorderListeningQuestionGroupsRequest;
use App\Http\Requests\Admin\Listening\StoreListeningQuestionGroupRequest;
use App\Http\Requests\Admin\Listening\UpdateListeningQuestionGroupRequest;
use App\Models\Listening\ListeningAudio;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Services\Listening\ListeningQuestionGroupService;
use App\Support\Listening\ListeningGroupInteraction;
use App\Support\Listening\ListeningQuestionGroupDefaults;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ListeningQuestionGroupController extends Controller
{
    public function __construct(
        private readonly ListeningQuestionGroupService $groups,
        private readonly BuildQuestionTypeSchemaAction $buildSchema,
        private readonly GenerateQuestionTypePreviewAction $generatePreview,
    ) {}

    public function index(ListeningTest $listeningTest, ListeningSection $section): View|RedirectResponse
    {
        $this->authorize('viewAny', [ListeningQuestionGroup::class, $listeningTest, $section]);

        if ($redirect = $this->guardSectionBelongsToTest($listeningTest, $section)) {
            return $redirect;
        }

        return view('admin.listening.question-groups.index', $this->sharedViewData($listeningTest, $section, [
            'groups' => $this->groups->listForSection($section),
            'availableRanges' => $this->groups->getAvailableQuestionRanges($section),
        ]));
    }

    public function create(ListeningTest $listeningTest, ListeningSection $section): View|RedirectResponse
    {
        $this->authorize('create', [ListeningQuestionGroup::class, $listeningTest, $section]);

        if ($redirect = $this->guardSectionBelongsToTest($listeningTest, $section)) {
            return $redirect;
        }

        $suggested = $this->groups->suggestNextQuestionRange($section);
        $defaultType = ListeningQuestionType::FormCompletion;

        return view('admin.listening.question-groups.create', $this->sharedViewData($listeningTest, $section, [
            'group' => new ListeningQuestionGroup([
                'is_active' => true,
                'layout_type' => ListeningLayoutType::Form,
                'question_type' => $defaultType,
                'title' => $suggested
                    ? ListeningQuestionGroupDefaults::title($suggested['start'], $suggested['end'])
                    : '',
                'instruction' => ListeningQuestionGroupDefaults::instruction($defaultType, (int) $section->section_number),
                'start_question_number' => $suggested['start'] ?? $section->start_question_number,
                'end_question_number' => $suggested['end'] ?? $section->start_question_number,
            ]),
            'audios' => ListeningAudio::query()->orderBy('original_name')->get(['id', 'original_name']),
            'availableRanges' => $this->groups->getAvailableQuestionRanges($section),
            'suggestedRange' => $suggested,
        ]));
    }

    public function storeBlank(ListeningTest $listeningTest, ListeningSection $section): RedirectResponse
    {
        $this->authorize('create', [ListeningQuestionGroup::class, $listeningTest, $section]);

        if ($redirect = $this->guardSectionBelongsToTest($listeningTest, $section)) {
            return $redirect;
        }

        try {
            $group = $this->groups->createBlank($listeningTest, $section);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.listening.tests.builder.index', [
                    'listeningTest' => $listeningTest,
                    'section' => $section->id,
                ])
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('admin.listening.tests.builder.index', [
                'listeningTest' => $listeningTest,
                'section' => $section->id,
                'question_group' => $group->id,
            ])
            ->with('status', 'Question group created.');
    }

    public function duplicate(
        ListeningTest $listeningTest,
        ListeningSection $section,
        ListeningQuestionGroup $group,
    ): RedirectResponse {
        $this->authorize('create', [ListeningQuestionGroup::class, $listeningTest, $section]);

        if ($redirect = $this->guardHierarchy($listeningTest, $section, $group)) {
            return $redirect;
        }

        try {
            $copy = $this->groups->duplicate($listeningTest, $section, $group);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('admin.listening.tests.builder.index', [
                'listeningTest' => $listeningTest,
                'section' => $section->id,
                'question_group' => $copy->id,
            ])
            ->with('status', 'Question group duplicated.');
    }

    public function store(StoreListeningQuestionGroupRequest $request, ListeningTest $listeningTest, ListeningSection $section): RedirectResponse
    {
        if ($redirect = $this->guardSectionBelongsToTest($listeningTest, $section)) {
            return $redirect;
        }

        try {
            $group = $this->groups->create($listeningTest, $section, $request->validated());
        } catch (ValidationException $exception) {
            return back()->withInput()->withErrors($exception->errors());
        }

        return redirect()
            ->route('admin.listening.tests.builder.index', [
                'listeningTest' => $listeningTest,
                'section' => $section->id,
                'question_group' => $group->id,
            ])
            ->with('status', 'Listening question group created successfully.');
    }

    public function show(ListeningTest $listeningTest, ListeningSection $section, ListeningQuestionGroup $group): View|RedirectResponse
    {
        $this->authorize('view', $group);

        if ($redirect = $this->guardHierarchy($listeningTest, $section, $group)) {
            return $redirect;
        }

        $group->loadCount('questions');
        $group->load('questions');

        return view('admin.listening.question-groups.show', $this->sharedViewData($listeningTest, $section, [
            'group' => $group,
            'readiness' => $this->groups->getGroupReadiness($group),
            'preview' => $this->generatePreview->execute($group, $group->questions),
        ]));
    }

    public function edit(ListeningTest $listeningTest, ListeningSection $section, ListeningQuestionGroup $group): View|RedirectResponse
    {
        $this->authorize('update', $group);

        if ($redirect = $this->guardHierarchy($listeningTest, $section, $group)) {
            return $redirect;
        }

        return view('admin.listening.question-groups.edit', $this->sharedViewData($listeningTest, $section, [
            'group' => $group,
            'audios' => ListeningAudio::query()->orderBy('original_name')->get(['id', 'original_name']),
            'availableRanges' => $this->groups->getAvailableQuestionRanges($section),
        ]));
    }

    public function update(UpdateListeningQuestionGroupRequest $request, ListeningTest $listeningTest, ListeningSection $section, ListeningQuestionGroup $group): RedirectResponse
    {
        if ($redirect = $this->guardHierarchy($listeningTest, $section, $group)) {
            return $redirect;
        }

        try {
            $this->groups->update($listeningTest, $section, $group, $request->validated());
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.listening.tests.builder.index', [
                    'listeningTest' => $listeningTest,
                    'section' => $section->id,
                    'question_group' => $group->id,
                ])
                ->withInput()
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('admin.listening.tests.builder.index', [
                'listeningTest' => $listeningTest,
                'section' => $section->id,
                'question_group' => $group->id,
            ])
            ->with('status', 'Listening question group updated successfully.');
    }

    public function destroy(ListeningTest $listeningTest, ListeningSection $section, ListeningQuestionGroup $group): RedirectResponse
    {
        $this->authorize('delete', $group);

        if ($redirect = $this->guardHierarchy($listeningTest, $section, $group)) {
            return $redirect;
        }

        try {
            $this->groups->delete($listeningTest, $section, $group);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('admin.listening.tests.builder.index', [
                'listeningTest' => $listeningTest,
                'section' => $section->id,
            ])
            ->with('status', 'Listening question group deleted successfully.');
    }

    public function reorder(
        ReorderListeningQuestionGroupsRequest $request,
        ListeningTest $listeningTest,
        ListeningSection $section,
    ): RedirectResponse {
        if ($redirect = $this->guardSectionBelongsToTest($listeningTest, $section)) {
            return $redirect;
        }

        $this->groups->reorder($section, $request->orderedIds());
        $firstId = $request->orderedIds()[0] ?? null;

        return redirect()
            ->route('admin.listening.tests.builder.index', array_filter([
                'listeningTest' => $listeningTest,
                'section' => $section->id,
                'question_group' => $firstId,
            ]))
            ->with('status', 'Question group order updated.');
    }

    public function moveUp(
        ListeningTest $listeningTest,
        ListeningSection $section,
        ListeningQuestionGroup $group,
    ): RedirectResponse {
        $this->authorize('update', $group);

        if ($redirect = $this->guardHierarchy($listeningTest, $section, $group)) {
            return $redirect;
        }

        $this->groups->moveUp($group);

        return redirect()
            ->route('admin.listening.tests.builder.index', [
                'listeningTest' => $listeningTest,
                'section' => $section->id,
                'question_group' => $group->id,
            ])
            ->with('status', 'Question group moved up.');
    }

    public function moveDown(
        ListeningTest $listeningTest,
        ListeningSection $section,
        ListeningQuestionGroup $group,
    ): RedirectResponse {
        $this->authorize('update', $group);

        if ($redirect = $this->guardHierarchy($listeningTest, $section, $group)) {
            return $redirect;
        }

        $this->groups->moveDown($group);

        return redirect()
            ->route('admin.listening.tests.builder.index', [
                'listeningTest' => $listeningTest,
                'section' => $section->id,
                'question_group' => $group->id,
            ])
            ->with('status', 'Question group moved down.');
    }

    public function updateInteractionSettings(Request $request, ListeningQuestionGroup $group): RedirectResponse
    {
        $group->loadMissing('section.test');
        $test = $group->section?->test;

        if ($test === null) {
            abort(404);
        }

        $this->authorize('update', $test);

        $data = $request->validate([
            'interaction_mode' => ['required', 'string', 'in:select,input,drag_drop'],
            'allow_reuse' => ['nullable', 'boolean'],
        ]);

        $settings = ListeningGroupInteraction::mergeSettings($group, [
            'interaction_mode' => $data['interaction_mode'],
            'allow_reuse' => $request->boolean('allow_reuse'),
        ]);

        $group->forceFill(['settings' => $settings])->save();

        if ($group->question_type?->isMatchingBuilderType()) {
            $options = is_array($group->options) ? $group->options : [];
            $options['allow_choice_reuse'] = $request->boolean('allow_reuse');
            $group->forceFill(['options' => $options])->save();
        }

        return back()->with('status', 'Interaction settings saved.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sharedViewData(ListeningTest $listeningTest, ListeningSection $section, array $data = []): array
    {
        $registry = app(\App\Services\Listening\QuestionTypes\ListeningQuestionTypeRegistry::class);
        $schemas = array_map(
            fn ($type) => $this->buildSchema->execute($type),
            $registry->all(),
        );

        return array_merge([
            'listeningTest' => $listeningTest,
            'section' => $section,
            'routePrefix' => 'admin.listening.tests',
            'sectionsRoutePrefix' => 'admin.listening.tests.sections',
            'groupsRoutePrefix' => 'admin.listening.tests.sections.groups',
            'questionsRoutePrefix' => 'admin.listening.tests.sections.groups.questions',
            'builderRoutePrefix' => 'admin.listening.tests.builder',
            'questionTypes' => ListeningQuestionType::cases(),
            'enabledQuestionTypes' => $registry->all(),
            'questionTypeSchemas' => $schemas,
            'layoutTypes' => ListeningLayoutType::cases(),
            'instructionDefaults' => ListeningQuestionGroupDefaults::instructionMap(),
            'suggestedRange' => $this->groups->suggestNextQuestionRange($section),
        ], $data);
    }

    private function guardSectionBelongsToTest(ListeningTest $listeningTest, ListeningSection $section): ?RedirectResponse
    {
        if ((int) $section->listening_test_id !== (int) $listeningTest->id) {
            return redirect()
                ->route('admin.listening.tests.sections.index', $listeningTest)
                ->with('error', 'Section does not belong to this test.');
        }

        return null;
    }

    private function guardHierarchy(ListeningTest $listeningTest, ListeningSection $section, ListeningQuestionGroup $group): ?RedirectResponse
    {
        if ($redirect = $this->guardSectionBelongsToTest($listeningTest, $section)) {
            return $redirect;
        }

        if ((int) $group->listening_section_id !== (int) $section->id) {
            return redirect()
                ->route('admin.listening.tests.sections.groups.index', [$listeningTest, $section])
                ->with('error', 'Group does not belong to this section.');
        }

        return null;
    }
}
