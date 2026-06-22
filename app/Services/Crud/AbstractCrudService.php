<?php

declare(strict_types=1);

namespace App\Services\Crud;

use App\Crud\Contracts\CrudServiceInterface;
use App\Crud\CrudDefinition;
use App\Crud\CrudQuery;
use App\Crud\CrudQueryBuilder;
use App\Crud\ImportResult;
use App\Services\Service;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

abstract class AbstractCrudService extends Service implements CrudServiceInterface
{
    public function __construct(
        protected readonly CsvExporter $csvExporter,
        protected readonly SpreadsheetImporter $spreadsheetImporter,
    ) {
    }

    /**
     * @return class-string<Model>
     */
    abstract protected function modelClass(): string;

    abstract public function definition(): CrudDefinition;

    public function paginate(CrudQuery $query): LengthAwarePaginator
    {
        $builder = new CrudQueryBuilder($this->definition());
        $eloquent = $builder->apply($this->newQuery(), $query, $this->customizeQuery(...));

        return $eloquent->paginate(
            $query->perPage ?? $this->definition()->perPage
        )->withQueryString();
    }

    public function findOrFail(int|string $id, bool $withTrashed = false): Model
    {
        $query = $this->newQuery();

        if ($withTrashed && $this->definition()->softDeletes) {
            $query->withTrashed();
        }

        return $query->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Model
    {
        $model = $this->newQuery()->create($attributes);
        $this->afterCreate($model, $attributes);

        return $model->refresh();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Model $model, array $attributes): Model
    {
        $model->fill($attributes);
        $model->save();
        $this->afterUpdate($model, $attributes);

        return $model->refresh();
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }

    public function restore(Model $model): bool
    {
        if (! $this->definition()->softDeletes || ! method_exists($model, 'restore')) {
            return false;
        }

        return (bool) $model->restore();
    }

    public function forceDelete(Model $model): bool
    {
        if (! $this->definition()->softDeletes || ! method_exists($model, 'forceDelete')) {
            return (bool) $model->delete();
        }

        return (bool) $model->forceDelete();
    }

    public function bulkDelete(array $ids): int
    {
        $count = 0;

        foreach ($ids as $id) {
            $model = $this->findOrFail($id, $this->definition()->softDeletes);
            $count += (int) $this->delete($model);
        }

        return $count;
    }

    public function bulkRestore(array $ids): int
    {
        $count = 0;

        foreach ($ids as $id) {
            $model = $this->findOrFail($id, true);
            $count += (int) $this->restore($model);
        }

        return $count;
    }

    public function exportCsv(CrudQuery $query): StreamedResponse
    {
        $builder = new CrudQueryBuilder($this->definition());
        $eloquent = $builder->apply($this->newQuery(), $query, $this->customizeQuery(...));

        return $this->csvExporter->stream(
            $this->exportFilename(),
            $eloquent,
            $this->definition()->exportColumns,
        );
    }

    public function importSpreadsheet(UploadedFile $file): ImportResult
    {
        return $this->spreadsheetImporter->import(
            $file,
            fn (array $row): bool => $this->importRow($row),
        );
    }

    protected function newQuery(): Builder
    {
        return $this->modelClass()::query();
    }

    protected function exportFilename(): string
    {
        return strtolower(class_basename($this->modelClass())).'-export-'.now()->format('Y-m-d-His').'.csv';
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function afterCreate(Model $model, array $attributes): void
    {
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function afterUpdate(Model $model, array $attributes): void
    {
    }

    protected function customizeQuery(Builder $query, CrudQuery $crudQuery): void
    {
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function importRow(array $row): bool
    {
        return false;
    }
}
