<?php

declare(strict_types=1);

namespace App\Services\Listening\QuestionTypes;

use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use Illuminate\Database\Eloquent\Collection;

class DiagramLabellingQuestionTypeService extends MapLabellingQuestionTypeService
{
    public function type(): ListeningQuestionType
    {
        return ListeningQuestionType::DiagramLabelling;
    }

    public function label(): string
    {
        return 'Diagram Labelling';
    }

    public function schema(): array
    {
        $schema = parent::schema();
        $schema['default_layout'] = ListeningLayoutType::Diagram->value;

        return $schema;
    }

    public function normalizePayload(array $payload, ?ListeningQuestionGroup $group = null, ?ListeningQuestion $question = null): array
    {
        $payload = parent::normalizePayload($payload, $group, $question);

        if (isset($payload['correct_answer'])) {
            $payload['correct_answer'] = array_values(array_map(function (array $item): array {
                $item['type'] = 'diagram_label';

                return $item;
            }, $this->normalizeAnswers($payload['correct_answer'], 'diagram_label')));
        }

        $payload['layout_type'] = $payload['layout_type'] ?? ListeningLayoutType::Diagram->value;

        return $payload;
    }

    public function validatePayload(
        array $payload,
        ?ListeningQuestionGroup $group = null,
        ?ListeningQuestion $question = null,
        ?Collection $questions = null,
    ): array {
        $errors = parent::validatePayload($payload, $group, $question, $questions);

        if ($question !== null) {
            $options = is_array($group?->options) ? $group->options : [];
            $errors = array_merge(
                $errors,
                $this->validateLabellingAnswer(
                    $this->normalizeAnswers($payload['correct_answer'] ?? $question->correct_answer, 'diagram_label'),
                    $options['labels'] ?? [],
                    'diagram_label',
                ),
            );
        }

        return array_unique($errors);
    }

    public function buildPreviewData(ListeningQuestionGroup $group, Collection $questions): array
    {
        $data = parent::buildPreviewData($group, $questions);
        $data['type'] = $this->type()->value;

        return $data;
    }
}
