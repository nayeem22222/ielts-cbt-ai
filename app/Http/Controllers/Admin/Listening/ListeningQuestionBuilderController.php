<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Listening;

use App\Actions\Listening\QuestionTypes\BuildQuestionTypeSchemaAction;
use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Http\Controllers\Controller;
use App\Models\Listening\ListeningAudio;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Services\Listening\ListeningQuestionBuilderService;
use App\Support\Listening\ListeningQuestionGroupDefaults;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ListeningQuestionBuilderController extends Controller
{
    public function __construct(
        private readonly ListeningQuestionBuilderService $builder,
        private readonly BuildQuestionTypeSchemaAction $buildSchema,
    ) {}

    public function index(ListeningTest $listeningTest, Request $request): View
    {
        $this->authorize('view', $listeningTest);

        $sectionList = $this->builder->sectionsForBuilder($listeningTest);
        $selectedSectionId = $this->builderSectionId($request);
        $selectedGroupId = $this->builderGroupId($request);

        [$selectedSection, $selectedGroup] = $this->builder->resolveBuilderSelection(
            $listeningTest,
            $sectionList,
            $selectedSectionId,
            $selectedGroupId,
        );

        $instructionDefaults = [];
        foreach (ListeningQuestionType::cases() as $type) {
            $instructionDefaults[$type->value] = ListeningQuestionGroupDefaults::instruction(
                $type,
                (int) ($selectedSection?->section_number ?? 1),
            );
        }

        $registry = app(\App\Services\Listening\QuestionTypes\ListeningQuestionTypeRegistry::class);
        $schemas = array_map(
            fn ($type) => $this->buildSchema->execute($type),
            $registry->all(),
        );

        return view('admin.listening.question-builder.builder', $this->sharedViewData($listeningTest, [
            'sections' => $sectionList,
            'selectedSection' => $selectedSection,
            'selectedGroup' => $selectedGroup,
            'summary' => $this->builder->getTestBuilderSummary($listeningTest),
            'instructionDefaults' => $instructionDefaults,
            'requestedGroupId' => $selectedGroup?->id ?? $selectedGroupId,
            'activePanel' => $selectedGroup ? 'group' : ($selectedSection ? 'section' : 'none'),
            'questionTypeSchemas' => $schemas,
            'enabledQuestionTypes' => $registry->all(),
            'audios' => ListeningAudio::query()->orderBy('original_name')->get(['id', 'original_name']),
            'availableRanges' => $selectedSection
                ? app(\App\Services\Listening\ListeningQuestionGroupService::class)->getAvailableQuestionRanges($selectedSection)
                : [],
        ], $selectedSection));
    }

    public function sectionBuilder(ListeningTest $listeningTest, ListeningSection $section): RedirectResponse
    {
        $this->authorize('view', $section);

        if ((int) $section->listening_test_id !== (int) $listeningTest->id) {
            return redirect()
                ->route('admin.listening.tests.builder.index', $listeningTest)
                ->with('error', 'Section does not belong to this test.');
        }

        return redirect()->route('admin.listening.tests.builder.index', [
            'listeningTest' => $listeningTest,
            'section' => $section->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sharedViewData(ListeningTest $listeningTest, array $data = [], ?ListeningSection $section = null): array
    {
        return array_merge([
            'listeningTest' => $listeningTest,
            'section' => $section,
            'routePrefix' => 'admin.listening.tests',
            'builderRoutePrefix' => 'admin.listening.tests.builder',
            'sectionsRoutePrefix' => 'admin.listening.tests.sections',
            'groupsRoutePrefix' => 'admin.listening.tests.sections.groups',
            'questionsRoutePrefix' => 'admin.listening.tests.sections.groups.questions',
            'questionTypes' => ListeningQuestionType::cases(),
            'layoutTypes' => ListeningLayoutType::cases(),
        ], $data);
    }

    private function builderSectionId(Request $request): int
    {
        foreach (['section', 'section_id'] as $key) {
            $value = $request->query($key);

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        foreach ($request->query() as $key => $value) {
            if (! is_numeric($value)) {
                continue;
            }

            $normalizedKey = strtolower((string) $key);

            if (
                $normalizedKey === 'section'
                || $normalizedKey === 'section_id'
                || str_ends_with($normalizedKey, 'section')
            ) {
                return (int) $value;
            }
        }

        return 0;
    }

    private function builderGroupId(Request $request): int
    {
        foreach (['question_group', 'group'] as $key) {
            $value = $request->query($key);

            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        foreach ($request->query() as $key => $value) {
            if (! is_numeric($value)) {
                continue;
            }

            $normalizedKey = strtolower((string) $key);

            if (
                $normalizedKey === 'question_group'
                || $normalizedKey === 'group'
                || str_ends_with($normalizedKey, 'question_group')
            ) {
                return (int) $value;
            }
        }

        return 0;
    }
}
