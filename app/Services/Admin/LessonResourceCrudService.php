<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Crud\CrudDefinition;
use App\Models\LessonResource;
use App\Services\Crud\AbstractCrudService;

class LessonResourceCrudService extends AbstractCrudService
{
    protected function modelClass(): string
    {
        return LessonResource::class;
    }

    public function definition(): CrudDefinition
    {
        return new CrudDefinition(
            searchable: ['title', 'file_path', 'external_url'],
            filters: [
                'file_type' => 'file_type',
                'course_id' => 'course_id',
                'lesson_id' => 'lesson_id',
            ],
            sortable: ['id', 'title', 'sort_order', 'created_at'],
            defaultSort: 'sort_order',
            defaultDirection: 'asc',
            exportColumns: [
                'title' => 'Title',
                'file_type' => 'Type',
                'external_url' => 'URL',
            ],
            perPage: 15,
            softDeletes: true,
            relations: ['course', 'lesson'],
        );
    }
}
