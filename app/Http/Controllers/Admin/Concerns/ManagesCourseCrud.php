<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Concerns;

use App\Crud\CrudQuery;
use App\Http\Requests\Crud\CrudIndexRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

trait ManagesCourseCrud
{
    abstract protected function crudService(): object;

    abstract protected function crudModelClass(): string;

    abstract protected function crudRoutePrefix(): string;

    abstract protected function entityLabel(): string;

    abstract protected function viewsNamespace(): string;

    public function index(CrudIndexRequest $request): View
    {
        $this->authorize('viewAny', $this->crudModelClass());

        $crudQuery = CrudQuery::fromRequest($request, $this->crudService()->definition());
        $records = $this->crudService()->paginate($crudQuery);

        return view($this->viewsNamespace().'.index', array_merge(
            $this->crudIndexData($crudQuery, $records),
            $this->indexViewData($crudQuery, $records),
            [
                'records' => $records,
                'routePrefix' => $this->crudRoutePrefix(),
                'entityLabel' => $this->entityLabel(),
            ]
        ));
    }

    /**
     * @return array<string, mixed>
     */
    protected function indexViewData(CrudQuery $crudQuery, mixed $records): array
    {
        return [];
    }
}
