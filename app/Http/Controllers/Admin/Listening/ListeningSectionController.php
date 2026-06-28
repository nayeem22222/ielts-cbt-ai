<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Listening;

use App\Enums\Listening\ListeningSectionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Listening\ReorderListeningSectionRequest;
use App\Http\Requests\Admin\Listening\StoreListeningSectionRequest;
use App\Http\Requests\Admin\Listening\UpdateListeningSectionRequest;
use App\Models\Listening\ListeningAudio;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Repositories\Listening\ListeningSectionRepository;
use App\Services\Listening\ListeningSectionService;
use App\Services\Listening\ListeningTranscriptService;
use App\Support\Listening\ListeningSectionMap;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

class ListeningSectionController extends Controller
{
    public function __construct(
        private readonly ListeningSectionService $sections,
        private readonly ListeningSectionRepository $sectionRepository,
        private readonly ListeningTranscriptService $transcripts,
    ) {}

    public function index(ListeningTest $listeningTest): View
    {
        $this->authorize('viewAny', [ListeningSection::class, $listeningTest]);

        return view('admin.listening.sections.index', $this->sharedViewData($listeningTest, [
            'sections' => $this->sections->listForTest($listeningTest),
            'summary' => $this->sections->getTestSectionSummary($listeningTest),
        ]));
    }

    public function create(ListeningTest $listeningTest): View
    {
        $this->authorize('create', [ListeningSection::class, $listeningTest]);

        $existingSectionNumbers = $this->sectionRepository->existingSectionNumbers($listeningTest);
        $availableSectionNumbers = $this->sectionRepository->availableSectionNumbersForCreate($listeningTest);
        $firstAvailable = $availableSectionNumbers[0] ?? null;
        $defaults = $firstAvailable !== null ? ListeningSectionMap::forSectionNumber($firstAvailable) : null;

        return view('admin.listening.sections.create', $this->sharedViewData($listeningTest, [
            'section' => new ListeningSection([
                'is_active' => true,
                'section_number' => $firstAvailable,
                'title' => $firstAvailable !== null ? 'Section '.$firstAvailable : null,
                'section_type' => $defaults['default_type'] ?? null,
                'display_order' => $firstAvailable,
            ]),
            'audios' => $this->audioOptions(),
            'transcripts' => $this->transcripts->getAvailableForSection(null),
            'listeningTest' => $listeningTest,
            'availableSectionNumbers' => $availableSectionNumbers,
            'existingSectionNumbers' => $existingSectionNumbers,
        ]));
    }

    public function store(StoreListeningSectionRequest $request, ListeningTest $listeningTest): RedirectResponse
    {
        try {
            $section = $this->sections->create($listeningTest, $request->validated());
        } catch (ValidationException $exception) {
            return back()
                ->withInput()
                ->withErrors($exception->errors())
                ->with('error', 'Listening section could not be saved.');
        }

        return redirect()
            ->route('admin.listening.tests.sections.show', [$listeningTest, $section])
            ->with('status', 'Listening section created successfully.');
    }

    public function show(ListeningTest $listeningTest, ListeningSection $section): View|RedirectResponse
    {
        $this->authorize('view', $section);

        if ($redirect = $this->guardSectionBelongsToTest($listeningTest, $section)) {
            return $redirect;
        }

        $section->load(['audio', 'transcript'])->loadCount(['questionGroups', 'questions']);

        return view('admin.listening.sections.show', $this->sharedViewData($listeningTest, [
            'section' => $section,
            'readiness' => $this->sections->getSectionReadiness($section),
            'availableTranscripts' => $this->transcripts->getAvailableForSection($section->audio_id),
        ]));
    }

    public function edit(ListeningTest $listeningTest, ListeningSection $section): View|RedirectResponse
    {
        $this->authorize('update', $section);

        if ($redirect = $this->guardSectionBelongsToTest($listeningTest, $section)) {
            return $redirect;
        }

        $section->load(['audio', 'transcript']);

        return view('admin.listening.sections.edit', $this->sharedViewData($listeningTest, [
            'section' => $section,
            'readiness' => $this->sections->getSectionReadiness($section),
            'audios' => $this->audioOptions(),
            'transcripts' => $this->transcripts->getAvailableForSection($section->audio_id),
            'availableTranscripts' => $this->transcripts->getAvailableForSection($section->audio_id),
            'availableSectionNumbers' => $this->sectionRepository->availableSectionNumbersForEdit($listeningTest, $section),
        ]));
    }

    public function update(UpdateListeningSectionRequest $request, ListeningTest $listeningTest, ListeningSection $section): RedirectResponse
    {
        if ($redirect = $this->guardSectionBelongsToTest($listeningTest, $section)) {
            return $redirect;
        }

        try {
            $this->sections->update($listeningTest, $section, $request->validated());
        } catch (ValidationException $exception) {
            return back()
                ->withInput()
                ->withErrors($exception->errors())
                ->with('error', 'Listening section could not be saved.');
        }

        return redirect()
            ->route('admin.listening.tests.sections.show', [$listeningTest, $section])
            ->with('status', 'Listening section updated successfully.');
    }

    public function destroy(ListeningTest $listeningTest, ListeningSection $section): RedirectResponse
    {
        $this->authorize('delete', $section);

        if ($redirect = $this->guardSectionBelongsToTest($listeningTest, $section)) {
            return $redirect;
        }

        try {
            if (! $this->sections->delete($listeningTest, $section)) {
                return back()->with('error', 'Listening section could not be deleted.');
            }
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->with('error', 'Listening section could not be deleted.');
        }

        return redirect()
            ->route('admin.listening.tests.sections.index', $listeningTest)
            ->with('status', 'Listening section deleted successfully.');
    }

    public function restore(ListeningTest $listeningTest, int $sectionId): RedirectResponse
    {
        $trashed = $listeningTest->sections()->onlyTrashed()->find($sectionId);

        if ($trashed === null) {
            return back()->with('error', 'Listening section not found.');
        }

        $this->authorize('restore', $trashed);

        try {
            $this->sections->restore($listeningTest, $sectionId);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return redirect()
            ->route('admin.listening.tests.sections.index', $listeningTest)
            ->with('status', 'Listening section restored successfully.');
    }

    public function createDefaultSections(ListeningTest $listeningTest): RedirectResponse
    {
        $this->authorize('createDefault', [ListeningSection::class, $listeningTest]);

        try {
            $result = $this->sections->createDefaultSections($listeningTest);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        $message = 'Default listening sections created successfully.';

        if ($result['created'] === 0) {
            $message = 'All official sections already exist.';
        }

        return back()->with('status', $message);
    }

    public function reorder(ReorderListeningSectionRequest $request, ListeningTest $listeningTest): RedirectResponse
    {
        try {
            $this->sections->reorder($listeningTest, array_map('intval', $request->input('sections', [])));
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Listening sections reordered successfully.');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sharedViewData(ListeningTest $listeningTest, array $data = []): array
    {
        return array_merge([
            'listeningTest' => $listeningTest,
            'routePrefix' => 'admin.listening.tests',
            'sectionsRoutePrefix' => 'admin.listening.tests.sections',
            'groupsRoutePrefix' => 'admin.listening.tests.sections.groups',
            'sectionRangeMap' => ListeningSectionMap::sectionRangeMap(),
            'sectionTypes' => ListeningSectionType::cases(),
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

    /**
     * @return Collection<int, ListeningAudio>
     */
    private function audioOptions()
    {
        return ListeningAudio::query()
            ->orderBy('original_name')
            ->get(['id', 'original_name', 'duration_seconds', 'processing_status', 'validation_status']);
    }
}
