<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Models\ReadingTest;
use Illuminate\Validation\Rule;

class StoreReadingTestRequest extends CourseSlugRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ReadingTest::class) ?? false;
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
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('reading_tests', 'slug')],
            'exam_type' => ['required', 'string', Rule::in(ExamType::values())],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:240'],
            'instructions' => ['nullable', 'string'],
            'meta_description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['required', 'string', Rule::in(PublishStatus::values())],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
