<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Crud\CrudQuery;
use App\Enums\Course\LessonContentType;
use App\Enums\Course\PublishStatus;
use App\Http\Controllers\Admin\Concerns\HandlesCrudOperations;
use App\Http\Controllers\Admin\Concerns\ManagesCourseCrud;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLessonRequest;
use App\Http\Requests\Admin\UpdateLessonRequest;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Services\Admin\LessonCrudService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class LessonController extends Controller
{
    use HandlesCrudOperations;
    use ManagesCourseCrud;

    public function __construct(private readonly LessonCrudService $lessons)
    {
    }

    protected function crudService(): LessonCrudService
    {
        return $this->lessons;
    }

    protected function crudModelClass(): string
    {
        return Lesson::class;
    }

    protected function crudRoutePrefix(): string
    {
        return 'admin.lessons';
    }

    protected function entityLabel(): string
    {
        return 'Lesson';
    }

    protected function viewsNamespace(): string
    {
        return 'pages.admin.lessons';
    }

    protected function crudTrashView(): string
    {
        return $this->viewsNamespace().'.trash';
    }

    protected function indexViewData(CrudQuery $crudQuery, mixed $records): array
    {
        return [
            'statuses' => PublishStatus::cases(),
            'contentTypes' => LessonContentType::cases(),
            'sections' => CourseSection::query()->with('course')->orderBy('title')->get(),
        ];
    }

    public function create(): View
    {
        $this->authorize('create', Lesson::class);

        return view($this->viewsNamespace().'.create', [
            'statuses' => PublishStatus::cases(),
            'contentTypes' => LessonContentType::cases(),
            'sections' => CourseSection::query()->with('course')->orderBy('title')->get(),
        ]);
    }

    public function store(StoreLessonRequest $request): RedirectResponse
    {
        $this->lessons->create(array_merge($request->validated(), [
            'created_by' => $request->user()?->id,
        ]));

        return redirect()->route($this->crudRoutePrefix().'.index')->with('status', 'Lesson created successfully.');
    }

    public function edit(Lesson $lesson): View
    {
        $this->authorize('update', $lesson);

        return view($this->viewsNamespace().'.edit', [
            'lesson' => $lesson,
            'statuses' => PublishStatus::cases(),
            'contentTypes' => LessonContentType::cases(),
            'sections' => CourseSection::query()->with('course')->orderBy('title')->get(),
        ]);
    }

    public function update(UpdateLessonRequest $request, Lesson $lesson): RedirectResponse
    {
        $this->lessons->update($lesson, $request->validated());

        return redirect()->route($this->crudRoutePrefix().'.index')->with('status', 'Lesson updated successfully.');
    }

    public function destroy(Lesson $lesson): RedirectResponse
    {
        $this->authorize('delete', $lesson);
        $this->lessons->delete($lesson);

        return redirect()->route($this->crudRoutePrefix().'.index')->with('status', 'Lesson deleted successfully.');
    }
}
