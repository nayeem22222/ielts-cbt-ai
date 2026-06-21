<?php

declare(strict_types=1);

namespace App\Repositories\Eloquent;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class EloquentRepository implements RepositoryInterface
{
    /**
     * @return class-string<Model>
     */
    abstract protected function model(): string;

    protected function query(): Builder
    {
        return $this->model()::query();
    }

    public function find(int|string $id): ?Model
    {
        return $this->query()->find($id);
    }

    public function all(): Collection
    {
        return $this->query()->get();
    }

    public function create(array $attributes): Model
    {
        return $this->query()->create($attributes);
    }

    public function update(Model $model, array $attributes): Model
    {
        $model->update($attributes);

        return $model->refresh();
    }

    public function delete(Model $model): bool
    {
        return (bool) $model->delete();
    }
}
