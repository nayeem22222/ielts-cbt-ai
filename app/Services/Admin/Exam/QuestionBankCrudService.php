<?php

declare(strict_types=1);

namespace App\Services\Admin\Exam;

use App\Crud\CrudDefinition;
use App\Crud\CrudQuery;
use App\Enums\Commerce\IeltsModule;
use App\Models\QuestionBank;
use App\Services\Crud\AbstractCrudService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class QuestionBankCrudService extends AbstractCrudService
{
    protected function modelClass(): string
    {
        return QuestionBank::class;
    }

    public function definition(): CrudDefinition
    {
        return new CrudDefinition(
            searchable: ['name', 'slug', 'description'],
            filters: [
                'status' => 'status',
                'exam_type' => 'exam_type',
            ],
            sortable: ['id', 'name', 'status', 'created_at'],
            defaultSort: 'name',
            defaultDirection: 'asc',
            exportColumns: [
                'name' => 'Name',
                'slug' => 'Slug',
                'module' => 'Module',
                'exam_type' => 'Exam Type',
                'status' => 'Status',
            ],
            importColumns: [
                'name' => 'Name',
                'slug' => 'Slug',
                'module' => 'Module',
                'exam_type' => 'Exam Type',
                'description' => 'Description',
                'status' => 'Status',
            ],
            perPage: 15,
            softDeletes: true,
            relations: ['creator'],
        );
    }

    protected function customizeQuery(Builder $query, CrudQuery $crudQuery): void
    {
        $query->where('module', IeltsModule::Reading->value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function afterCreate(Model $model, array $attributes): void
    {
        if (! $model instanceof QuestionBank || auth()->id() === null) {
            return;
        }

        if (empty($model->created_by)) {
            $model->forceFill(['created_by' => auth()->id()])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $row
     */
    protected function importRow(array $row): bool
    {
        $name = trim((string) ($row['name'] ?? ''));

        if ($name === '') {
            return false;
        }

        $slug = trim((string) ($row['slug'] ?? '')) ?: str($name)->slug()->toString();

        QuestionBank::query()->updateOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'module' => IeltsModule::Reading->value,
                'exam_type' => $row['exam_type'] ?? 'academic',
                'description' => $row['description'] ?? null,
                'status' => $row['status'] ?? 'draft',
                'created_by' => auth()->id(),
            ]
        );

        return true;
    }
}
