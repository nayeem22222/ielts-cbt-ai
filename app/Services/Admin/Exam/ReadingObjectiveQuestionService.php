<?php

declare(strict_types=1);

namespace App\Services\Admin\Exam;

use App\Enums\Exam\OfficialReadingQuestionType;
use App\Models\ReadingCorrectAnswer;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingQuestionOption;
use App\Models\ReadingTest;
use App\Support\Reading\ObjectiveBulkImportParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReadingObjectiveQuestionService
{
    public function loadGroupForBuilder(ReadingQuestionGroup $group): ReadingQuestionGroup
    {
        return $group->load([
            'passage.test',
            'questions' => fn ($query) => $query
                ->with(['options', 'correctAnswers'])
                ->orderBy('sort_order'),
        ]);
    }

    public function assertObjectiveGroup(ReadingQuestionGroup $group): void
    {
        if (! $group->question_type?->isObjectiveBuilderType()) {
            throw ValidationException::withMessages([
                'question_type' => 'This question group does not use the objective question builder.',
            ]);
        }
    }

    public function readingTestForGroup(ReadingQuestionGroup $group): ReadingTest
    {
        /** @var ReadingPassage $passage */
        $passage = $group->passage()->firstOrFail();

        return $passage->test()->firstOrFail();
    }

    /**
     * @param  array{
     *     question_number: int,
     *     prompt: string,
     *     correct_answer?: ?string,
     *     correct_answers?: list<string>|null,
     *     explanation?: ?string,
     *     difficulty?: ?string,
     *     options?: list<array{option_key?: string, option_label: string}>|null,
     *     sort_order?: ?int
     * }  $data
     */
    public function storeQuestion(ReadingQuestionGroup $group, array $data): ReadingQuestion
    {
        $this->assertObjectiveGroup($group);

        return DB::transaction(function () use ($group, $data): ReadingQuestion {
            $questionNumber = (int) $data['question_number'];
            $this->assertQuestionNumberIsValid($group, $questionNumber);

            $sortOrder = (int) ($data['sort_order'] ?? ((int) $group->questions()->max('sort_order') + 1));

            /** @var ReadingQuestion $question */
            $question = $group->questions()->create([
                'question_number' => $questionNumber,
                'prompt' => (string) $data['prompt'],
                'explanation' => $data['explanation'] ?? null,
                'difficulty' => $data['difficulty'] ?? 'medium',
                'sort_order' => max(1, $sortOrder),
                'marks' => 1,
            ]);

            if ($group->question_type->usesPerQuestionOptions()) {
                $this->syncQuestionOptions($question, $data['options'] ?? []);
            }

            $this->syncCorrectAnswers($group, $question, $data);

            return $question->load(['options', 'correctAnswers']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateQuestion(ReadingQuestion $question, array $data): ReadingQuestion
    {
        return DB::transaction(function () use ($question, $data): ReadingQuestion {
            $group = $question->group()->firstOrFail();
            $this->assertObjectiveGroup($group);

            if (isset($data['question_number'])) {
                $questionNumber = (int) $data['question_number'];
                $this->assertQuestionNumberIsValid($group, $questionNumber, $question);
                $question->question_number = $questionNumber;
            }

            if (isset($data['prompt'])) {
                $question->prompt = (string) $data['prompt'];
            }

            if (array_key_exists('explanation', $data)) {
                $question->explanation = $data['explanation'];
            }

            if (isset($data['difficulty'])) {
                $question->difficulty = (string) $data['difficulty'];
            }

            if (isset($data['sort_order'])) {
                $question->sort_order = max(1, (int) $data['sort_order']);
            }

            $question->save();

            if ($group->question_type->usesPerQuestionOptions() && array_key_exists('options', $data)) {
                $this->syncQuestionOptions($question, $data['options'] ?? []);
            }

            if (
                array_key_exists('correct_answer', $data)
                || array_key_exists('correct_answers', $data)
            ) {
                $this->syncCorrectAnswers($group, $question, $data);
            }

            return $question->load(['options', 'correctAnswers']);
        });
    }

    public function deleteQuestion(ReadingQuestion $question): void
    {
        DB::transaction(function () use ($question): void {
            $group = $question->group()->firstOrFail();
            $this->assertObjectiveGroup($group);
            $question->delete();
        });
    }

    public function duplicateQuestion(ReadingQuestion $question): ReadingQuestion
    {
        return DB::transaction(function () use ($question): ReadingQuestion {
            $group = $question->group()->firstOrFail();
            $this->assertObjectiveGroup($group);

            $question->load(['options', 'correctAnswers']);

            $sortOrder = (int) $group->questions()->max('sort_order') + 1;

            /** @var ReadingQuestion $copy */
            $copy = $group->questions()->create([
                'question_number' => 0,
                'prompt' => $question->prompt,
                'explanation' => $question->explanation,
                'difficulty' => $question->difficulty,
                'sort_order' => $sortOrder,
                'marks' => $question->marks,
                'metadata' => array_merge($question->metadata ?? [], [
                    'duplicate_draft' => true,
                    'source_question_id' => $question->id,
                ]),
            ]);

            foreach ($question->options as $option) {
                $copy->options()->create($option->only([
                    'option_key',
                    'option_label',
                    'sort_order',
                ]));
            }

            foreach ($question->correctAnswers as $answer) {
                $copy->correctAnswers()->create($answer->only([
                    'answer',
                    'answer_json',
                    'matching_key',
                ]));
            }

            return $copy->load(['options', 'correctAnswers']);
        });
    }

    /**
     * @param  array{option_key?: string, option_label: string, sort_order?: ?int}  $data
     */
    public function storeOption(ReadingQuestion $question, array $data): ReadingQuestionOption
    {
        return DB::transaction(function () use ($question, $data): ReadingQuestionOption {
            $group = $question->group()->firstOrFail();
            $this->assertObjectiveGroup($group);
            $this->assertMcqGroup($group);

            $key = trim((string) ($data['option_key'] ?? $this->nextOptionKey($question)));
            $this->assertQuestionOptionKeyIsUnique($question, $key);

            if (trim((string) ($data['option_label'] ?? '')) === '') {
                throw ValidationException::withMessages([
                    'option_label' => 'Option text cannot be empty.',
                ]);
            }

            /** @var ReadingQuestionOption $option */
            $option = $question->options()->create([
                'option_key' => $key,
                'option_label' => (string) $data['option_label'],
                'sort_order' => max(1, (int) ($data['sort_order'] ?? ((int) $question->options()->max('sort_order') + 1))),
            ]);

            return $option->refresh();
        });
    }

    /**
     * @param  array{option_key?: string, option_label?: string, sort_order?: ?int}  $data
     */
    public function updateOption(ReadingQuestionOption $option, array $data): ReadingQuestionOption
    {
        return DB::transaction(function () use ($option, $data): ReadingQuestionOption {
            $question = $this->objectiveOptionQuestion($option);
            $group = $question->group()->firstOrFail();
            $this->assertObjectiveGroup($group);
            $this->assertMcqGroup($group);

            $originalKey = $option->option_key;
            $newKey = isset($data['option_key']) ? trim((string) $data['option_key']) : $originalKey;

            if ($newKey !== $originalKey) {
                $this->assertQuestionOptionKeyIsUnique($question, $newKey, $option->id);
                $this->rewriteQuestionCorrectAnswersForOptionKey($question, $originalKey, $newKey);
            }

            if (array_key_exists('option_label', $data) && trim((string) $data['option_label']) === '') {
                throw ValidationException::withMessages([
                    'option_label' => 'Option text cannot be empty.',
                ]);
            }

            $option->forceFill([
                'option_key' => $newKey,
                'option_label' => array_key_exists('option_label', $data)
                    ? (string) $data['option_label']
                    : $option->option_label,
                'sort_order' => isset($data['sort_order'])
                    ? max(1, (int) $data['sort_order'])
                    : $option->sort_order,
            ])->save();

            return $option->refresh();
        });
    }

    public function deleteOption(ReadingQuestionOption $option, bool $confirmed = false): void
    {
        DB::transaction(function () use ($option, $confirmed): void {
            $question = $this->objectiveOptionQuestion($option);
            $group = $question->group()->firstOrFail();
            $this->assertObjectiveGroup($group);
            $this->assertMcqGroup($group);

            if ($question->options()->count() <= 2) {
                throw ValidationException::withMessages([
                    'option' => 'A multiple choice question must keep at least two options.',
                ]);
            }

            $usageCount = $this->questionOptionUsageCount($question, $option->option_key);

            if ($usageCount > 0 && ! $confirmed) {
                throw ValidationException::withMessages([
                    'option' => "This option is used as a correct answer. Reassign or confirm deletion.",
                ]);
            }

            $option->delete();
        });
    }

    /**
     * @param  list<int>  $questionIds
     */
    public function reorderQuestions(ReadingQuestionGroup $group, array $questionIds): void
    {
        $this->assertObjectiveGroup($group);

        DB::transaction(function () use ($group, $questionIds): void {
            $questions = $group->questions()->whereIn('id', $questionIds)->get()->keyBy('id');

            if ($questions->count() !== count($questionIds)) {
                throw ValidationException::withMessages([
                    'question_ids' => 'One or more questions do not belong to this question group.',
                ]);
            }

            foreach (array_values($questionIds) as $index => $id) {
                /** @var ReadingQuestion $question */
                $question = $questions->get($id);
                $question->forceFill(['sort_order' => $index + 1])->save();
            }
        });
    }

    /**
     * @param  list<int>  $optionIds
     */
    public function reorderOptions(ReadingQuestion $question, array $optionIds): void
    {
        DB::transaction(function () use ($question, $optionIds): void {
            $group = $question->group()->firstOrFail();
            $this->assertObjectiveGroup($group);
            $this->assertMcqGroup($group);

            $options = $question->options()->whereIn('id', $optionIds)->get()->keyBy('id');

            if ($options->count() !== count($optionIds)) {
                throw ValidationException::withMessages([
                    'option_ids' => 'One or more options do not belong to this question.',
                ]);
            }

            foreach (array_values($optionIds) as $index => $id) {
                /** @var ReadingQuestionOption $option */
                $option = $options->get($id);
                $option->forceFill(['sort_order' => $index + 1])->save();
            }
        });
    }

  /**
     * @param  array{import_text?: ?string}  $data
     */
    public function bulkImport(ReadingQuestionGroup $group, array $data): int
    {
        $this->assertObjectiveGroup($group);

        $text = trim((string) ($data['import_text'] ?? ''));

        if ($text === '') {
            throw ValidationException::withMessages([
                'import_text' => 'Import text is required.',
            ]);
        }

        return DB::transaction(function () use ($group, $text): int {
            $created = 0;

            foreach (ObjectiveBulkImportParser::parse($text, $group->question_type) as $row) {
                if ($group->questions()->where('question_number', $row['question_number'])->exists()) {
                    continue;
                }

                $this->storeQuestion($group, $row);
                $created++;
            }

            return $created;
        });
    }

    /**
     * @param  list<array{option_key?: string, option_label?: string}>  $options
     */
    private function syncQuestionOptions(ReadingQuestion $question, array $options): void
    {
        if (count($options) < 2) {
            throw ValidationException::withMessages([
                'options' => 'Multiple choice questions require at least two options.',
            ]);
        }

        $question->options()->delete();

        foreach (array_values($options) as $index => $option) {
            $label = trim((string) ($option['option_label'] ?? ''));

            if ($label === '') {
                throw ValidationException::withMessages([
                    'options' => 'Option text cannot be empty.',
                ]);
            }

            $key = trim((string) ($option['option_key'] ?? ObjectiveBulkImportParser::optionKeyForIndex($index)));

            $question->options()->create([
                'option_key' => $key,
                'option_label' => $label,
                'sort_order' => $index + 1,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncCorrectAnswers(ReadingQuestionGroup $group, ReadingQuestion $question, array $data): void
    {
        $type = $group->question_type;
        $question->correctAnswers()->delete();

        if ($type->allowsMultipleCorrectAnswers()) {
            $answers = array_values(array_filter(array_map(
                fn ($value) => strtoupper(trim((string) $value)),
                $data['correct_answers'] ?? [],
            )));

            if ($answers === []) {
                throw ValidationException::withMessages([
                    'correct_answers' => 'Select at least one correct answer.',
                ]);
            }

            $this->assertCorrectAnswersExistForQuestion($question, $answers);

            $question->correctAnswers()->create([
                'answer' => $answers[0],
                'answer_json' => $answers,
                'matching_key' => null,
            ]);

            return;
        }

        $answer = strtoupper(trim((string) ($data['correct_answer'] ?? '')));

        if ($answer === '') {
            throw ValidationException::withMessages([
                'correct_answer' => 'Correct answer is required.',
            ]);
        }

        $choices = $type->objectiveAnswerChoices();

        if ($choices !== null) {
            if (! in_array($answer, $choices, true)) {
                throw ValidationException::withMessages([
                    'correct_answer' => 'Correct answer must be one of: '.implode(', ', $choices).'.',
                ]);
            }
        } else {
            $this->assertCorrectAnswersExistForQuestion($question, [$answer]);
        }

        $question->correctAnswers()->create([
            'answer' => $answer,
            'answer_json' => null,
            'matching_key' => null,
        ]);
    }

    /**
     * @param  list<string>  $answers
     */
    private function assertCorrectAnswersExistForQuestion(ReadingQuestion $question, array $answers): void
    {
        $keys = $question->options()->pluck('option_key')->all();

        foreach ($answers as $answer) {
            if (! in_array($answer, $keys, true)) {
                throw ValidationException::withMessages([
                    'correct_answer' => "Correct answer [{$answer}] must match an existing option key.",
                ]);
            }
        }
    }

    private function assertQuestionNumberIsValid(
        ReadingQuestionGroup $group,
        int $questionNumber,
        ?ReadingQuestion $except = null,
    ): void {
        if ($questionNumber < 1) {
            throw ValidationException::withMessages([
                'question_number' => 'Question number is required.',
            ]);
        }

        if ($group->start_question !== null && $questionNumber < $group->start_question) {
            throw ValidationException::withMessages([
                'question_number' => "Question number must be at least {$group->start_question} for this group.",
            ]);
        }

        if ($group->end_question !== null && $questionNumber > $group->end_question) {
            throw ValidationException::withMessages([
                'question_number' => "Question number must not exceed {$group->end_question} for this group.",
            ]);
        }

        $groupQuery = $group->questions()->where('question_number', $questionNumber)->where('question_number', '>', 0);

        if ($except !== null) {
            $groupQuery->whereKeyNot($except->id);
        }

        if ($groupQuery->exists()) {
            throw ValidationException::withMessages([
                'question_number' => "Question number {$questionNumber} already exists in this group.",
            ]);
        }

        $test = $this->readingTestForGroup($group);
        $testQuery = $test->questions()->where('question_number', $questionNumber)->where('question_number', '>', 0);

        if ($except !== null) {
            $testQuery->whereKeyNot($except->id);
        }

        if ($testQuery->exists()) {
            throw ValidationException::withMessages([
                'question_number' => "Question number {$questionNumber} is already used in this reading test.",
            ]);
        }
    }

    private function assertMcqGroup(ReadingQuestionGroup $group): void
    {
        if (! $group->question_type->usesPerQuestionOptions()) {
            throw ValidationException::withMessages([
                'question_type' => 'Options can only be managed for multiple choice question groups.',
            ]);
        }
    }

    private function assertQuestionOptionKeyIsUnique(ReadingQuestion $question, string $key, ?int $exceptId = null): void
    {
        $query = $question->options()->where('option_key', $key);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'option_key' => "Option key [{$key}] already exists for this question.",
            ]);
        }
    }

    private function objectiveOptionQuestion(ReadingQuestionOption $option): ReadingQuestion
    {
        if ($option->question_id === null) {
            throw ValidationException::withMessages([
                'option' => 'Only question-level options can be managed here.',
            ]);
        }

        return $option->question()->firstOrFail();
    }

    private function nextOptionKey(ReadingQuestion $question): string
    {
        $count = (int) $question->options()->count();

        return ObjectiveBulkImportParser::optionKeyForIndex($count);
    }

    private function questionOptionUsageCount(ReadingQuestion $question, string $optionKey): int
    {
        return ReadingCorrectAnswer::query()
            ->where('question_id', $question->id)
            ->where(function ($query) use ($optionKey): void {
                $query->where('answer', $optionKey)
                    ->orWhereJsonContains('answer_json', $optionKey);
            })
            ->count();
    }

    private function rewriteQuestionCorrectAnswersForOptionKey(ReadingQuestion $question, string $from, string $to): void
    {
        foreach ($question->correctAnswers as $answer) {
            $json = $answer->answer_json;

            if (is_array($json)) {
                $answer->answer_json = array_map(
                    fn ($value) => $value === $from ? $to : $value,
                    $json,
                );
            }

            if ($answer->answer === $from) {
                $answer->answer = $to;
            }

            $answer->save();
        }
    }
}
