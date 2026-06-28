<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Listening\Concerns;

use Illuminate\Http\Request;

trait InteractsWithListeningQuestionBuilder
{
    protected function logListeningQuestionPayload(Request $request, string $action): void
    {
        logger()->info('Listening question payload', [
            'action' => $action,
            'payload' => $request->all(),
        ]);
    }

    /**
     * @return list<string|object>
     */
    protected function textAnswerRules(bool $required = true): array
    {
        $allowDraft = (bool) config('listening.questions.allow_draft_without_answer', true);

        return [
            ($required && ! $allowDraft) ? 'required' : 'nullable',
            'string',
            'max:500',
        ];
    }

    /**
     * @return array<string, list<string|object>>
     */
    protected function alternativeAnswerRules(): array
    {
        return [
            'alternative_answers' => ['nullable', 'array'],
            'alternative_answers.*' => ['nullable', 'string', 'max:500'],
        ];
    }
}
