<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\ExamTest;
use Illuminate\Foundation\Http\FormRequest;

class ImportReadingTestRequest extends FormRequest
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
            'file' => ['required', 'file', 'mimes:json,txt', 'max:5120'],
        ];
    }
}
