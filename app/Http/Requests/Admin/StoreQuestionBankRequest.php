<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Models\QuestionBank;
use Illuminate\Validation\Rule;

class StoreQuestionBankRequest extends CourseSlugRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', QuestionBank::class) ?? false;
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
        return [
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:200', 'unique:question_banks,slug'],
            'description' => ['nullable', 'string', 'max:5000'],
            'exam_type' => ['required', 'string', Rule::in(ExamType::values())],
            'status' => ['required', 'string', Rule::in(PublishStatus::values())],
        ];
    }
}
