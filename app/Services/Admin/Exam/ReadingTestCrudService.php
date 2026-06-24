<?php

declare(strict_types=1);

namespace App\Services\Admin\Exam;

use App\Crud\CrudDefinition;
use App\Crud\CrudQuery;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\TestType;
use App\Models\ExamTest;
use App\Services\Admin\Exam\ReadingTestBuilderService;
use App\Services\Crud\AbstractCrudService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ReadingTestCrudService extends AbstractCrudService
{
    public function __construct(private readonly ReadingTestBuilderService $builder)
    {
    }

    protected function modelClass(): string
    {
        return ExamTest::class;
    }

    public function definition(): CrudDefinition
    {
        return new CrudDefinition(
            searchable: ['title', 'slug', 'description'],
            filters: [
                'status' => 'status',
                'exam_type' => 'exam_type',
            ],
            sortable: ['id', 'title', 'status', 'published_at', 'created_at'],
            defaultSort: 'created_at',
            defaultDirection: 'desc',
            exportColumns: [
                'title' => 'Title',
                'slug' => 'Slug',
                'exam_type' => 'Exam Type',
                'total_questions' => 'Questions',
                'status' => 'Status',
            ],
            perPage: 15,
            softDeletes: true,
            relations: ['creator'],
        );
    }

    protected function customizeQuery(Builder $query, CrudQuery $crudQuery): void
    {
        $query->where('type', TestType::ReadingTest->value);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function afterCreate(Model $model, array $attributes): void
    {
        if (! $model instanceof ExamTest) {
            return;
        }

        if (empty($model->created_by) && auth()->id()) {
            $model->forceFill(['created_by' => auth()->id()])->save();
        }

        $this->builder->bootstrapReadingTest($model);
    }

    public function normalizeInput(array $data): array
    {
        $data['type'] = TestType::ReadingTest->value;

        if (empty($data['status'])) {
            $data['status'] = PublishStatus::Draft->value;
        }

        if (($data['status'] ?? null) === PublishStatus::Published->value && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if (($data['status'] ?? null) === PublishStatus::Draft->value) {
            $data['published_at'] = null;
        }

        if (! isset($data['duration_seconds']) || $data['duration_seconds'] === '') {
            $data['duration_seconds'] = 3600;
        }

        $data['is_timed'] = (bool) ($data['is_timed'] ?? true);

        return $data;
    }

    protected function afterUpdate(Model $model, array $attributes): void
    {
        if (! $model instanceof ExamTest) {
            return;
        }

        $this->builder->syncQuestionBankForTest($model);
    }
}
