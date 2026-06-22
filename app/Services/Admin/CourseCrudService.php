<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Crud\CrudDefinition;
use App\Models\Course;
use App\Services\Crud\AbstractCrudService;
use Illuminate\Database\Eloquent\Model;

class CourseCrudService extends AbstractCrudService
{
    protected function modelClass(): string
    {
        return Course::class;
    }

    public function definition(): CrudDefinition
    {
        return new CrudDefinition(
            searchable: ['title', 'slug', 'description'],
            filters: [
                'status' => 'status',
                'exam_type' => 'exam_type',
                'level' => 'level',
                'course_category_id' => 'course_category_id',
            ],
            sortable: ['id', 'title', 'sort_order', 'status', 'published_at', 'created_at'],
            defaultSort: 'sort_order',
            defaultDirection: 'asc',
            exportColumns: [
                'title' => 'Title',
                'slug' => 'Slug',
                'exam_type' => 'Exam Type',
                'level' => 'Level',
                'status' => 'Status',
            ],
            perPage: 15,
            softDeletes: true,
            relations: ['category', 'creator'],
        );
    }

    protected function afterCreate(Model $model, array $attributes): void
    {
        if (! $model instanceof Course) {
            return;
        }

        if (empty($model->created_by) && auth()->id()) {
            $model->forceFill(['created_by' => auth()->id()])->save();
        }
    }
}
