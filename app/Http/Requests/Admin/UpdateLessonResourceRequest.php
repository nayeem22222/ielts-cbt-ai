<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Course\ResourceType;
use App\Models\LessonResource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLessonResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('lesson_resource')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_downloadable' => $this->boolean('is_downloadable'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['nullable', 'integer', 'exists:courses,id', 'required_without:lesson_id'],
            'lesson_id' => ['nullable', 'integer', 'exists:lessons,id', 'required_without:course_id'],
            'title' => ['required', 'string', 'max:200'],
            'file_path' => ['nullable', 'string', 'max:500'],
            'file_type' => ['required', 'string', Rule::in(ResourceType::values())],
            'external_url' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_downloadable' => ['nullable', 'boolean'],
        ];
    }
}
