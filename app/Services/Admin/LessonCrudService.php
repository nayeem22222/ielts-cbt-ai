<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Crud\CrudDefinition;
use App\Models\Lesson;
use App\Services\Crud\AbstractCrudService;
use Illuminate\Database\Eloquent\Model;

class LessonCrudService extends AbstractCrudService
{
    protected function modelClass(): string
    {
        return Lesson::class;
    }

    public function definition(): CrudDefinition
    {
        return new CrudDefinition(
            searchable: ['title', 'slug', 'description'],
            filters: [
                'status' => 'status',
                'content_type' => 'content_type',
                'course_section_id' => 'course_section_id',
            ],
            sortable: ['id', 'title', 'sort_order', 'status', 'published_at', 'created_at'],
            defaultSort: 'sort_order',
            defaultDirection: 'asc',
            exportColumns: [
                'title' => 'Title',
                'slug' => 'Slug',
                'content_type' => 'Content Type',
                'status' => 'Status',
            ],
            perPage: 15,
            softDeletes: true,
            relations: ['section.course'],
        );
    }

    protected function afterCreate(Model $model, array $attributes): void
    {
        if (! $model instanceof Lesson) {
            return;
        }

        if (empty($model->created_by) && auth()->id()) {
            $model->forceFill(['created_by' => auth()->id()])->save();
        }
    }
}
