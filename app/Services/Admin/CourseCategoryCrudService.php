<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Crud\CrudDefinition;
use App\Models\CourseCategory;
use App\Services\Crud\AbstractCrudService;
use Illuminate\Database\Eloquent\Model;

class CourseCategoryCrudService extends AbstractCrudService
{
    protected function modelClass(): string
    {
        return CourseCategory::class;
    }

    public function definition(): CrudDefinition
    {
        return new CrudDefinition(
            searchable: ['name', 'slug', 'description'],
            filters: [
                'status' => 'status',
                'parent_id' => 'parent_id',
            ],
            sortable: ['id', 'name', 'sort_order', 'status', 'created_at'],
            defaultSort: 'sort_order',
            defaultDirection: 'asc',
            exportColumns: [
                'name' => 'Name',
                'slug' => 'Slug',
                'status' => 'Status',
                'sort_order' => 'Sort Order',
            ],
            perPage: 15,
            softDeletes: true,
            relations: ['parent'],
        );
    }

    protected function afterCreate(Model $model, array $attributes): void
    {
        // no-op
    }
}
