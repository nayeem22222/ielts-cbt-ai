<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Course\CategoryStatus;
use App\Http\Controllers\Admin\Concerns\HandlesCrudOperations;
use App\Http\Controllers\Admin\Concerns\ManagesCourseCrud;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseCategoryRequest;
use App\Http\Requests\Admin\UpdateCourseCategoryRequest;
use App\Models\CourseCategory;
use App\Services\Admin\CourseCategoryCrudService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CourseCategoryController extends Controller
{
    use HandlesCrudOperations;
    use ManagesCourseCrud;

    public function __construct(private readonly CourseCategoryCrudService $categories)
    {
    }

    protected function crudService(): CourseCategoryCrudService
    {
        return $this->categories;
    }

    protected function crudModelClass(): string
    {
        return CourseCategory::class;
    }

    protected function crudRoutePrefix(): string
    {
        return 'admin.course-categories';
    }

    protected function entityLabel(): string
    {
        return 'Categories';
    }

    protected function viewsNamespace(): string
    {
        return 'pages.admin.course-categories';
    }

    protected function crudTrashView(): string
    {
        return $this->viewsNamespace().'.trash';
    }

    protected function indexViewData(\App\Crud\CrudQuery $crudQuery, mixed $records): array
    {
        return [
            'statuses' => CategoryStatus::cases(),
            'parents' => CourseCategory::query()->orderBy('name')->get(),
        ];
    }

    public function create(): View
    {
        $this->authorize('create', CourseCategory::class);

        return view($this->viewsNamespace().'.create', [
            'statuses' => CategoryStatus::cases(),
            'parents' => CourseCategory::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCourseCategoryRequest $request): RedirectResponse
    {
        $this->categories->create($request->validated());

        return redirect()->route($this->crudRoutePrefix().'.index')->with('status', 'Category created successfully.');
    }

    public function edit(CourseCategory $courseCategory): View
    {
        $this->authorize('update', $courseCategory);

        return view($this->viewsNamespace().'.edit', [
            'category' => $courseCategory,
            'statuses' => CategoryStatus::cases(),
            'parents' => CourseCategory::query()->where('id', '!=', $courseCategory->id)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCourseCategoryRequest $request, CourseCategory $courseCategory): RedirectResponse
    {
        $this->categories->update($courseCategory, $request->validated());

        return redirect()->route($this->crudRoutePrefix().'.index')->with('status', 'Category updated successfully.');
    }

    public function destroy(CourseCategory $courseCategory): RedirectResponse
    {
        $this->authorize('delete', $courseCategory);
        $this->categories->delete($courseCategory);

        return redirect()->route($this->crudRoutePrefix().'.index')->with('status', 'Category deleted successfully.');
    }
}
