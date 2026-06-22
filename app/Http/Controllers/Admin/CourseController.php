<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Crud\CrudQuery;
use App\Enums\Course\CourseLevel;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Http\Controllers\Admin\Concerns\HandlesCrudOperations;
use App\Http\Controllers\Admin\Concerns\ManagesCourseCrud;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Services\Admin\CourseCrudService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CourseController extends Controller
{
    use HandlesCrudOperations;
    use ManagesCourseCrud;

    public function __construct(private readonly CourseCrudService $courses)
    {
    }

    protected function crudService(): CourseCrudService
    {
        return $this->courses;
    }

    protected function crudModelClass(): string
    {
        return Course::class;
    }

    protected function crudRoutePrefix(): string
    {
        return 'admin.courses';
    }

    protected function entityLabel(): string
    {
        return 'Course';
    }

    protected function viewsNamespace(): string
    {
        return 'pages.admin.courses';
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
            'levels' => CourseLevel::cases(),
            'categories' => CourseCategory::query()->orderBy('name')->get(),
        ];
    }

    public function create(): View
    {
        $this->authorize('create', Course::class);

        return view($this->viewsNamespace().'.create', [
            'statuses' => PublishStatus::cases(),
            'examTypes' => ExamType::cases(),
            'levels' => CourseLevel::cases(),
            'categories' => CourseCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $this->courses->create(array_merge($request->validated(), [
            'created_by' => $request->user()?->id,
        ]));

        return redirect()->route($this->crudRoutePrefix().'.index')->with('status', 'Course created successfully.');
    }

    public function edit(Course $course): View
    {
        $this->authorize('update', $course);

        return view($this->viewsNamespace().'.edit', [
            'course' => $course,
            'statuses' => PublishStatus::cases(),
            'examTypes' => ExamType::cases(),
            'levels' => CourseLevel::cases(),
            'categories' => CourseCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCourseRequest $request, Course $course): RedirectResponse
    {
        $this->courses->update($course, $request->validated());

        return redirect()->route($this->crudRoutePrefix().'.index')->with('status', 'Course updated successfully.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $this->authorize('delete', $course);
        $this->courses->delete($course);

        return redirect()->route($this->crudRoutePrefix().'.index')->with('status', 'Course deleted successfully.');
    }
}
