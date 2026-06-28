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

class ListeningObjectiveQuestionBuilderService
{
    use ManagesListeningBuilderGroup;

    public function __construct(
        private readonly ListeningQuestionService $questions,
        private readonly ListeningQuestionGroupService $groups,
        private readonly ListeningBuilderPresenter $presenter,
    ) {}

    public function assertObjectiveGroup(ListeningQuestionGroup $group): void
    {
        if (! $group->question_type?->isObjectiveBuilderType()) {
            throw ValidationException::withMessages([
                'question_type' => 'This question group does not use the objective question builder.',
            ]);
        }
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
        $this->assertObjectiveGroup($group);
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);

        return DB::transaction(function () use ($group, $data, $test, $section): ListeningQuestion {
            if (isset($data['options']) && is_array($data['options'])) {
                $this->groups->update($test, $section, $group, [
                    'options' => array_values(array_map(fn (array $option): array => [
                        'key' => strtoupper(trim((string) ($option['option_key'] ?? ''))),
                        'text' => trim((string) ($option['option_label'] ?? '')),
                    ], $data['options'])),
                ]);
                $group->refresh();
            }

            $payload = $this->presenter->mapObjectiveQuestionPayload($group, $data);

            return $this->questions->syncQuestionSlot(
                $test,
                $section,
                $group,
                (int) $payload['question_number'],
                $payload,
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateQuestion(ListeningQuestion $question, array $data): ListeningQuestion
    {
        $group = $question->group ?? abort(404);
        $this->assertObjectiveGroup($group);
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);

        return DB::transaction(function () use ($question, $group, $data, $test, $section): ListeningQuestion {
            if (isset($data['options']) && is_array($data['options'])) {
                $this->groups->update($test, $section, $group, [
                    'options' => array_values(array_map(fn (array $option): array => [
                        'key' => strtoupper(trim((string) ($option['option_key'] ?? ''))),
                        'text' => trim((string) ($option['option_label'] ?? '')),
                    ], $data['options'])),
                ]);
                $group->refresh();
            }

            $payload = $this->presenter->mapObjectiveQuestionPayload($group, $data);

            return $this->questions->update($test, $section, $group, $question, $payload);
        });
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
     * @param  array{import_text: string}  $data
     */
    public function bulkImport(ListeningQuestionGroup $group, array $data): int
    {
        $this->assertObjectiveGroup($group);
        $lines = preg_split('/\R/', trim((string) ($data['import_text'] ?? ''))) ?: [];
        $created = 0;

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $questionNumber = (int) ($parts[0] ?? 0);
            $prompt = (string) ($parts[1] ?? '');
            $correct = (string) ($parts[2] ?? '');

            if ($questionNumber < 1 || $prompt === '') {
                continue;
            }

            $this->storeQuestion($group, [
                'question_number' => $questionNumber,
                'prompt' => $prompt,
                'correct_answer' => $correct,
            ]);
            $created++;
        }

        return $created;
    }

    /**
     * @return Collection<int, \App\Support\Listening\Builder\ListeningBuilderOptionView>
     */
    public function groupOptions(ListeningQuestionGroup $group): Collection
    {
        return $this->presenter->presentGroupMcqOptions($group);
    }

    /**
     * @param  array{option_key: string, option_label?: ?string}  $data
     */
    public function storeGroupOption(ListeningQuestionGroup $group, array $data): void
    {
        $this->assertObjectiveGroup($group);
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);
        $options = is_array($group->options) && array_is_list($group->options) ? $group->options : [];
        $key = strtoupper(trim((string) ($data['option_key'] ?? '')));

        foreach ($options as $option) {
            if (strtoupper((string) ($option['key'] ?? '')) === $key) {
                throw ValidationException::withMessages(['option_key' => "Option key \"{$key}\" already exists."]);
            }
        }

        $options[] = [
            'key' => $key,
            'text' => trim((string) ($data['option_label'] ?? '')),
        ];

        $this->groups->update($test, $section, $group, ['options' => $options]);
    }

    /**
     * @param  array{option_key?: string, option_label?: ?string}  $data
     */
    public function updateGroupOption(ListeningQuestionGroup $group, int $optionIndex, array $data): void
    {
        $this->assertObjectiveGroup($group);
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);
        $options = is_array($group->options) && array_is_list($group->options) ? $group->options : [];

        if (! isset($options[$optionIndex])) {
            abort(404);
        }

        if (isset($data['option_key'])) {
            $options[$optionIndex]['key'] = strtoupper(trim((string) $data['option_key']));
        }

        if (array_key_exists('option_label', $data)) {
            $options[$optionIndex]['text'] = trim((string) ($data['option_label'] ?? ''));
        }

        $this->groups->update($test, $section, $group, ['options' => array_values($options)]);
    }

    public function deleteGroupOption(ListeningQuestionGroup $group, int $optionIndex): void
    {
        $this->assertObjectiveGroup($group);
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);
        $options = is_array($group->options) && array_is_list($group->options) ? $group->options : [];
        unset($options[$optionIndex]);
        $this->groups->update($test, $section, $group, ['options' => array_values($options)]);
    }
}
