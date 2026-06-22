<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Concerns;

use App\Crud\Contracts\CrudServiceInterface;
use App\Crud\CrudQuery;
use App\Http\Requests\Crud\BulkActionRequest;
use App\Http\Requests\Crud\CrudIndexRequest;
use App\Http\Requests\Crud\ImportSpreadsheetRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

trait HandlesCrudOperations
{
    abstract protected function crudService(): CrudServiceInterface;

    /**
     * @return class-string<Model>
     */
    abstract protected function crudModelClass(): string;

    abstract protected function crudRoutePrefix(): string;

    protected function crudTrashView(): string
    {
        return 'pages.admin.crud.trash';
    }

    protected function crudImportView(): string
    {
        return 'pages.admin.crud.import';
    }

    public function trash(CrudIndexRequest $request): View
    {
        $this->authorize('viewAny', $this->crudModelClass());

        $request->merge(['trashed' => true]);
        $crudQuery = CrudQuery::fromRequest($request, $this->crudService()->definition());
        $records = $this->crudService()->paginate($crudQuery);

        return view($this->crudTrashView(), array_merge(
            $this->crudIndexData($crudQuery, $records),
            [
                'records' => $records,
                'routePrefix' => $this->crudRoutePrefix(),
            ]
        ));
    }

    public function export(CrudIndexRequest $request): StreamedResponse
    {
        $this->authorize('viewAny', $this->crudModelClass());

        $crudQuery = CrudQuery::fromRequest($request, $this->crudService()->definition());

        return $this->crudService()->exportCsv($crudQuery);
    }

    public function importForm(): View
    {
        $this->authorize('create', $this->crudModelClass());

        return view($this->crudImportView(), [
            'routePrefix' => $this->crudRoutePrefix(),
            'importColumns' => $this->crudService()->definition()->importColumns,
        ]);
    }

    public function import(ImportSpreadsheetRequest $request): RedirectResponse
    {
        $this->authorize('create', $this->crudModelClass());

        $result = $this->crudService()->importSpreadsheet($request->file('file'));

        if ($result->hasErrors()) {
            return back()
                ->withInput()
                ->withErrors(['file' => $result->errors]);
        }

        return redirect()
            ->route($this->crudRoutePrefix().'.index')
            ->with('status', "Imported {$result->imported} records. Skipped {$result->skipped}.");
    }

    public function bulk(BulkActionRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', $this->crudModelClass());

        $ids = array_map('intval', $request->input('ids', []));
        $action = $request->string('action')->toString();

        $count = match ($action) {
            'delete' => $this->crudService()->bulkDelete($ids),
            'restore' => $this->crudService()->bulkRestore($ids),
            'force_delete' => $this->bulkForceDelete($ids),
            default => 0,
        };

        return back()->with('status', ucfirst(str_replace('_', ' ', $action))." applied to {$count} records.");
    }

    public function restore(int|string $id): RedirectResponse
    {
        $model = $this->crudService()->findOrFail($id, true);
        $this->authorize('delete', $model);

        $this->crudService()->restore($model);

        return back()->with('status', 'Record restored successfully.');
    }

    public function forceDestroy(int|string $id): RedirectResponse
    {
        $model = $this->crudService()->findOrFail($id, true);
        $this->authorize('delete', $model);

        $this->crudService()->forceDelete($model);

        return back()->with('status', 'Record permanently deleted.');
    }

    /**
     * @param  list<int>  $ids
     */
    protected function bulkForceDelete(array $ids): int
    {
        $count = 0;

        foreach ($ids as $id) {
            $model = $this->crudService()->findOrFail($id, true);
            $this->authorize('delete', $model);
            $count += (int) $this->crudService()->forceDelete($model);
        }

        return $count;
    }

    /**
     * @return array<string, mixed>
     */
    protected function crudIndexData(CrudQuery $crudQuery, mixed $records): array
    {
        return [
            'filters' => array_merge(
                ['search' => $crudQuery->search ?? ''],
                $crudQuery->filters
            ),
            'sort' => $crudQuery->sort,
            'direction' => $crudQuery->direction,
            'definition' => $this->crudService()->definition(),
        ];
    }
}
