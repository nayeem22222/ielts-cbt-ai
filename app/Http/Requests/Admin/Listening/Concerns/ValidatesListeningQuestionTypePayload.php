<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Listening\Concerns;

use App\Actions\Listening\QuestionTypes\ValidateQuestionTypePayloadAction;
use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use Illuminate\Validation\Validator;

trait ValidatesListeningQuestionTypePayload
{
    protected function shouldValidateQuestionTypePayload(string $context): bool
    {
        return true;
    }

    protected function validateQuestionTypePayload(Validator $validator, string $context): void
    {
        $validator->after(function (Validator $validator) use ($context): void {
            if ($validator->errors()->isNotEmpty() || ! $this->shouldValidateQuestionTypePayload($context)) {
                return;
            }

            $typeValue = (string) $this->input(
                'question_type',
                $context === 'group'
                    ? $this->route('group')?->question_type?->value
                    : ($this->route('group')?->question_type?->value ?? $this->input('question_type')),
            );

            if ($typeValue === '') {
                return;
            }

            if (! in_array($typeValue, config('listening.question_types.enabled', []), true)) {
                $validator->errors()->add('question_type', "Question type [{$typeValue}] is not supported.");

                return;
            }

            $type = ListeningQuestionType::from($typeValue);
            /** @var ListeningQuestionGroup|null $group */
            $group = $this->route('group');
            /** @var ListeningQuestion|null $question */
            $question = $this->route('question');

            $payload = $this->all();

            if ($group !== null) {
                $group->loadMissing('questions');

                if (
                    $type === ListeningQuestionType::Matching
                    && is_array($group->options)
                    && $group->options !== []
                    && (! is_array($payload['options'] ?? null) || $payload['options'] === [])
                ) {
                    $payload['options'] = $group->options;
                }
            }

            $errors = app(ValidateQuestionTypePayloadAction::class)->execute(
                $context,
                $payload,
                $type,
                $group,
                $question,
                $group?->questions,
            );

            foreach ($errors as $error) {
                $field = $context === 'question' ? 'correct_answer' : (str_contains(strtolower($error), 'content') ? 'content' : 'options');
                $validator->errors()->add($field, $error);
            }
        });
    }
}
