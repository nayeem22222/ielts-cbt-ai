<?php

declare(strict_types=1);

namespace App\Services\Listening\QuestionTypes;

use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use Illuminate\Database\Eloquent\Collection;

class ShortAnswerQuestionTypeService extends BaseListeningQuestionTypeService
{
    public function type(): ListeningQuestionType
    {
        return ListeningQuestionType::ShortAnswer;
    }

    public function label(): string
    {
        return 'Short Answer';
    }

    public function schema(): array
    {
        return [
            'default_layout' => ListeningLayoutType::Default->value,
            'default_answer_format' => ListeningAnswerFormat::Text->value,
            'required_question_fields' => ['question_text', 'correct_answer', 'word_limit'],
        ];
    }

    public function defaultOptions(): ?array
    {
        return null;
    }

    public function defaultSettings(): array
    {
        return [
            'word_limit' => 3,
            'allow_number' => true,
            'instruction' => 'Write NO MORE THAN THREE WORDS AND/OR A NUMBER.',
        ];
    }

    public function validationRules(): array
    {
        return [
            'question_text' => ['required', 'string'],
            'word_limit' => ['required', 'integer', 'min:1', 'max:10'],
        ];
    }

    public function normalizePayload(array $payload, ?ListeningQuestionGroup $group = null, ?ListeningQuestion $question = null): array
    {
        if (isset($payload['correct_answer'])) {
            $payload['correct_answer'] = $this->normalizeAnswers($payload['correct_answer'], 'text');
        }

        if (isset($payload['accepted_answers'])) {
            $payload['accepted_answers'] = $this->normalizeAnswers($payload['accepted_answers'], 'text');
        }

        $payload['settings'] = array_merge(
            $this->defaultSettings(),
            is_array($group?->settings) ? $group->settings : [],
            is_array($payload['settings'] ?? null) ? $payload['settings'] : [],
        );

        if ($question !== null || isset($payload['word_limit'])) {
            $payload['word_limit'] = (int) ($payload['word_limit'] ?? $question?->word_limit ?? $payload['settings']['word_limit'] ?? 3);
            $payload['answer_format'] = $payload['answer_format'] ?? ListeningAnswerFormat::Text->value;
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
            return [];
        }

        $errors = [];
        $text = trim((string) ($payload['question_text'] ?? $question->question_text ?? ''));

        if ($text === '') {
            $errors[] = 'Question text is required for short answer questions.';
        }

        $wordLimit = (int) ($payload['word_limit'] ?? $question->word_limit ?? 0);

        if ($wordLimit < 1) {
            $errors[] = 'Word limit is required.';
        }

        $errors = array_merge(
            $errors,
            $this->validateCorrectAnswerPresence(
                $this->normalizeAnswers($payload['correct_answer'] ?? $question->correct_answer, 'text'),
            ),
        );

        return $errors;
    }

    public function buildPreviewData(ListeningQuestionGroup $group, Collection $questions): array
    {
        return [
            'type' => $this->type()->value,
            'instruction' => $group->instruction,
            'settings' => $group->settings ?? $this->defaultSettings(),
            'questions' => $questions->map(fn (ListeningQuestion $q) => [
                'number' => $q->question_number,
                'text' => $q->question_text,
                'word_limit' => $q->word_limit,
                'correct_answer' => $q->correct_answer,
                'accepted_answers' => $q->accepted_answers,
            ])->values()->all(),
        ];
    }
}
