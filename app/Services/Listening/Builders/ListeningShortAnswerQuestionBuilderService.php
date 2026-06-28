<?php

declare(strict_types=1);

namespace App\Services\Listening\Builders;

use App\Enums\Exam\ReadingCompletionAnswerRule;
use App\Enums\Listening\ListeningAnswerFormat;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Services\Listening\Builders\Concerns\ManagesListeningBuilderGroup;
use App\Services\Listening\ListeningQuestionGroupService;
use App\Services\Listening\ListeningQuestionService;
use App\Support\Listening\Builder\ListeningBuilderPresenter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListeningShortAnswerQuestionBuilderService
{
    use ManagesListeningBuilderGroup;

    public function __construct(
        private readonly ListeningQuestionService $questions,
        private readonly ListeningQuestionGroupService $groups,
        private readonly ListeningBuilderPresenter $presenter,
    ) {}

    public function assertShortAnswerGroup(ListeningQuestionGroup $group): void
    {
        if (! $group->question_type?->isShortAnswerBuilderType()) {
            throw ValidationException::withMessages([
                'question_type' => 'This question group does not use the short answer builder.',
            ]);
        }
    }

    /**
     * @return array{answer_rule: string, custom_answer_rule: ?string}
     */
    public function groupBuilderSettings(ListeningQuestionGroup $group): array
    {
        $settings = is_array($group->settings) ? $group->settings : [];

        return [
            'answer_rule' => (string) ($settings['answer_rule'] ?? ReadingCompletionAnswerRule::ThreeWords->value),
            'custom_answer_rule' => $settings['custom_answer_rule'] ?? null,
        ];
    }

    /**
     * @return Collection<int, \App\Support\Listening\Builder\ListeningBuilderQuestionView>
     */
    public function presentQuestions(ListeningQuestionGroup $group): Collection
    {
        return $this->presenter->presentQuestions($group, $this->questions->listForGroup($group));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storeQuestion(ListeningQuestionGroup $group, array $data): ListeningQuestion
    {
        $this->assertShortAnswerGroup($group);
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);

        return DB::transaction(function () use ($group, $data, $test, $section): ListeningQuestion {
            if (isset($data['answer_rule'])) {
                $this->saveAnswerRule($group, $data);
            }

            return $this->questions->syncQuestionSlot($test, $section, $group, (int) $data['question_number'], [
                'question_type' => $group->question_type?->value,
                'question_text' => trim((string) ($data['prompt'] ?? '')),
                'correct_answer' => trim((string) ($data['correct_answer'] ?? '')) !== ''
                    ? [['value' => trim((string) $data['correct_answer']), 'type' => 'text']]
                    : [],
                'accepted_answers' => $this->mapAlternatives($data['alternative_answers'] ?? []),
                'answer_format' => ListeningAnswerFormat::Text->value,
                'word_limit' => $this->wordLimitFromRule((string) ($data['answer_rule'] ?? $this->groupBuilderSettings($group)['answer_rule'])),
                'case_sensitive' => (bool) ($data['case_sensitive'] ?? false),
                'explanation' => $data['explanation'] ?? null,
                'marks' => 1,
                'is_active' => true,
                'is_required' => true,
                'meta' => ['difficulty' => $data['difficulty'] ?? 'medium'],
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateQuestion(ListeningQuestion $question, array $data): ListeningQuestion
    {
        $group = $question->group ?? abort(404);
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);

        return $this->questions->update($test, $section, $group, $question, [
            'question_number' => (int) ($data['question_number'] ?? $question->question_number),
            'question_text' => trim((string) ($data['prompt'] ?? $question->question_text)),
            'correct_answer' => trim((string) ($data['correct_answer'] ?? '')) !== ''
                ? [['value' => trim((string) $data['correct_answer']), 'type' => 'text']]
                : [],
            'accepted_answers' => $this->mapAlternatives($data['alternative_answers'] ?? []),
            'case_sensitive' => (bool) ($data['case_sensitive'] ?? $question->case_sensitive),
            'explanation' => $data['explanation'] ?? $question->explanation,
            'meta' => array_merge($question->meta ?? [], ['difficulty' => $data['difficulty'] ?? ($question->meta['difficulty'] ?? 'medium')]),
        ]);
    }

    public function deleteQuestion(ListeningQuestion $question): void
    {
        $this->questions->delete($question);
    }

    /**
     * @param  list<int>  $questionIds
     */
    public function reorderQuestions(ListeningQuestionGroup $group, array $questionIds): void
    {
        $this->questions->reorder($group, $questionIds);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveAnswerRule(ListeningQuestionGroup $group, array $data): void
    {
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);
        $settings = is_array($group->settings) ? $group->settings : [];
        $settings['answer_rule'] = (string) ($data['answer_rule'] ?? ReadingCompletionAnswerRule::ThreeWords->value);
        $settings['custom_answer_rule'] = $data['custom_answer_rule'] ?? null;
        $this->groups->updateBuilderState($test, $section, $group, ['settings' => $settings]);
        $group->refresh();
    }

    /**
     * @param  list<string>|mixed  $answers
     * @return list<array{value: string, type: string}>
     */
    private function mapAlternatives(mixed $answers): array
    {
        if (! is_array($answers)) {
            return [];
        }

        return array_values(array_map(
            fn (string $answer): array => ['value' => trim($answer), 'type' => 'text'],
            array_filter(array_map('strval', $answers), fn (string $a): bool => trim($a) !== ''),
        ));
    }

    private function wordLimitFromRule(string $rule): int
    {
        return match (ReadingCompletionAnswerRule::tryFrom(strtolower($rule))) {
            ReadingCompletionAnswerRule::OneWord, ReadingCompletionAnswerRule::OneWordOnly, ReadingCompletionAnswerRule::OneWordAndOrNumber => 1,
            ReadingCompletionAnswerRule::TwoWords => 2,
            ReadingCompletionAnswerRule::ThreeWords => 3,
            default => 3,
        };
    }
}
