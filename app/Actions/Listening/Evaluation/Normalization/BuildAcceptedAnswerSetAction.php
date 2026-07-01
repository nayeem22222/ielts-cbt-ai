<?php

declare(strict_types=1);

namespace App\Actions\Listening\Evaluation\Normalization;

use App\Models\Listening\ListeningQuestion;

class BuildAcceptedAnswerSetAction
{
    /**
     * @return array{correct: list<array<string, mixed>>, accepted: list<array<string, mixed>>, all: list<array<string, mixed>>}
     */
    public function execute(ListeningQuestion $question): array
    {
        $correct = is_array($question->correct_answer) ? $question->correct_answer : [];
        $accepted = is_array($question->accepted_answers) ? $question->accepted_answers : [];
        $seen = [];
        $all = [];

        foreach (array_merge($correct, $accepted) as $answer) {
            if (! is_array($answer)) {
                continue;
            }

            $key = ((string) ($answer['type'] ?? 'text')).'|'.((string) ($answer['value'] ?? ''));

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $all[] = $answer;
        }

        return compact('correct', 'accepted', 'all');
    }
}
