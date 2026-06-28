<?php

declare(strict_types=1);

namespace App\Actions\Listening\QuestionTypes;

use App\Enums\Listening\ListeningQuestionType;
use App\Services\Listening\QuestionTypes\ListeningQuestionTypeRegistry;

class BuildQuestionTypeSchemaAction
{
    public function __construct(
        private readonly ListeningQuestionTypeRegistry $registry,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(ListeningQuestionType|string $type): array
    {
        $enum = $type instanceof ListeningQuestionType ? $type : ListeningQuestionType::from($type);
        $schema = $this->registry->get($enum);

        return [
            'type' => $schema->type->value,
            'label' => $schema->label,
            'default_layout' => $schema->defaultLayout->value,
            'default_answer_format' => $schema->defaultAnswerFormat->value,
            'form_partial' => $schema->formPartial,
            'preview_partial' => $schema->previewPartial,
            'supports_options' => $schema->supportsOptions,
            'supports_image' => $schema->supportsImage,
            'supports_template' => $schema->supportsTemplate,
            'supports_multiple_answers' => $schema->supportsMultipleAnswers,
            'required_group_fields' => $schema->requiredGroupFields,
            'required_question_fields' => $schema->requiredQuestionFields,
            'default_options' => $this->registry->serviceFor($enum)->defaultOptions(),
            'default_settings' => $this->registry->serviceFor($enum)->defaultSettings(),
            'validation_rules' => $this->registry->serviceFor($enum)->validationRules(),
        ];
    }
}
