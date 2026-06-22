<?php

declare(strict_types=1);

namespace App\Crud;

use Illuminate\Database\Eloquent\Builder;

final class CrudQueryBuilder
{
    public function __construct(
        private readonly CrudDefinition $definition,
    ) {
    }

    /**
     * @param  callable(Builder, CrudQuery): void|null  $customize
     */
    public function apply(Builder $query, CrudQuery $crudQuery, ?callable $customize = null): Builder
    {
        if ($this->definition->softDeletes && $crudQuery->onlyTrashed) {
            $query->onlyTrashed();
        }

        if ($this->definition->relations !== []) {
            $query->with($this->definition->relations);
        }

        if ($crudQuery->search !== null && $this->definition->searchable !== []) {
            $query->where(function (Builder $inner) use ($crudQuery): void {
                foreach ($this->definition->searchable as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $inner->{$method}($column, 'like', '%'.$crudQuery->search.'%');
                }
            });
        }

        foreach ($crudQuery->filters as $key => $value) {
            $filter = $this->definition->filters[$key] ?? null;

            if ($filter === null) {
                continue;
            }

            if (is_callable($filter)) {
                $filter($query, $value);

                continue;
            }

            $query->where($filter, $value);
        }

        if ($customize !== null) {
            $customize($query, $crudQuery);
        }

        $sort = $crudQuery->sort ?? $this->definition->defaultSort;

        if (! in_array($sort, $this->definition->sortable, true)) {
            $sort = $this->definition->defaultSort;
        }

        return $query->orderBy($sort, $crudQuery->direction);
    }
}
