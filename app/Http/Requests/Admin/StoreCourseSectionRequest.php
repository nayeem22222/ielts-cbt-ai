<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Course\PublishStatus;
use App\Models\CourseSection;
use Illuminate\Validation\Rule;

class StoreCourseSectionRequest extends CourseSlugRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', CourseSection::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSlug('title');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', Rule::in(PublishStatus::values())],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->filled('course_id') && $this->filled('slug')) {
                $exists = CourseSection::query()
                    ->where('course_id', $this->integer('course_id'))
                    ->where('slug', $this->string('slug')->toString())
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('slug', 'This slug already exists for the selected course.');
                }
            }
        });
    }
}
