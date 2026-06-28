<?php

declare(strict_types=1);

namespace App\Actions\Listening\QuestionTypes;

use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Services\Listening\QuestionTypes\ListeningQuestionTypeRegistry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class ValidateQuestionTypePayloadAction
{
    public function __construct(
        private readonly ListeningQuestionTypeRegistry $registry,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @param  Collection<int, ListeningQuestion>|null  $questions
     * @return list<string>
     */
    public function execute(
        string $context,
        array $payload,
        ListeningQuestionType $type,
        ?ListeningQuestionGroup $group = null,
        ?ListeningQuestion $question = null,
        ?Collection $questions = null,
    ): array {
        if (! $this->registry->isEnabled($type)) {
            return ["Question type [{$type->value}] is not supported."];
        }

        $service = $this->registry->serviceFor($type);

        if ($context === 'question' && $question === null) {
            $question = new ListeningQuestion([
                'question_number' => (int) ($payload['question_number'] ?? 0),
                'question_text' => $payload['question_text'] ?? null,
                'word_limit' => $payload['word_limit'] ?? null,
                'correct_answer' => $payload['correct_answer'] ?? null,
            ]);
        }

        return $service->validatePayload(
            $payload,
            $context === 'group' ? $group : ($group ?? $question?->group),
            $context === 'question' ? $question : null,
            $questions ?? ($group?->questions ?? null),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  Collection<int, ListeningQuestion>|null  $questions
     */
    public function executeOrFail(
        string $context,
        array $payload,
        ListeningQuestionType $type,
        ?ListeningQuestionGroup $group = null,
        ?ListeningQuestion $question = null,
        ?Collection $questions = null,
    ): void {
        $errors = $this->execute($context, $payload, $type, $group, $question, $questions);

        if ($errors === []) {
            return;
        }

        $field = $context === 'question' ? 'correct_answer' : 'question_type';

        throw ValidationException::withMessages([$field => $errors[0]]);
    }
}
