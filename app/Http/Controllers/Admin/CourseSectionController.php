<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Crud\CrudQuery;
use App\Enums\Course\PublishStatus;
use App\Http\Controllers\Admin\Concerns\HandlesCrudOperations;
use App\Http\Controllers\Admin\Concerns\ManagesCourseCrud;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseSectionRequest;
use App\Http\Requests\Admin\UpdateCourseSectionRequest;
use App\Models\Course;
use App\Models\CourseSection;
use App\Services\Admin\CourseSectionCrudService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CourseSectionController extends Controller
{
    use HandlesCrudOperations;
    use ManagesCourseCrud;

    public function __construct(private readonly CourseSectionCrudService $sections)
    {
    }

    protected function crudService(): CourseSectionCrudService
    {
        return $this->sections;
    }

    protected function crudModelClass(): string
    {
        return CourseSection::class;
    }

    protected function crudRoutePrefix(): string
    {
        return 'admin.course-sections';
    }

    protected function entityLabel(): string
    {
        return 'Section';
    }

    protected function viewsNamespace(): string
    {
        return 'pages.admin.course-sections';
    }

    protected function crudTrashView(): string
    {
        return $this->viewsNamespace().'.trash';
    }

    protected function indexViewData(CrudQuery $crudQuery, mixed $records): array
    {
        return [
            'statuses' => PublishStatus::cases(),
            'courses' => Course::query()->orderBy('title')->get(),
        ];
    }

    public function create(): View
    {
        $this->authorize('create', CourseSection::class);

        return view($this->viewsNamespace().'.create', [
            'statuses' => PublishStatus::cases(),
            'courses' => Course::query()->orderBy('title')->get(),
        ]);
    }

    public function store(StoreCourseSectionRequest $request): RedirectResponse
    {
        $this->sections->create($request->validated());

        return redirect()->route($this->crudRoutePrefix().'.index')->with('status', 'Section created successfully.');
    }

    public function edit(CourseSection $courseSection): View
    {
        $this->authorize('update', $courseSection);

        return view($this->viewsNamespace().'.edit', [
            'section' => $courseSection,
            'statuses' => PublishStatus::cases(),
            'courses' => Course::query()->orderBy('title')->get(),
        ]);
    }

    public function update(UpdateCourseSectionRequest $request, CourseSection $courseSection): RedirectResponse
    {
        $this->sections->update($courseSection, $request->validated());

        return redirect()->route($this->crudRoutePrefix().'.index')->with('status', 'Section updated successfully.');
    }

    public function destroy(CourseSection $courseSection): RedirectResponse
    {
        $this->authorize('delete', $courseSection);
        $this->sections->delete($courseSection);

        return redirect()->route($this->crudRoutePrefix().'.index')->with('status', 'Section deleted successfully.');
    }
}
