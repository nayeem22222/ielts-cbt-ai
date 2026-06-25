<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\ReadingQuestionGroup;
use App\Support\Reading\ReadingGroupInteraction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReadingGroupInteractionSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $group = $this->route('group');

        if (! $group instanceof ReadingQuestionGroup) {
            return false;
        }

        $test = $group->passage?->test;

        return $test !== null && ($this->user()?->can('update', $test) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $group = $this->route('group');
        $modes = $group instanceof ReadingQuestionGroup && $group->question_type?->isCompletionBuilderType()
            ? ReadingGroupInteraction::completionInteractionModes()
            : ReadingGroupInteraction::matchingInteractionModes();

        return [
            'interaction_mode' => ['required', 'string', Rule::in($modes)],
            'allow_reuse' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function settingsPayload(): array
    {
        return [
            'interaction_mode' => $this->string('interaction_mode')->toString(),
            'allow_reuse' => $this->boolean('allow_reuse'),
        ];
    }
}
