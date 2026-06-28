<?php

declare(strict_types=1);

namespace App\Services\Listening\QuestionTypes;

use App\DTOs\Listening\QuestionTypes\QuestionTypeSchemaData;
use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningLayoutType;
use App\Enums\Listening\ListeningQuestionType;
use InvalidArgumentException;

class ListeningQuestionTypeRegistry
{
    /** @var array<string, class-string<BaseListeningQuestionTypeService>> */
    private array $services = [
        ListeningQuestionType::MCQ->value => McqQuestionTypeService::class,
        ListeningQuestionType::MultipleAnswer->value => MultipleAnswerQuestionTypeService::class,
        ListeningQuestionType::Matching->value => MatchingQuestionTypeService::class,
        ListeningQuestionType::MapLabelling->value => MapLabellingQuestionTypeService::class,
        ListeningQuestionType::PlanLabelling->value => PlanLabellingQuestionTypeService::class,
        ListeningQuestionType::DiagramLabelling->value => DiagramLabellingQuestionTypeService::class,
        ListeningQuestionType::FormCompletion->value => FormCompletionQuestionTypeService::class,
        ListeningQuestionType::NoteCompletion->value => NoteCompletionQuestionTypeService::class,
        ListeningQuestionType::TableCompletion->value => TableCompletionQuestionTypeService::class,
        ListeningQuestionType::FlowchartCompletion->value => FlowchartCompletionQuestionTypeService::class,
        ListeningQuestionType::SentenceCompletion->value => SentenceCompletionQuestionTypeService::class,
        ListeningQuestionType::SummaryCompletion->value => SummaryCompletionQuestionTypeService::class,
        ListeningQuestionType::ShortAnswer->value => ShortAnswerQuestionTypeService::class,
    ];

    /**
     * @return list<ListeningQuestionType>
     */
    public function all(): array
    {
        $enabled = config('listening.question_types.enabled', []);

        return array_values(array_filter(
            ListeningQuestionType::cases(),
            fn (ListeningQuestionType $type): bool => in_array($type->value, $enabled, true),
        ));
    }

    public function get(string|ListeningQuestionType $type): QuestionTypeSchemaData
    {
        $enum = $type instanceof ListeningQuestionType ? $type : ListeningQuestionType::from($this->resolveType($type));
        $service = $this->serviceFor($enum);
        $schema = $service->schema();

        return new QuestionTypeSchemaData(
            type: $enum,
            label: $service->label(),
            defaultLayout: ListeningLayoutType::from((string) ($schema['default_layout'] ?? ListeningLayoutType::Default->value)),
            defaultAnswerFormat: ListeningAnswerFormat::from((string) ($schema['default_answer_format'] ?? ListeningAnswerFormat::Text->value)),
            serviceClass: $this->services[$enum->value],
            formPartial: $this->formPartialFor($enum),
            previewPartial: $this->previewPartialFor($enum),
            supportsOptions: (bool) ($schema['supports_options'] ?? false),
            supportsImage: (bool) ($schema['supports_image'] ?? false),
            supportsTemplate: (bool) ($schema['supports_template'] ?? false),
            supportsMultipleAnswers: (bool) ($schema['supports_multiple_answers'] ?? false),
            requiredGroupFields: $schema['required_group_fields'] ?? [],
            requiredQuestionFields: $schema['required_question_fields'] ?? [],
        );
    }

    public function serviceFor(ListeningQuestionType $type): BaseListeningQuestionTypeService
    {
        $this->assertEnabled($type);
        $class = $this->services[$type->value] ?? null;

        if ($class === null) {
            throw new InvalidArgumentException("No service registered for question type [{$type->value}].");
        }

        return app($class);
    }

    /**
     * @return array<string, mixed>
     */
    public function schemaFor(ListeningQuestionType $type): array
    {
        return $this->serviceFor($type)->schema();
    }

    public function defaultLayoutFor(ListeningQuestionType $type): ListeningLayoutType
    {
        return $this->get($type)->defaultLayout;
    }

    public function defaultAnswerFormatFor(ListeningQuestionType $type): ListeningAnswerFormat
    {
        return $this->get($type)->defaultAnswerFormat;
    }

    public function formPartialFor(ListeningQuestionType $type): string
    {
        $slug = str_replace('_', '-', $type->value);

        if ($type === ListeningQuestionType::MapLabelling
            || $type === ListeningQuestionType::PlanLabelling
            || $type === ListeningQuestionType::DiagramLabelling) {
            $file = match ($type) {
                ListeningQuestionType::MapLabelling => 'map-form',
                ListeningQuestionType::PlanLabelling => 'plan-form',
                ListeningQuestionType::DiagramLabelling => 'diagram-form',
                default => 'map-form',
            };

            return "admin.listening.question-types.labelling.{$file}";
        }

        if (in_array($type, [
            ListeningQuestionType::FormCompletion,
            ListeningQuestionType::NoteCompletion,
            ListeningQuestionType::SentenceCompletion,
            ListeningQuestionType::SummaryCompletion,
        ], true)) {
            return 'admin.listening.question-types.completion.form';
        }

        if ($type === ListeningQuestionType::TableCompletion) {
            return 'admin.listening.question-types.table-completion.form';
        }

        if ($type === ListeningQuestionType::FlowchartCompletion) {
            return 'admin.listening.question-types.flowchart-completion.form';
        }

        return "admin.listening.question-types.{$slug}.form";
    }

    public function previewPartialFor(ListeningQuestionType $type): string
    {
        if (in_array($type, [
            ListeningQuestionType::MapLabelling,
            ListeningQuestionType::PlanLabelling,
            ListeningQuestionType::DiagramLabelling,
        ], true)) {
            return 'admin.listening.question-types.labelling.preview';
        }

        if (in_array($type, [
            ListeningQuestionType::FormCompletion,
            ListeningQuestionType::NoteCompletion,
            ListeningQuestionType::SentenceCompletion,
            ListeningQuestionType::SummaryCompletion,
        ], true)) {
            return 'admin.listening.question-types.completion.preview';
        }

        if ($type === ListeningQuestionType::TableCompletion) {
            return 'admin.listening.question-types.table-completion.preview';
        }

        if ($type === ListeningQuestionType::FlowchartCompletion) {
            return 'admin.listening.question-types.flowchart-completion.preview';
        }

        $slug = str_replace('_', '-', $type->value);

        return "admin.listening.question-types.{$slug}.preview";
    }

    public function supportsOptions(ListeningQuestionType $type): bool
    {
        return $this->get($type)->supportsOptions;
    }

    public function supportsImage(ListeningQuestionType $type): bool
    {
        return $this->get($type)->supportsImage;
    }

    public function supportsTemplate(ListeningQuestionType $type): bool
    {
        return $this->get($type)->supportsTemplate;
    }

    public function supportsMultipleAnswers(ListeningQuestionType $type): bool
    {
        return $this->get($type)->supportsMultipleAnswers;
    }

    public function isEnabled(ListeningQuestionType $type): bool
    {
        return in_array($type->value, config('listening.question_types.enabled', []), true);
    }

    private function resolveType(string $type): string
    {
        if (! in_array($type, config('listening.question_types.enabled', []), true)) {
            throw new InvalidArgumentException("Question type [{$type}] is not enabled.");
        }

        return $type;
    }

    private function assertEnabled(ListeningQuestionType $type): void
    {
        if (! $this->isEnabled($type)) {
            throw new InvalidArgumentException("Question type [{$type->value}] is not enabled.");
        }
    }
}
