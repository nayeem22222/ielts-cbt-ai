<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Crud\CrudQuery;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\TestType;
use App\Http\Controllers\Admin\Concerns\HandlesCrudOperations;
use App\Http\Controllers\Admin\Concerns\ManagesCourseCrud;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReadingTestRequest;
use App\Http\Requests\Admin\UpdateReadingTestRequest;
use App\Models\ExamTest;
use App\Services\Admin\Exam\ReadingTestCrudService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ReadingTestController extends Controller
{
    use HandlesCrudOperations;
    use ManagesCourseCrud;

    public function __construct(private readonly ReadingTestCrudService $tests)
    {
    }

    protected function crudService(): ReadingTestCrudService
    {
        return $this->tests;
    }

    protected function crudModelClass(): string
    {
        return ExamTest::class;
    }

    protected function crudRoutePrefix(): string
    {
        return 'admin.reading-tests';
    }

    protected function entityLabel(): string
    {
        return 'Reading Test';
    }

    protected function viewsNamespace(): string
    {
        return 'pages.admin.reading-tests';
    }

    protected function crudTrashView(): string
    {
        return $this->viewsNamespace().'.trash';
    }

    protected function indexViewData(CrudQuery $crudQuery, mixed $records): array
    {
        return [
            'statuses' => PublishStatus::cases(),
            'examTypes' => ExamType::cases(),
        ];
    }

    public function create(): View
    {
        $this->authorize('create', ExamTest::class);

        return view($this->viewsNamespace().'.create', $this->formData());
    }

    public function store(StoreReadingTestRequest $request): RedirectResponse
    {
        $test = $this->tests->create(array_merge(
            $this->tests->normalizeInput($request->validated()),
            ['created_by' => $request->user()?->id]
        ));

        return redirect()
            ->route('admin.reading-tests.builder', $test)
            ->with('status', 'Reading test created. Add passages and questions in the builder.');
    }

    public function edit(ExamTest $readingTest): View
    {
        $this->ensureReadingTest($readingTest);
        $this->authorize('update', $readingTest);

        return view($this->viewsNamespace().'.edit', array_merge($this->formData(), [
            'readingTest' => $readingTest,
        ]));
    }

    public function update(UpdateReadingTestRequest $request, ExamTest $readingTest): RedirectResponse
    {
        $this->ensureReadingTest($readingTest);
        $this->tests->update($readingTest, $this->tests->normalizeInput($request->validated()));

        return redirect()
            ->route($this->crudRoutePrefix().'.index')
            ->with('status', 'Reading test updated successfully.');
    }

    public function destroy(ExamTest $readingTest): RedirectResponse
    {
        $this->ensureReadingTest($readingTest);
        $this->authorize('delete', $readingTest);
        $this->tests->delete($readingTest);

        return redirect()
            ->route($this->crudRoutePrefix().'.index')
            ->with('status', 'Reading test deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'statuses' => PublishStatus::cases(),
            'examTypes' => ExamType::cases(),
        ];
    }

    private function ensureReadingTest(ExamTest $test): void
    {
        if ($test->type !== TestType::ReadingTest) {
            abort(404);
        }
    }
}
