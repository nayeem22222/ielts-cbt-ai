<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Matching;

use App\Models\ReadingQuestionGroup;

class UpdateScopedMatchingOptionRequest extends MatchingScopedRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'option_key' => ['sometimes', 'required', 'string', 'max:50'],
            'option_label' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
            'confirm_delete' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function optionAttributes(): array
    {
        return array_filter([
            'option_key' => $this->has('option_key') ? $this->string('option_key')->toString() : null,
            'option_label' => $this->has('option_label') ? $this->input('option_label') : null,
            'sort_order' => $this->input('sort_order'),
        ], fn ($value) => $value !== null);
    }

    public function confirmedDelete(): bool
    {
        return $this->boolean('confirm_delete');
    }

    protected function resolveGroup(): ?ReadingQuestionGroup
    {
        return $this->optionFromRoute()?->group;
    }
}
