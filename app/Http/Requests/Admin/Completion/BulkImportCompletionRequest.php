<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Completion;

use App\Models\ReadingQuestionGroup;

class BulkImportCompletionRequest extends CompletionScopedRequest
{
    protected function resolveGroup(): ?ReadingQuestionGroup
    {
        $group = $this->route('group');

        return $group instanceof ReadingQuestionGroup ? $group : null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'import_text' => ['required', 'string', 'max:50000'],
            'confirm_remove' => ['nullable', 'boolean'],
        ];
    }

    public function importText(): string
    {
        return $this->string('import_text')->toString();
    }
}
