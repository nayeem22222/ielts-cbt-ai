<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Matching;

class BulkImportMatchingRequest extends MatchingQuestionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'options_text' => ['nullable', 'string', 'max:50000'],
            'questions_text' => ['nullable', 'string', 'max:50000'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function importPayload(): array
    {
        return [
            'options_text' => $this->input('options_text'),
            'questions_text' => $this->input('questions_text'),
        ];
    }
}
