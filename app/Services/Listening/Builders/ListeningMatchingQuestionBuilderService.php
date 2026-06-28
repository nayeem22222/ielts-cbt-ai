<?php

declare(strict_types=1);

namespace App\Services\Listening\Builders;

use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Services\Listening\Builders\Concerns\ManagesListeningBuilderGroup;
use App\Services\Listening\ListeningQuestionGroupService;
use App\Services\Listening\ListeningQuestionService;
use App\Support\Listening\Builder\ListeningBuilderPresenter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListeningMatchingQuestionBuilderService
{
    use ManagesListeningBuilderGroup;

    public function __construct(
        private readonly ListeningQuestionService $questions,
        private readonly ListeningQuestionGroupService $groups,
        private readonly ListeningBuilderPresenter $presenter,
    ) {}

    public function assertMatchingGroup(ListeningQuestionGroup $group): void
    {
        if (! $group->question_type?->isMatchingBuilderType()) {
            throw ValidationException::withMessages([
                'question_type' => 'This question group does not use the matching question builder.',
            ]);
        }
    }

    /**
     * @return Collection<int, \App\Support\Listening\Builder\ListeningBuilderOptionView>
     */
    public function presentOptions(ListeningQuestionGroup $group): Collection
    {
        return $this->presenter->presentMatchingOptions($group);
    }

    /**
     * @return Collection<int, \App\Support\Listening\Builder\ListeningBuilderQuestionView>
     */
    public function presentQuestions(ListeningQuestionGroup $group): Collection
    {
        return $this->presenter->presentQuestions($group, $this->questions->listForGroup($group));
    }

    /**
     * @param  array{option_key: string, option_label?: ?string}  $data
     */
    public function storeOption(ListeningQuestionGroup $group, array $data): void
    {
        $this->assertMatchingGroup($group);
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);
        $options = is_array($group->options) ? $group->options : ['items' => [], 'choices' => [], 'allow_choice_reuse' => false];
        $choices = is_array($options['choices'] ?? null) ? $options['choices'] : [];
        $key = strtoupper(trim((string) ($data['option_key'] ?? '')));

        foreach ($choices as $choice) {
            if (strtoupper((string) ($choice['key'] ?? '')) === $key) {
                throw ValidationException::withMessages(['option_key' => "Option key \"{$key}\" already exists."]);
            }
        }

        $choices[] = ['key' => $key, 'text' => trim((string) ($data['option_label'] ?? ''))];
        $options['choices'] = $choices;

        $this->groups->updateBuilderState($test, $section, $group, ['options' => $options]);
    }

    /**
     * @param  array{option_key?: string, option_label?: ?string}  $data
     */
    public function updateOption(ListeningQuestionGroup $group, int $optionIndex, array $data): void
    {
        $this->assertMatchingGroup($group);
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);
        $options = is_array($group->options) ? $group->options : ['items' => [], 'choices' => [], 'allow_choice_reuse' => false];
        $choices = is_array($options['choices'] ?? null) ? $options['choices'] : [];

        if (! isset($choices[$optionIndex])) {
            abort(404);
        }

        if (isset($data['option_key'])) {
            $choices[$optionIndex]['key'] = strtoupper(trim((string) $data['option_key']));
        }

        if (array_key_exists('option_label', $data)) {
            $choices[$optionIndex]['text'] = trim((string) ($data['option_label'] ?? ''));
        }

        $options['choices'] = array_values($choices);
        $this->groups->updateBuilderState($test, $section, $group, ['options' => $options]);
    }

    public function deleteOption(ListeningQuestionGroup $group, int $optionIndex, bool $confirmed = false): void
    {
        $this->assertMatchingGroup($group);
        $choices = is_array($group->options['choices'] ?? null) ? $group->options['choices'] : [];
        $key = (string) ($choices[$optionIndex]['key'] ?? '');

        if ($key === '') {
            abort(404);
        }

        $usage = $group->questions()->get()->filter(function (ListeningQuestion $question) use ($key): bool {
            $answers = is_array($question->correct_answer) ? $question->correct_answer : [];

            foreach ($answers as $answer) {
                if (strtoupper((string) ($answer['value'] ?? '')) === strtoupper($key)) {
                    return true;
                }
            }

            return false;
        })->count();

        if ($usage > 0 && ! $confirmed) {
            throw ValidationException::withMessages([
                'option' => "This option is used as the correct answer for {$usage} question(s). Reassign those answers or confirm deletion.",
            ]);
        }

        unset($choices[$optionIndex]);
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);
        $options = $group->options ?? [];
        $options['choices'] = array_values($choices);
        $this->groups->updateBuilderState($test, $section, $group, ['options' => $options]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storeQuestion(ListeningQuestionGroup $group, array $data): ListeningQuestion
    {
        $this->assertMatchingGroup($group);
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);
        $payload = $this->presenter->mapMatchingQuestionPayload($data);

        $question = $this->questions->syncQuestionSlot($test, $section, $group, (int) $payload['question_number'], $payload);
        $this->syncMatchingItemsFromQuestions($group);

        return $question;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateQuestion(ListeningQuestion $question, array $data): ListeningQuestion
    {
        $group = $question->group ?? abort(404);
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);
        $payload = $this->presenter->mapMatchingQuestionPayload($data);

        $question = $this->questions->update($test, $section, $group, $question, $payload);
        $this->syncMatchingItemsFromQuestions($group);

        return $question;
    }

    public function deleteQuestion(ListeningQuestion $question): void
    {
        $this->questions->delete($question);
    }

    /**
     * @param  array{option_ids?: list<int>, question_ids?: list<int>}  $payload
     */
    public function reorder(ListeningQuestionGroup $group, array $payload): void
    {
        if (isset($payload['question_ids']) && is_array($payload['question_ids'])) {
            $this->questions->reorder($group, array_map('intval', $payload['question_ids']));
        }

        if (isset($payload['option_ids']) && is_array($payload['option_ids'])) {
            $choices = is_array($group->options['choices'] ?? null) ? $group->options['choices'] : [];
            $reordered = [];

            foreach ($payload['option_ids'] as $index) {
                if (isset($choices[(int) $index])) {
                    $reordered[] = $choices[(int) $index];
                }
            }

            $test = $this->listeningTestForGroup($group);
            $section = $group->section ?? abort(404);
            $options = $group->options ?? [];
            $options['choices'] = $reordered;
            $this->groups->updateBuilderState($test, $section, $group, ['options' => $options]);
        }
    }

    /**
     * @param  array{options_text?: string, questions_text?: string}  $payload
     * @return array{options: int, questions: int}
     */
    public function bulkImport(ListeningQuestionGroup $group, array $payload): array
    {
        $optionCount = 0;
        $questionCount = 0;
        $optionsText = trim((string) ($payload['options_text'] ?? ''));

        foreach (preg_split('/\R/', $optionsText) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line, 2));
            $this->storeOption($group, [
                'option_key' => $parts[0],
                'option_label' => $parts[1] ?? '',
            ]);
            $group->refresh();
            $optionCount++;
        }

        foreach (preg_split('/\R/', trim((string) ($payload['questions_text'] ?? ''))) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $this->storeQuestion($group, [
                'question_number' => (int) ($parts[0] ?? 0),
                'prompt' => (string) ($parts[1] ?? ''),
                'correct_answer' => (string) ($parts[2] ?? ''),
            ]);
            $questionCount++;
        }

        return ['options' => $optionCount, 'questions' => $questionCount];
    }

    private function syncMatchingItemsFromQuestions(ListeningQuestionGroup $group): void
    {
        $group->load('questions');
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);
        $options = is_array($group->options) ? $group->options : ['items' => [], 'choices' => [], 'allow_choice_reuse' => false];
        $options['items'] = $group->questions
            ->sortBy('question_number')
            ->map(fn (ListeningQuestion $question): array => [
                'key' => (string) $question->question_number,
                'text' => (string) ($question->question_text ?? ''),
            ])
            ->values()
            ->all();

        $this->groups->updateBuilderState($test, $section, $group, ['options' => $options]);
        $group->refresh();
    }
}
