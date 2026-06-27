<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Listening;

use App\Enums\Listening\ListeningConstants;
use App\Enums\Listening\ListeningDifficultyLevel;
use App\Enums\Listening\ListeningTestStatus;
use App\Enums\Listening\ListeningTestType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Listening\StoreListeningTestRequest;
use App\Http\Requests\Admin\Listening\UpdateListeningTestRequest;
use App\Http\Requests\Admin\Listening\UpdateListeningTestSettingsRequest;
use App\Models\Listening\ListeningTest;
use App\Services\Listening\ListeningTestService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ListeningTestController extends Controller
{
    public function __construct(
        private readonly ListeningTestService $tests,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ListeningTest::class);

        $filters = $this->filtersFromRequest($request);
        $records = $this->tests->paginateForAdmin($filters);

        return view('admin.listening.tests.index', $this->sharedViewData([
            'records' => $records,
            'filters' => $filters,
        ]));
    }

    public function create(): View
    {
        $this->authorize('create', ListeningTest::class);

        return view('admin.listening.tests.create', $this->sharedViewData([
            'listeningTest' => new ListeningTest([
                'test_type' => ListeningTestType::Academic,
                'difficulty_level' => ListeningDifficultyLevel::Official,
                'duration_minutes' => ListeningConstants::DEFAULT_DURATION_MINUTES,
                'transfer_time_minutes' => ListeningConstants::DEFAULT_TRANSFER_TIME_MINUTES,
                'status' => ListeningTestStatus::Draft,
            ]),
        ]));
    }

    public function store(StoreListeningTestRequest $request): RedirectResponse
    {
        $test = $this->tests->create(array_merge(
            $request->validated(),
            [
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ],
        ));

        return redirect()
            ->route('admin.listening.tests.show', $test)
            ->with('status', 'Listening test created successfully.');
    }

    public function show(ListeningTest $listeningTest): View
    {
        $this->authorize('view', $listeningTest);

        $this->tests->ensureSettings($listeningTest);
        $listeningTest->load(['setting', 'createdBy', 'updatedBy']);

        return view('admin.listening.tests.show', $this->sharedViewData([
            'listeningTest' => $listeningTest,
            'readiness' => $this->tests->getReadinessSummary($listeningTest),
        ]));
    }

    public function edit(ListeningTest $listeningTest): View
    {
        $this->authorize('update', $listeningTest);

        $this->tests->ensureSettings($listeningTest);
        $listeningTest->load('setting');

        return view('admin.listening.tests.edit', $this->sharedViewData([
            'listeningTest' => $listeningTest,
            'readiness' => $this->tests->getReadinessSummary($listeningTest),
        ]));
    }

    public function update(UpdateListeningTestRequest $request, ListeningTest $listeningTest): RedirectResponse
    {
        $this->tests->update($listeningTest, array_merge(
            $request->validated(),
            ['updated_by' => $request->user()?->id],
        ));

        return redirect()
            ->route('admin.listening.tests.show', $listeningTest)
            ->with('status', 'Listening test updated successfully.');
    }

    public function destroy(ListeningTest $listeningTest): RedirectResponse
    {
        $this->authorize('delete', $listeningTest);

        if (! $this->tests->delete($listeningTest)) {
            return back()->with('error', 'Listening test could not be deleted.');
        }

        return redirect()
            ->route('admin.listening.tests.index')
            ->with('status', 'Listening test deleted successfully.');
    }

    public function publish(ListeningTest $listeningTest): RedirectResponse
    {
        $this->authorize('publish', $listeningTest);

        $result = $this->tests->publish($listeningTest);

        if (! $result['success']) {
            return back()
                ->with('error', 'Listening test cannot be published.')
                ->with('publish_errors', $result['errors']);
        }

        return back()->with('status', 'Listening test published successfully.');
    }

    public function unpublish(ListeningTest $listeningTest): RedirectResponse
    {
        $this->authorize('publish', $listeningTest);

        $this->tests->unpublish($listeningTest);

        return back()->with('status', 'Listening test unpublished successfully.');
    }

    public function archive(ListeningTest $listeningTest): RedirectResponse
    {
        $this->authorize('archive', $listeningTest);

        $this->tests->archive($listeningTest);

        return back()->with('status', 'Listening test archived successfully.');
    }

    public function restore(int $id): RedirectResponse
    {
        $test = ListeningTest::query()->onlyTrashed()->find($id);

        if ($test === null) {
            return back()->with('error', 'Listening test not found.');
        }

        $this->authorize('restore', $test);

        $this->tests->restore($id);

        return redirect()
            ->route('admin.listening.tests.index')
            ->with('status', 'Listening test restored successfully.');
    }

    public function duplicate(ListeningTest $listeningTest): RedirectResponse
    {
        $this->authorize('duplicate', $listeningTest);

        $copy = $this->tests->duplicate($listeningTest, (int) auth()->id());

        return redirect()
            ->route('admin.listening.tests.show', $copy)
            ->with('status', 'Listening test duplicated successfully.');
    }

    public function updateSettings(UpdateListeningTestSettingsRequest $request, ListeningTest $listeningTest): RedirectResponse
    {
        $this->tests->updateSettings($listeningTest, $request->validated());

        return back()->with('status', 'Listening test settings updated successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function filtersFromRequest(Request $request): array
    {
        return [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'test_type' => $request->string('test_type')->toString(),
            'difficulty_level' => $request->string('difficulty_level')->toString(),
            'is_active' => $request->input('is_active'),
            'is_featured' => $request->input('is_featured'),
            'created_by' => $request->input('created_by'),
            'date_from' => $request->string('date_from')->toString(),
            'date_to' => $request->string('date_to')->toString(),
            'trashed' => $request->string('trashed')->toString(),
            'sort_by' => $request->string('sort_by')->toString(),
            'sort_direction' => $request->string('sort_direction')->toString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sharedViewData(array $data = []): array
    {
        return array_merge([
            'statuses' => ListeningTestStatus::cases(),
            'testTypes' => ListeningTestType::cases(),
            'difficultyLevels' => ListeningDifficultyLevel::cases(),
            'routePrefix' => 'admin.listening.tests',
            'entityLabel' => 'Listening Test',
            'totalSections' => ListeningConstants::TOTAL_SECTIONS,
            'totalQuestions' => ListeningConstants::TOTAL_QUESTIONS,
        ], $data);
    }
}
