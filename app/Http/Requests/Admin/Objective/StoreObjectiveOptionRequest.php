<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Objective;

use App\Models\ReadingQuestionGroup;
use Illuminate\Validation\Rule;

class StoreObjectiveOptionRequest extends ObjectiveScopedRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $question = $this->questionFromRoute();

        return [
            'option_key' => ['nullable', 'string', 'max:50'],
            'option_label' => ['required', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function optionAttributes(): array
    {
        return [
            'option_key' => $this->input('option_key'),
            'option_label' => $this->string('option_label')->toString(),
            'sort_order' => $this->input('sort_order'),
        ];
    }

    protected function resolveGroup(): ?ReadingQuestionGroup
    {
        return $this->questionFromRoute()?->group;
    }
}
