<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Exam\PassageStatus;
use App\Models\ReadingTest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class ReadingPassageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $test = $this->route('readingTest');

        return $test instanceof ReadingTest
            && ($this->user()?->can('update', $test) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'instruction' => ['nullable', 'string'],
            'start_question' => ['required', 'integer', 'min:1', 'max:100'],
            'end_question' => ['required', 'integer', 'min:1', 'max:100', 'gt:start_question'],
            'content_html' => ['required', 'string'],
            'status' => ['required', 'string', Rule::in(PassageStatus::values())],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'auto_paragraph_labels' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function passageAttributes(): array
    {
        return [
            'title' => $this->string('title')->toString(),
            'subtitle' => $this->input('subtitle'),
            'instruction' => $this->input('instruction'),
            'start_question' => (int) $this->input('start_question'),
            'end_question' => (int) $this->input('end_question'),
            'content_html' => $this->string('content_html')->toString(),
            'status' => $this->string('status')->toString(),
            'sort_order' => $this->input('sort_order'),
            'auto_paragraph_labels' => $this->boolean('auto_paragraph_labels'),
        ];
    }
}
