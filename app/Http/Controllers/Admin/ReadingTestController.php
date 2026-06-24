<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Crud\CrudQuery;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\PassageStatus;
use App\Support\Reading\ReadingQuestionGroupDefaults;
use App\Http\Controllers\Controller;
use App\Http\Requests\Crud\BulkActionRequest;
use App\Http\Requests\Crud\CrudIndexRequest;
use App\Http\Requests\Admin\StoreReadingTestRequest;
use App\Http\Requests\Admin\UpdateReadingTestRequest;
use App\Models\ReadingTest;
use App\Services\Admin\Exam\ReadingPassageBuilderService;
use App\Services\Admin\Exam\ReadingQuestionGroupBuilderService;
use App\Services\Admin\Exam\ReadingTestCrudService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReadingTestController extends Controller
{
    public function __construct(
        private readonly ReadingTestCrudService $tests,
        private readonly ReadingPassageBuilderService $passages,
        private readonly ReadingQuestionGroupBuilderService $questionGroups,
    ) {
    }

    public function index(CrudIndexRequest $request): View
    {
        $this->authorize('viewAny', ReadingTest::class);

        $crudQuery = CrudQuery::fromRequest($request, $this->tests->definition());
        $records = $this->tests->paginate($crudQuery);

        return view($this->viewsNamespace().'.index', $this->indexData($crudQuery, $records));
    }

    public function trash(CrudIndexRequest $request): View
    {
        $this->authorize('viewAny', ReadingTest::class);

        $request->merge(['trashed' => true]);
        $crudQuery = CrudQuery::fromRequest($request, $this->tests->definition());
        $records = $this->tests->paginate($crudQuery);

        return view($this->viewsNamespace().'.trash', $this->indexData($crudQuery, $records));
    }

    public function export(CrudIndexRequest $request): StreamedResponse
    {
        $this->authorize('viewAny', ReadingTest::class);

        $crudQuery = CrudQuery::fromRequest($request, $this->tests->definition());

        return $this->tests->exportCsv($crudQuery);
    }

    public function create(): View
    {
        $this->authorize('create', ReadingTest::class);

        return view($this->viewsNamespace().'.create', $this->formData([
            'readingTest' => new ReadingTest([
                'exam_type' => ExamType::Academic,
                'duration_minutes' => 60,
                'status' => PublishStatus::Draft,
            ]),
        ]));
    }

    public function store(StoreReadingTestRequest $request): RedirectResponse
    {
        /** @var ReadingTest $test */
        $test = $this->tests->create($this->tests->normalizeInput(array_merge(
            $request->validated(),
            [
                'created_by' => $request->user()?->id,
                'updated_by' => $request->user()?->id,
            ],
        )));

        return redirect()
            ->route('admin.reading-tests.edit', $test)
            ->with('status', 'Reading test created successfully.');
    }

    public function edit(ReadingTest $readingTest): View
    {
        $this->authorize('update', $readingTest);

        return view($this->viewsNamespace().'.edit', $this->formData([
            'readingTest' => $readingTest,
        ]));
    }

    public function update(UpdateReadingTestRequest $request, ReadingTest $readingTest): RedirectResponse
    {
        $this->tests->update($readingTest, $this->tests->normalizeInput(array_merge(
            $request->validated(),
            ['updated_by' => $request->user()?->id],
        )));

        return redirect()
            ->route('admin.reading-tests.index')
            ->with('status', 'Reading test updated successfully.');
    }

    public function destroy(ReadingTest $readingTest): RedirectResponse
    {
        $this->authorize('delete', $readingTest);

        DB::transaction(fn () => $this->tests->delete($readingTest));

        return redirect()
            ->route('admin.reading-tests.index')
            ->with('status', 'Reading test moved to trash.');
    }

    public function publish(ReadingTest $readingTest): RedirectResponse
    {
        $this->authorize('update', $readingTest);
        $this->tests->publish($readingTest);

        return back()->with('status', 'Reading test published.');
    }

    public function unpublish(ReadingTest $readingTest): RedirectResponse
    {
        $this->authorize('update', $readingTest);
        $this->tests->unpublish($readingTest);

        return back()->with('status', 'Reading test unpublished.');
    }

    public function duplicate(ReadingTest $readingTest): RedirectResponse
    {
        $this->authorize('create', ReadingTest::class);
        $copy = $this->tests->duplicate($readingTest, (int) auth()->id());

        return redirect()
            ->route('admin.reading-tests.edit', $copy)
            ->with('status', 'Reading test duplicated as draft.');
    }

    public function restore(int|string $id): RedirectResponse
    {
        /** @var ReadingTest $test */
        $test = $this->tests->findOrFail($id, true);
        $this->authorize('delete', $test);

        DB::transaction(fn () => $this->tests->restore($test));

        return back()->with('status', 'Reading test restored successfully.');
    }

    public function forceDestroy(int|string $id): RedirectResponse
    {
        /** @var ReadingTest $test */
        $test = $this->tests->findOrFail($id, true);
        $this->authorize('delete', $test);

        $this->tests->forceDelete($test);

        return back()->with('status', 'Reading test permanently deleted.');
    }

    public function bulk(BulkActionRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', ReadingTest::class);

        $ids = array_map('intval', $request->input('ids', []));
        $action = $request->string('action')->toString();

        $count = match ($action) {
            'delete' => DB::transaction(fn () => $this->tests->bulkDelete($ids)),
            'restore' => DB::transaction(fn () => $this->tests->bulkRestore($ids)),
            'force_delete' => DB::transaction(fn () => $this->bulkForceDelete($ids)),
            'publish' => $this->tests->bulkPublish($ids),
            'unpublish' => $this->tests->bulkUnpublish($ids),
            'archive' => $this->tests->bulkArchive($ids),
            default => 0,
        };

        return back()->with('status', ucfirst(str_replace('_', ' ', $action))." applied to {$count} reading tests.");
    }

    public function builder(ReadingTest $readingTest, Request $request): View
    {
        $this->authorize('update', $readingTest);

        $passageList = $this->questionGroups->passagesForBuilder($readingTest);
        $selectedPassageId = $this->builderPassageId($request);
        $selectedGroupId = $this->builderGroupId($request);

        [$selectedPassage, $selectedGroup] = $this->questionGroups->resolveBuilderSelection(
            $readingTest,
            $passageList,
            $selectedPassageId,
            $selectedGroupId,
        );

        $instructionDefaults = [];
        foreach (OfficialReadingQuestionType::cases() as $type) {
            $instructionDefaults[$type->value] = ReadingQuestionGroupDefaults::instruction(
                $type,
                (int) ($selectedPassage?->part_number ?? 1),
            );
        }

        return view($this->viewsNamespace().'.builder', [
            'test' => $readingTest,
            'passages' => $passageList,
            'selectedPassage' => $selectedPassage,
            'selectedGroup' => $selectedGroup,
            'passageStatuses' => PassageStatus::cases(),
            'questionTypes' => OfficialReadingQuestionType::cases(),
            'instructionDefaults' => $instructionDefaults,
            'requestedGroupId' => $selectedGroup?->id ?? $selectedGroupId,
            'activePanel' => $selectedGroup ? 'group' : ($selectedPassage ? 'passage' : 'none'),
        ]);
    }

    public function preview(ReadingTest $readingTest): View
    {
        $this->authorize('view', $readingTest);

        return view($this->viewsNamespace().'.preview', [
            'test' => $readingTest->load(['passages.groups.questions.options', 'passages.groups.questions.correctAnswers']),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(array $data = []): array
    {
        return array_merge([
            'statuses' => PublishStatus::cases(),
            'examTypes' => ExamType::cases(),
        ], $data);
    }

    /**
     * @return array<string, mixed>
     */
    private function indexData(CrudQuery $crudQuery, mixed $records): array
    {
        return array_merge($this->formData(), [
            'records' => $records,
            'routePrefix' => 'admin.reading-tests',
            'entityLabel' => 'Reading Test',
            'filters' => array_merge(
                ['search' => $crudQuery->search ?? ''],
                $crudQuery->filters,
            ),
            'sort' => $crudQuery->sort,
            'direction' => $crudQuery->direction,
            'definition' => $this->tests->definition(),
        ]);
    }

    /**
     * @param  list<int>  $ids
     */
    private function bulkForceDelete(array $ids): int
    {
        $count = 0;

        foreach ($ids as $id) {
            /** @var ReadingTest $test */
            $test = $this->tests->findOrFail($id, true);
            $this->authorize('delete', $test);
            $count += (int) $this->tests->forceDelete($test);
        }

        return $count;
    }

    private function viewsNamespace(): string
    {
        return 'pages.admin.reading-tests';
    }

    private function builderPassageId(Request $request): int
    {
        $passage = $request->query('passage');

        return is_numeric($passage) ? (int) $passage : 0;
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
