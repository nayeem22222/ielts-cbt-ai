<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Crud\CrudDefinition;
use App\Models\CourseSection;
use App\Services\Crud\AbstractCrudService;

class CourseSectionCrudService extends AbstractCrudService
{
    protected function modelClass(): string
    {
        return CourseSection::class;
    }

    public function definition(): CrudDefinition
    {
        return new CrudDefinition(
            searchable: ['title', 'slug', 'description'],
            filters: [
                'status' => 'status',
                'course_id' => 'course_id',
            ],
            sortable: ['id', 'title', 'sort_order', 'status', 'created_at'],
            defaultSort: 'sort_order',
            defaultDirection: 'asc',
            exportColumns: [
                'title' => 'Title',
                'slug' => 'Slug',
                'status' => 'Status',
            ],
            perPage: 15,
            softDeletes: true,
            relations: ['course'],
        );
    }
}
