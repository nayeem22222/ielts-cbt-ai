<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Course\CourseLevel;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Models\Course;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends CourseSlugRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Course::class) ?? false;
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
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:200', 'unique:courses,slug'],
            'course_category_id' => ['nullable', 'integer', 'exists:course_categories,id'],
            'description' => ['nullable', 'string', 'max:5000'],
            'exam_type' => ['required', 'string', Rule::in(ExamType::values())],
            'level' => ['required', 'string', Rule::in(CourseLevel::values())],
            'thumbnail_path' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'string', Rule::in(PublishStatus::values())],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
