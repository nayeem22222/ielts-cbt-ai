<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Crud\CrudQuery;
use App\Enums\Course\ResourceType;
use App\Http\Controllers\Admin\Concerns\HandlesCrudOperations;
use App\Http\Controllers\Admin\Concerns\ManagesCourseCrud;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLessonResourceRequest;
use App\Http\Requests\Admin\UpdateLessonResourceRequest;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonResource;
use App\Services\Admin\LessonResourceCrudService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class LessonResourceController extends Controller
{
    use HandlesCrudOperations;
    use ManagesCourseCrud;

    public function __construct(private readonly LessonResourceCrudService $resources)
    {
    }

    protected function crudService(): LessonResourceCrudService
    {
        return $this->resources;
    }

    protected function crudModelClass(): string
    {
        return LessonResource::class;
    }

    protected function crudRoutePrefix(): string
    {
        return 'admin.lesson-resources';
    }

    protected function entityLabel(): string
    {
        return 'Resource';
    }

    protected function viewsNamespace(): string
    {
        return 'pages.admin.lesson-resources';
    }

    protected function crudTrashView(): string
    {
        return $this->viewsNamespace().'.trash';
    }

    protected function indexViewData(CrudQuery $crudQuery, mixed $records): array
    {
        return [
            'resourceTypes' => ResourceType::cases(),
            'courses' => Course::query()->orderBy('title')->get(),
            'lessons' => Lesson::query()->orderBy('title')->get(),
        ];
    }

    public function create(): View
    {
        $this->authorize('create', LessonResource::class);

        return view($this->viewsNamespace().'.create', [
            'resourceTypes' => ResourceType::cases(),
            'courses' => Course::query()->orderBy('title')->get(),
            'lessons' => Lesson::query()->orderBy('title')->get(),
        ]);
    }

    public function store(StoreLessonResourceRequest $request): RedirectResponse
    {
        $this->resources->create($request->validated());

        return redirect()->route($this->crudRoutePrefix().'.index')->with('status', 'Resource created successfully.');
    }

    public function edit(LessonResource $lessonResource): View
    {
        $this->authorize('update', $lessonResource);

        return view($this->viewsNamespace().'.edit', [
            'resource' => $lessonResource,
            'resourceTypes' => ResourceType::cases(),
            'courses' => Course::query()->orderBy('title')->get(),
            'lessons' => Lesson::query()->orderBy('title')->get(),
        ]);
    }

    public function update(UpdateLessonResourceRequest $request, LessonResource $lessonResource): RedirectResponse
    {
        $this->resources->update($lessonResource, $request->validated());

        return redirect()->route($this->crudRoutePrefix().'.index')->with('status', 'Resource updated successfully.');
    }

    public function destroy(LessonResource $lessonResource): RedirectResponse
    {
        $this->authorize('delete', $lessonResource);
        $this->resources->delete($lessonResource);

        return redirect()->route($this->crudRoutePrefix().'.index')->with('status', 'Resource deleted successfully.');
    }
}
