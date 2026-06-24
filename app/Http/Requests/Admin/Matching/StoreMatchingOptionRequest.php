<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Matching;

class StoreMatchingOptionRequest extends MatchingQuestionRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'option_key' => ['required', 'string', 'max:50'],
            'option_label' => ['nullable', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function optionAttributes(): array
    {
        return [
            'option_key' => $this->string('option_key')->toString(),
            'option_label' => $this->input('option_label'),
            'sort_order' => $this->input('sort_order'),
        ];
    }
}
