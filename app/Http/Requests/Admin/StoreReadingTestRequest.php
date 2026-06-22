<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Models\ExamTest;
use Illuminate\Validation\Rule;

class StoreReadingTestRequest extends CourseSlugRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ExamTest::class) ?? false;
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
            'slug' => ['required', 'string', 'max:200', 'unique:tests,slug'],
            'description' => ['nullable', 'string', 'max:5000'],
            'exam_type' => ['required', 'string', Rule::in(ExamType::values())],
            'duration_seconds' => ['nullable', 'integer', 'min:60', 'max:86400'],
            'is_timed' => ['nullable', 'boolean'],
            'status' => ['required', 'string', Rule::in(PublishStatus::values())],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
