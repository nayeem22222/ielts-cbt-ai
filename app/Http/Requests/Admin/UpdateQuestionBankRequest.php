<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Models\QuestionBank;
use Illuminate\Validation\Rule;

class UpdateQuestionBankRequest extends CourseSlugRequest
{
    public function authorize(): bool
    {
        $bank = $this->route('question_bank');

        return $bank instanceof QuestionBank
            && ($this->user()?->can('update', $bank) ?? false);
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
        /** @var QuestionBank $bank */
        $bank = $this->route('question_bank');

        return [
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['required', 'string', 'max:200', Rule::unique('question_banks', 'slug')->ignore($bank->id)],
            'description' => ['nullable', 'string', 'max:5000'],
            'exam_type' => ['required', 'string', Rule::in(ExamType::values())],
            'status' => ['required', 'string', Rule::in(PublishStatus::values())],
        ];
    }
}
