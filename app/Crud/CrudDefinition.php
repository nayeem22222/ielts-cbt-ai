<?php

declare(strict_types=1);

namespace App\Crud;

final class CrudDefinition
{
    /**
     * @param  list<string>  $searchable
     * @param  array<string, string|callable>  $filters
     * @param  list<string>  $sortable
     * @param  array<string, string>  $exportColumns
     * @param  list<string>  $relations
     * @param  list<string>  $importColumns
     */
    public function __construct(
        public readonly array $searchable = [],
        public readonly array $filters = [],
        public readonly array $sortable = ['id', 'created_at'],
        public readonly string $defaultSort = 'id',
        public readonly string $defaultDirection = 'desc',
        public readonly array $exportColumns = [],
        public readonly array $importColumns = [],
        public readonly int $perPage = 15,
        public readonly bool $softDeletes = true,
        public readonly array $relations = [],
    ) {
    }
}
