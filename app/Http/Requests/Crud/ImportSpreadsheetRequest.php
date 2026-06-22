<?php

declare(strict_types=1);

namespace App\Http\Requests\Crud;

use Illuminate\Foundation\Http\FormRequest;

class ImportSpreadsheetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'mimes:csv,xlsx', 'max:5120'],
        ];
    }
}
