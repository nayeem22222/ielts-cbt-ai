<?php

declare(strict_types=1);

namespace App\Crud\Contracts;

use App\Crud\CrudDefinition;
use App\Crud\CrudQuery;
use App\Crud\ImportResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface CrudServiceInterface
{
    public function definition(): CrudDefinition;

    public function paginate(CrudQuery $query): LengthAwarePaginator;

    public function findOrFail(int|string $id, bool $withTrashed = false): Model;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Model;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Model $model, array $attributes): Model;

    public function delete(Model $model): bool;

    public function restore(Model $model): bool;

    public function forceDelete(Model $model): bool;

    /**
     * @param  list<int|string>  $ids
     */
    public function bulkDelete(array $ids): int;

    /**
     * @param  list<int|string>  $ids
     */
    public function bulkRestore(array $ids): int;

    public function exportCsv(CrudQuery $query): StreamedResponse;

    public function importSpreadsheet(UploadedFile $file): ImportResult;
}
