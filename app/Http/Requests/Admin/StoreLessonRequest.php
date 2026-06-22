<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Course\LessonContentType;
use App\Enums\Course\PublishStatus;
use App\Models\Lesson;
use Illuminate\Validation\Rule;

class StoreLessonRequest extends CourseSlugRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Lesson::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareSlug('title');
        $this->merge([
            'is_preview' => $this->boolean('is_preview'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'course_section_id' => ['required', 'integer', 'exists:course_sections,id'],
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:5000'],
            'content_type' => ['required', 'string', Rule::in(LessonContentType::values())],
            'video_url' => ['nullable', 'string', 'max:500'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
            'is_preview' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'string', Rule::in(PublishStatus::values())],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->filled('course_section_id') && $this->filled('slug')) {
                $exists = Lesson::query()
                    ->where('course_section_id', $this->integer('course_section_id'))
                    ->where('slug', $this->string('slug')->toString())
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('slug', 'This slug already exists for the selected section.');
                }
            }
        });
    }
}
