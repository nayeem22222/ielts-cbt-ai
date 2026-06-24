<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Objective;

class BulkImportObjectiveRequest extends ObjectiveQuestionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'import_text' => ['required', 'string', 'max:50000'],
        ];
    }

    public function importText(): string
    {
        return $this->string('import_text')->toString();
    }
}
