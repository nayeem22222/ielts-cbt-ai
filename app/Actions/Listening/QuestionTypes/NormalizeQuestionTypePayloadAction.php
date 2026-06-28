<?php

declare(strict_types=1);

namespace App\Actions\Listening\QuestionTypes;

use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Services\Listening\QuestionTypes\ListeningQuestionTypeRegistry;

class NormalizeQuestionTypePayloadAction
{
    public function __construct(
        private readonly ListeningQuestionTypeRegistry $registry,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function execute(
        array $payload,
        ListeningQuestionType $type,
        ?ListeningQuestionGroup $group = null,
        ?ListeningQuestion $question = null,
    ): array {
        if (! $this->registry->isEnabled($type)) {
            return $payload;
        }

        $normalized = $this->registry->serviceFor($type)->normalizePayload($payload, $group, $question);

        if ($group === null && $question === null) {
            $schema = $this->registry->get($type);
            $normalized['layout_type'] = $normalized['layout_type'] ?? $schema->defaultLayout->value;

            if ($question === null && ! isset($normalized['answer_format']) && $schema->defaultAnswerFormat) {
                // group-level only
            }
        }

        $isQuestionPayload = $question !== null
            || array_key_exists('question_number', $payload)
            || array_key_exists('question_text', $payload);

        if (
            ! $isQuestionPayload
            && $question === null
            && ! isset($normalized['options'])
            && $this->registry->supportsOptions($type)
        ) {
            $defaults = $this->registry->serviceFor($type)->defaultOptions();

            if ($defaults !== null && empty($payload['options'])) {
                $normalized['options'] = $defaults;
            }
        }

        if (! isset($normalized['settings'])) {
            $normalized['settings'] = $this->registry->serviceFor($type)->defaultSettings();
        }

        return $normalized;
    }
}
