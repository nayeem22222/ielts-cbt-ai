<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Models\ExamTest;
use Illuminate\Validation\Rule;

class UpdateReadingTestRequest extends CourseSlugRequest
{
    public function authorize(): bool
    {
        $test = $this->route('reading_test');

        return $test instanceof ExamTest
            && ($this->user()?->can('update', $test) ?? false);
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
        /** @var ExamTest $test */
        $test = $this->route('reading_test');

        return [
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:200', Rule::unique('tests', 'slug')->ignore($test->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'exam_type' => ['required', 'string', Rule::in(ExamType::values())],
            'duration_seconds' => ['nullable', 'integer', 'min:60', 'max:86400'],
            'is_timed' => ['nullable', 'boolean'],
            'status' => ['required', 'string', Rule::in(PublishStatus::values())],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
