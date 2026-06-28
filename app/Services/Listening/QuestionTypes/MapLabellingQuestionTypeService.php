<?php

declare(strict_types=1);

namespace App\Services\Listening\QuestionTypes;

use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Services\Listening\QuestionTypes\Concerns\HandlesLabellingQuestionType;
use Illuminate\Database\Eloquent\Collection;

class MapLabellingQuestionTypeService extends BaseListeningQuestionTypeService
{
    use HandlesLabellingQuestionType;

    public function type(): ListeningQuestionType
    {
        return ListeningQuestionType::MapLabelling;
    }

    public function label(): string
    {
        return 'Map Labelling';
    }

    public function schema(): array
    {
        return [
            'default_layout' => ListeningLayoutType::Map->value,
            'default_answer_format' => ListeningAnswerFormat::MapLabel->value,
            'supports_options' => true,
            'supports_image' => true,
            'required_group_fields' => ['image_path', 'options'],
        ];
    }

    public function defaultOptions(): ?array
    {
        return [
            'image' => ['path' => '', 'alt' => ''],
            'labels' => [['key' => 'A', 'text' => '']],
            'points' => [['number' => 1, 'x' => 50, 'y' => 50]],
        ];
    }

    public function defaultSettings(): array
    {
        return [];
    }

    public function validationRules(): array
    {
        return ['options' => ['required', 'array'], 'image_path' => ['required', 'string']];
    }

    public function normalizePayload(array $payload, ?ListeningQuestionGroup $group = null, ?ListeningQuestion $question = null): array
    {
        if (isset($payload['options']) && is_array($payload['options'])) {
            $payload['options'] = $this->normalizeLabellingOptions(
                $payload['options'],
                $payload['image_path'] ?? $group?->image_path,
                $payload['image_alt'] ?? $group?->image_alt,
            );
        }

        if (isset($payload['correct_answer'])) {
            $payload['correct_answer'] = $this->normalizeAnswers($payload['correct_answer'], 'map_label');
            $payload['answer_format'] = ListeningAnswerFormat::MapLabel->value;
        }

        $payload['layout_type'] = $payload['layout_type'] ?? ListeningLayoutType::Map->value;

        return $payload;
    }

    public function validatePayload(
        array $payload,
        ?ListeningQuestionGroup $group = null,
        ?ListeningQuestion $question = null,
        ?Collection $questions = null,
    ): array {
        $options = is_array($payload['options'] ?? null)
            ? $payload['options']
            : (is_array($group?->options) ? $group->options : []);
        $imagePath = $payload['image_path'] ?? $group?->image_path;
        $errors = $this->validateLabellingOptions($options, $imagePath);

        if ($question !== null) {
            $errors = array_merge(
                $errors,
                $this->validateLabellingAnswer(
                    $this->normalizeAnswers($payload['correct_answer'] ?? $question->correct_answer, 'map_label'),
                    $options['labels'] ?? [],
                ),
            );
        }

        return $errors;
    }

    public function buildPreviewData(ListeningQuestionGroup $group, Collection $questions): array
    {
        return [
            'type' => $this->type()->value,
            'instruction' => $group->instruction,
            'image_path' => $group->image_path,
            'options' => $group->options ?? $this->defaultOptions(),
            'questions' => $questions->map(fn (ListeningQuestion $q) => [
                'number' => $q->question_number,
                'correct_answer' => $q->correct_answer,
            ])->values()->all(),
        ];
    }
}
