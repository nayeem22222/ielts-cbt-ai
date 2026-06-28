<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Listening;

use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Http\Requests\Admin\Listening\Concerns\DecodesListeningJsonAttributes;
use App\Http\Requests\Admin\Listening\Concerns\ValidatesListeningQuestionTypePayload;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use App\Rules\Listening\ValidListeningQuestionRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateListeningQuestionGroupRequest extends FormRequest
{
    use DecodesListeningJsonAttributes;
    use ValidatesListeningQuestionTypePayload;

    public function authorize(): bool
    {
        /** @var ListeningQuestionGroup|null $group */
        $group = $this->route('group');

        return $group !== null && ($this->user()?->can('update', $group) ?? false);
    }

    protected function prepareForValidation(): void
    {
        $this->normalizeNullableIntegers(['audio_id', 'display_order']);
        $this->decodeJsonAttributes(['transcript_reference', 'options', 'settings', 'validation_rules', 'meta']);
        $this->merge(['is_active' => $this->boolean('is_active', true)]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var ListeningSection|null $section */
        $section = $this->route('section');

        if (! $section instanceof ListeningSection) {
            /** @var ListeningQuestionGroup|null $group */
            $group = $this->route('group');
            $section = $group?->section;
        }

        if (! $section instanceof ListeningSection) {
            return [];
        }

        /** @var ListeningQuestionGroup|null $group */
        $group = $this->route('group');

        if (! $group instanceof ListeningQuestionGroup) {
            return [];
        }

        $start = (int) $this->input('start_question_number', $group->start_question_number);

        return [
            'title' => ['nullable', 'string', 'max:255'],
            'instruction' => ['nullable', 'string'],
            'question_type' => ['required', 'string', Rule::enum(ListeningQuestionType::class)],
            'start_question_number' => ['required', 'integer', 'min:1', 'max:40'],
            'end_question_number' => ['required', 'integer', 'min:1', 'max:40', 'gte:start_question_number', new ValidListeningQuestionRange($section, $start, $group->id)],
            'layout_type' => ['required', 'string', Rule::enum(ListeningLayoutType::class)],
            'audio_id' => ['nullable', 'integer', 'exists:listening_audios,id'],
            'transcript_reference' => ['nullable', 'array'],
            'image_path' => ['nullable', 'string', 'max:2048'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'options' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'validation_rules' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'meta' => ['nullable', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $this->validateQuestionTypePayload($validator, 'group');
    }

    protected function shouldValidateQuestionTypePayload(string $context): bool
    {
        if ($context !== 'group') {
            return true;
        }

        return $this->hasAny(['content', 'options', 'settings', 'image_path', 'validation_rules']);
    }
}
