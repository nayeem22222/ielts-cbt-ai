<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Crud\CrudQuery;
use App\Enums\Commerce\BillingInterval;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Commerce\PackageDiscountType;
use App\Enums\Commerce\PackageStatus;
use App\Http\Controllers\Admin\Concerns\HandlesCrudOperations;
use App\Http\Controllers\Admin\Concerns\ManagesCourseCrud;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePackageRequest;
use App\Http\Requests\Admin\UpdatePackageRequest;
use App\Models\Course;
use App\Models\Package;
use App\Services\Admin\PackageCrudService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PackageController extends Controller
{
    use HandlesCrudOperations;
    use ManagesCourseCrud;

    public function __construct(private readonly PackageCrudService $packages)
    {
    }

    protected function crudService(): PackageCrudService
    {
        return $this->packages;
    }

    protected function crudModelClass(): string
    {
        return Package::class;
    }

    protected function crudRoutePrefix(): string
    {
        return 'admin.packages';
    }

    protected function entityLabel(): string
    {
        return 'Packages';
    }

    protected function viewsNamespace(): string
    {
        return 'pages.admin.packages';
    }

    protected function crudTrashView(): string
    {
        return $this->viewsNamespace().'.trash';
    }

    protected function indexViewData(CrudQuery $crudQuery, mixed $records): array
    {
        return [
            'statuses' => PackageStatus::cases(),
            'intervals' => BillingInterval::cases(),
        ];
    }

    public function create(): View
    {
        $this->authorize('create', Package::class);

        return view($this->viewsNamespace().'.create', $this->formData());
    }

    public function store(StorePackageRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $payload = $this->packages->normalizeInput($validated);
        $this->packages->create(array_merge($payload, [
            'course_ids' => $validated['course_ids'] ?? [],
        ]));

        return redirect()->route($this->crudRoutePrefix().'.index')->with('status', 'Package created successfully.');
    }

    public function edit(Package $package): View
    {
        $this->authorize('update', $package);
        $package->load('courses');

        return view($this->viewsNamespace().'.edit', array_merge($this->formData(), [
            'package' => $package,
            'selectedCourses' => $package->courses->pluck('id')->all(),
        ]));
    }

    public function update(UpdatePackageRequest $request, Package $package): RedirectResponse
    {
        $validated = $request->validated();
        $payload = $this->packages->normalizeInput($validated);
        $this->packages->update($package, array_merge($payload, [
            'course_ids' => $validated['course_ids'] ?? [],
        ]));

        return redirect()->route($this->crudRoutePrefix().'.index')->with('status', 'Package updated successfully.');
    }

    public function destroy(Package $package): RedirectResponse
    {
        $this->authorize('delete', $package);
        $this->packages->delete($package);

        return redirect()->route($this->crudRoutePrefix().'.index')->with('status', 'Package deleted successfully.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'modules' => IeltsModule::cases(),
            'statuses' => PackageStatus::cases(),
            'intervals' => BillingInterval::cases(),
            'discountTypes' => PackageDiscountType::cases(),
            'courses' => Course::query()->orderBy('title')->get(),
        ];
    }
}
