<?php

declare(strict_types=1);

namespace App\DTOs\Listening\QuestionTypes;

use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;

readonly class QuestionTypeSchemaData
{
    /**
     * @param  list<string>  $requiredGroupFields
     * @param  list<string>  $requiredQuestionFields
     */
    public function __construct(
        public ListeningQuestionType $type,
        public string $label,
        public ListeningLayoutType $defaultLayout,
        public ListeningAnswerFormat $defaultAnswerFormat,
        public string $serviceClass,
        public string $formPartial,
        public string $previewPartial,
        public bool $supportsOptions,
        public bool $supportsImage,
        public bool $supportsTemplate,
        public bool $supportsMultipleAnswers,
        public array $requiredGroupFields = [],
        public array $requiredQuestionFields = [],
    ) {}
}
