<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Course\CategoryStatus;
use App\Models\CourseCategory;
use Illuminate\Validation\Rule;

class UpdateCourseCategoryRequest extends CourseSlugRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('course_category')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSlug('name');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var CourseCategory $category */
        $category = $this->route('course_category');

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', Rule::unique('course_categories', 'slug')->ignore($category->id)],
            'parent_id' => ['nullable', 'integer', 'exists:course_categories,id', Rule::notIn([$category->id])],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', Rule::in(CategoryStatus::values())],
        ];
    }
}
