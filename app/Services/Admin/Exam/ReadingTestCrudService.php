<?php

declare(strict_types=1);

namespace App\Services\Admin\Exam;

use App\Crud\CrudDefinition;
use App\Crud\CrudQuery;
use App\Enums\Course\PublishStatus;
use App\Models\ReadingAttempt;
use App\Models\ReadingCorrectAnswer;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingQuestionOption;
use App\Models\ReadingTest;
use App\Services\Crud\AbstractCrudService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReadingTestCrudService extends AbstractCrudService
{
    protected function modelClass(): string
    {
        return ReadingTest::class;
    }

    public function definition(): CrudDefinition
    {
        return new CrudDefinition(
            searchable: ['title', 'slug'],
            filters: [
                'status' => 'status',
                'exam_type' => 'exam_type',
            ],
            sortable: ['id', 'title', 'created_at', 'duration_minutes'],
            defaultSort: 'id',
            defaultDirection: 'desc',
            exportColumns: [
                'id' => 'ID',
                'title' => 'Title',
                'slug' => 'Slug',
                'exam_type_label' => 'Exam Type',
                'duration_minutes' => 'Duration',
                'status_label' => 'Status',
                'passages_count' => 'Passages Count',
                'questions_count' => 'Questions Count',
                'published_at' => 'Published At',
                'created_at' => 'Created At',
                'updated_at' => 'Updated At',
            ],
            perPage: 15,
            softDeletes: true,
            relations: ['creator'],
        );
    }

    protected function customizeQuery(Builder $query, CrudQuery $crudQuery): void
    {
        $query->withCount(['passages', 'questionGroups']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function afterCreate(Model $model, array $attributes): void
    {
        if (! $model instanceof ReadingTest) {
            return;
        }

        if (empty($model->created_by) && auth()->id()) {
            $model->forceFill(['created_by' => auth()->id()])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function normalizeInput(array $data): array
    {
        if (empty($data['status'])) {
            $data['status'] = PublishStatus::Draft->value;
        }

        if (empty($data['slug']) && ! empty($data['title'])) {
            $data['slug'] = Str::slug((string) $data['title']);
        }

        if (($data['status'] ?? null) === PublishStatus::Published->value && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        if (! isset($data['duration_minutes']) || $data['duration_minutes'] === '') {
            $data['duration_minutes'] = 60;
        }

        return $data;
    }

    protected function afterUpdate(Model $model, array $attributes): void
    {
        if (! $model instanceof ReadingTest) {
            return;
        }
    }

    public function publish(ReadingTest $test): ReadingTest
    {
        return DB::transaction(function () use ($test): ReadingTest {
            $test->forceFill([
                'status' => PublishStatus::Published,
                'published_at' => $test->published_at ?? now(),
                'updated_by' => auth()->id(),
            ])->save();

            return $test->refresh();
        });
    }

    public function unpublish(ReadingTest $test): ReadingTest
    {
        $test->forceFill([
            'status' => PublishStatus::Draft,
            'updated_by' => auth()->id(),
        ])->save();

        return $test->refresh();
    }

    public function archive(ReadingTest $test): ReadingTest
    {
        $test->forceFill([
            'status' => PublishStatus::Archived,
            'updated_by' => auth()->id(),
        ])->save();

        return $test->refresh();
    }

    public function duplicate(ReadingTest $test, int $userId): ReadingTest
    {
        return DB::transaction(function () use ($test, $userId): ReadingTest {
            $test->load([
                'passages.groups.groupOptions',
                'passages.groups.questions.options',
                'passages.groups.questions.correctAnswers',
            ]);

            $copy = ReadingTest::query()->create([
                'slug' => $this->uniqueSlug('copy-of-'.$test->slug),
                'title' => 'Copy of '.$test->title,
                'exam_type' => $test->exam_type,
                'duration_minutes' => $test->duration_minutes,
                'instructions' => $test->instructions,
                'meta_description' => $test->meta_description,
                'notes' => $test->notes,
                'status' => PublishStatus::Draft,
                'published_at' => null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($test->passages as $passage) {
                /** @var ReadingPassage $passageCopy */
                $passageCopy = $copy->passages()->create($passage->only([
                    'part_number',
                    'title',
                    'subtitle',
                    'instruction',
                    'start_question',
                    'end_question',
                    'content_html',
                    'content_text',
                    'status',
                    'settings',
                    'sort_order',
                ]));

                foreach ($passage->groups as $group) {
                    /** @var ReadingQuestionGroup $groupCopy */
                    $groupCopy = $passageCopy->groups()->create($group->only([
                        'title',
                        'instruction',
                        'question_type',
                        'start_question',
                        'end_question',
                        'sort_order',
                        'status',
                        'settings',
                    ]));

                    foreach ($group->groupOptions as $option) {
                        $groupCopy->groupOptions()->create($option->only([
                            'option_key',
                            'option_label',
                            'sort_order',
                        ]));
                    }

                    foreach ($group->questions as $question) {
                        /** @var ReadingQuestion $questionCopy */
                        $questionCopy = $groupCopy->questions()->create($question->only([
                            'question_number',
                            'prompt',
                            'paragraph_reference',
                            'explanation',
                            'marks',
                            'sort_order',
                            'difficulty',
                            'metadata',
                        ]));

                        foreach ($question->options as $option) {
                            $questionCopy->options()->create($option->only([
                                'option_key',
                                'option_label',
                                'sort_order',
                            ]));
                        }

                        foreach ($question->correctAnswers as $answer) {
                            $questionCopy->correctAnswers()->create($answer->only([
                                'answer',
                                'answer_json',
                                'matching_key',
                            ]));
                        }
                    }
                }
            }

            return $copy->refresh();
        });
    }

    public function forceDelete(Model $model): bool
    {
        if (! $model instanceof ReadingTest) {
            return parent::forceDelete($model);
        }

        if (ReadingAttempt::query()->where('reading_test_id', $model->id)->exists()) {
            throw ValidationException::withMessages([
                'reading_test' => 'This reading test has student attempts and cannot be permanently deleted.',
            ]);
        }

        return DB::transaction(fn (): bool => parent::forceDelete($model));
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkPublish(array $ids): int
    {
        return $this->bulkStatus($ids, PublishStatus::Published);
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkUnpublish(array $ids): int
    {
        return $this->bulkStatus($ids, PublishStatus::Draft);
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkArchive(array $ids): int
    {
        return $this->bulkStatus($ids, PublishStatus::Archived);
    }

    private function uniqueSlug(string $base): string
    {
        $base = Str::slug($base) ?: 'reading-test';
        $slug = $base;
        $suffix = 2;

        while (ReadingTest::query()->withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  list<int>  $ids
     */
    private function bulkStatus(array $ids, PublishStatus $status): int
    {
        return DB::transaction(function () use ($ids, $status): int {
            $count = 0;

            foreach ($ids as $id) {
                /** @var ReadingTest $test */
                $test = $this->findOrFail($id);

                $attributes = [
                    'status' => $status,
                    'updated_by' => auth()->id(),
                ];

                if ($status === PublishStatus::Published && $test->published_at === null) {
                    $attributes['published_at'] = now();
                }

                $test->forceFill($attributes)->save();
                $count++;
            }

            return $count;
        });
    }
}
