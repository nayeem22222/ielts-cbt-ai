<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\ExamTest;
use Illuminate\Foundation\Http\FormRequest;

class ImportReadingTestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ExamTest::class) ?? false;
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
