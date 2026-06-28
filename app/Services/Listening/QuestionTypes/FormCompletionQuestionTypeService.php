<?php

declare(strict_types=1);

namespace App\Services\Listening\QuestionTypes;

use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Services\Listening\QuestionTypes\Concerns\HandlesCompletionQuestionType;
use Illuminate\Database\Eloquent\Collection;

class FormCompletionQuestionTypeService extends BaseListeningQuestionTypeService
{
    use HandlesCompletionQuestionType;

    public function type(): ListeningQuestionType
    {
        return ListeningQuestionType::FormCompletion;
    }

    public function label(): string
    {
        return 'Form Completion';
    }

    public function schema(): array
    {
        return [
            'default_layout' => ListeningLayoutType::Form->value,
            'default_answer_format' => ListeningAnswerFormat::Text->value,
            'supports_template' => true,
            'required_group_fields' => ['content', 'settings'],
        ];
    }

    public function defaultOptions(): ?array
    {
        return null;
    }

    public function defaultSettings(): array
    {
        return $this->normalizeCompletionSettings([], 'form', 2);
    }

    public function validationRules(): array
    {
        return ['content' => ['required', 'string'], 'settings.word_limit' => ['required', 'integer', 'min:1']];
    }

    public function normalizePayload(array $payload, ?ListeningQuestionGroup $group = null, ?ListeningQuestion $question = null): array
    {
        $payload['settings'] = $this->normalizeCompletionSettings(
            is_array($payload['settings'] ?? null) ? $payload['settings'] : [],
            'form',
        );
        $payload['layout_type'] = $payload['layout_type'] ?? ListeningLayoutType::Form->value;

        if (isset($payload['correct_answer'])) {
            $payload['correct_answer'] = $this->normalizeAnswers($payload['correct_answer'], 'text');
            $payload['answer_format'] = ListeningAnswerFormat::Text->value;
            $payload['word_limit'] = $payload['word_limit'] ?? $this->settingsWordLimit($payload['settings']);
        }

        return $payload;
    }

    public function validatePayload(
        array $payload,
        ?ListeningQuestionGroup $group = null,
        ?ListeningQuestion $question = null,
        ?Collection $questions = null,
    ): array {
        if ($question === null) {
            $content = (string) ($payload['content'] ?? $group?->content ?? '');
            $errors = $group
                ? $this->validateCompletionContent($content, $group)
                : $this->validateTemplateBlanks($this->blankParser->extractBlankNumbers($content), 1, 40);

            if ($this->settingsWordLimit(is_array($payload['settings'] ?? null) ? $payload['settings'] : []) === null) {
                $errors[] = 'Word limit is required.';
            }

            if ($questions !== null && $questions->isNotEmpty() && $group !== null && $content !== '') {
                $errors = array_merge($errors, $this->validateCompletionBlanksMatchQuestions($content, $questions, $group));
            }

            return $errors;
        }

        return $this->validateCorrectAnswerPresence(
            $this->normalizeAnswers($payload['correct_answer'] ?? $question->correct_answer, 'text'),
        );
    }

    public function buildPreviewData(ListeningQuestionGroup $group, Collection $questions): array
    {
        return [
            'type' => $this->type()->value,
            'instruction' => $group->instruction,
            'content' => $group->content,
            'content_preview' => $group->content ? $this->blankParser->replaceBlanksForAdminPreview($group->content) : '',
            'settings' => $group->settings ?? $this->defaultSettings(),
            'questions' => $questions->map(fn (ListeningQuestion $q) => [
                'number' => $q->question_number,
                'correct_answer' => $q->correct_answer,
            ])->values()->all(),
        ];
    }
}
