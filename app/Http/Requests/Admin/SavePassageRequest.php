<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Course\PublishStatus;
use App\Models\ExamTest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SavePassageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $test = $this->route('reading_test');

        return $test instanceof ExamTest
            && ($this->user()?->can('update', $test) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'stimulus_text' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', Rule::in(PublishStatus::values())],
        ];
    }
}
