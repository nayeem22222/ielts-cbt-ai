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

class SentenceCompletionQuestionTypeService extends BaseListeningQuestionTypeService
{
    use HandlesCompletionQuestionType;

    public function type(): ListeningQuestionType
    {
        return ListeningQuestionType::SentenceCompletion;
    }

    public function label(): string
    {
        return 'Sentence Completion';
    }

    public function schema(): array
    {
        return [
            'default_layout' => ListeningLayoutType::Default->value,
            'default_answer_format' => ListeningAnswerFormat::Text->value,
            'supports_template' => true,
            'required_group_fields' => ['settings'],
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
            'sentences' => [
                ['number' => 1, 'text' => 'The tour begins at [blank:1].'],
            ],
        ];
    }

    public function validationRules(): array
    {
        return ['settings.sentences' => ['required', 'array', 'min:1'], 'settings.word_limit' => ['required', 'integer']];
    }

    public function normalizePayload(array $payload, ?ListeningQuestionGroup $group = null, ?ListeningQuestion $question = null): array
    {
        $payload['settings'] = array_merge($this->defaultSettings(), is_array($payload['settings'] ?? null) ? $payload['settings'] : []);

        if (isset($payload['correct_answer'])) {
            $payload['correct_answer'] = $this->normalizeAnswers($payload['correct_answer'], 'text');
            $payload['answer_format'] = ListeningAnswerFormat::Text->value;
            $payload['word_limit'] = $payload['word_limit'] ?? $this->settingsWordLimit($payload['settings'], 3);
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
            $settings = is_array($payload['settings'] ?? null)
                ? $payload['settings']
                : (is_array($group?->settings) ? $group->settings : []);
            $errors = [];

            if ($this->settingsWordLimit($settings) === null) {
                $errors[] = 'Word limit is required.';
            }

            foreach ($settings['sentences'] ?? [] as $sentence) {
                $text = (string) ($sentence['text'] ?? '');
                $number = (int) ($sentence['number'] ?? 0);

                if ($number > 0 && ! str_contains($text, "[blank:{$number}]")) {
                    $errors[] = "Sentence for question {$number} must contain [blank:{$number}].";
                }

                if ($group !== null) {
                    $errors = array_merge($errors, $this->validateTemplateBlanks(
                        $this->blankParser->extractBlankNumbers($text),
                        (int) $group->start_question_number,
                        (int) $group->end_question_number,
                    ));
                }
            }

            return array_unique($errors);
        }

        return $this->validateCorrectAnswerPresence(
            $this->normalizeAnswers($payload['correct_answer'] ?? $question->correct_answer, 'text'),
        );
    }

    public function buildPreviewData(ListeningQuestionGroup $group, Collection $questions): array
    {
        $sentences = $group->settings['sentences'] ?? [];

        return [
            'type' => $this->type()->value,
            'instruction' => $group->instruction,
            'settings' => $group->settings ?? $this->defaultSettings(),
            'sentences_preview' => array_map(
                fn (array $s) => [
                    'number' => $s['number'] ?? null,
                    'html' => $this->blankParser->replaceBlanksForAdminPreview((string) ($s['text'] ?? '')),
                ],
                $sentences,
            ),
            'questions' => $questions->map(fn (ListeningQuestion $q) => [
                'number' => $q->question_number,
                'correct_answer' => $q->correct_answer,
            ])->values()->all(),
        ];
    }
}
