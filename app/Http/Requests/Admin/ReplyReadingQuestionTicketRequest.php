<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReplyReadingQuestionTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('tests.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'admin_reply' => ['required', 'string', 'max:10000'],
        ];
    }
}
