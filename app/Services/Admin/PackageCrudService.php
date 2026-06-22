<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Crud\CrudDefinition;
use App\Enums\Commerce\IeltsModule;
use App\Models\Package;
use App\Services\Crud\AbstractCrudService;
use Illuminate\Database\Eloquent\Model;

class PackageCrudService extends AbstractCrudService
{
    protected function modelClass(): string
    {
        return Package::class;
    }

    public function definition(): CrudDefinition
    {
        return new CrudDefinition(
            searchable: ['name', 'slug', 'description'],
            filters: [
                'status' => 'status',
                'billing_interval' => 'billing_interval',
                'is_active' => 'is_active',
            ],
            sortable: ['id', 'name', 'price', 'sort_order', 'duration_days', 'created_at'],
            defaultSort: 'sort_order',
            defaultDirection: 'asc',
            exportColumns: [
                'name' => 'Name',
                'slug' => 'Slug',
                'price' => 'Price',
                'currency' => 'Currency',
                'duration_days' => 'Duration (days)',
                'status' => 'Status',
            ],
            perPage: 15,
            softDeletes: true,
            relations: ['courses'],
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function afterCreate(Model $model, array $attributes): void
    {
        $this->syncCourses($model, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function afterUpdate(Model $model, array $attributes): void
    {
        $this->syncCourses($model, $attributes);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function normalizeInput(array $data): array
    {
        $modules = collect($data['module_access'] ?? [])
            ->filter(fn (mixed $value): bool => in_array((string) $value, IeltsModule::values(), true))
            ->values()
            ->all();

        $attemptLimits = [];

        foreach (IeltsModule::cases() as $module) {
            $key = 'attempt_limits.'.$module->value;
            $raw = data_get($data, $key) ?? ($data['attempt_limits'][$module->value] ?? null);

            if ($raw !== null && $raw !== '') {
                $attemptLimits[$module->value] = (int) $raw;
            }
        }

        $data['module_access'] = $modules;
        $data['attempt_limits'] = $attemptLimits !== [] ? $attemptLimits : null;
        unset($data['course_ids']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function syncCourses(Model $model, array $attributes): void
    {
        if (! $model instanceof Package || ! array_key_exists('course_ids', $attributes)) {
            return;
        }

        $courseIds = collect($attributes['course_ids'] ?? [])
            ->filter(fn (mixed $id): bool => $id !== null && $id !== '')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        $model->courses()->sync($courseIds);
    }
}
