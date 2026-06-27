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
use App\Support\Reading\MatchingBulkImportParser;
use App\Support\Reading\ReadingQuestionReferenceSupport;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReadingMatchingQuestionService
{
    public function loadGroupForBuilder(ReadingQuestionGroup $group): ReadingQuestionGroup
    {
        return $group->load([
            'passage.test',
            'groupOptions',
            'questions' => fn ($query) => $query->with('correctAnswers')->orderBy('sort_order'),
        ]);
    }

    public function assertMatchingGroup(ReadingQuestionGroup $group): void
    {
        if (! $group->question_type?->isMatchingBuilderType()) {
            throw ValidationException::withMessages([
                'question_type' => 'This question group does not use the matching question builder.',
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
     * @param  array{option_key: string, option_label?: ?string, sort_order?: ?int}  $data
     */
    public function storeOption(ReadingQuestionGroup $group, array $data): ReadingQuestionOption
    {
        $this->assertMatchingGroup($group);

        return DB::transaction(function () use ($group, $data): ReadingQuestionOption {
            $key = trim((string) $data['option_key']);
            $this->assertOptionKeyIsUnique($group, $key);

            $sortOrder = (int) ($data['sort_order'] ?? ((int) $group->groupOptions()->max('sort_order') + 1));

            /** @var ReadingQuestionOption $option */
            $option = $group->groupOptions()->create([
                'option_key' => $key,
                'option_label' => (string) ($data['option_label'] ?? ''),
                'sort_order' => max(1, $sortOrder),
            ]);

            return $option->refresh();
        });
    }

    /**
     * @param  array{option_key?: string, option_label?: ?string, sort_order?: ?int}  $data
     */
    public function updateOption(ReadingQuestionOption $option, array $data): ReadingQuestionOption
    {
        return DB::transaction(function () use ($option, $data): ReadingQuestionOption {
            $group = $this->optionGroup($option);
            $this->assertMatchingGroup($group);

            $originalKey = $option->option_key;
            $newKey = isset($data['option_key']) ? trim((string) $data['option_key']) : $originalKey;

            if ($newKey !== $originalKey) {
                $this->assertOptionKeyIsUnique($group, $newKey, $option->id);
                $this->rewriteCorrectAnswersForOptionKey($group, $originalKey, $newKey);
            }

            $option->forceFill([
                'option_key' => $newKey,
                'option_label' => array_key_exists('option_label', $data)
                    ? (string) ($data['option_label'] ?? '')
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
            $group = $this->optionGroup($option);
            $this->assertMatchingGroup($group);

            $usageCount = $this->optionUsageCount($group, $option->option_key);

            if ($usageCount > 0 && ! $confirmed) {
                throw ValidationException::withMessages([
                    'option' => "This option is used as the correct answer for {$usageCount} question(s). Reassign those answers or confirm deletion.",
                ]);
            }

            $option->delete();
        });
    }

    /**
     * @param  array{
     *     question_number: int,
     *     prompt: string,
     *     correct_answer?: ?string,
     *     paragraph_reference?: ?string,
     *     explanation?: ?string,
     *     sort_order?: ?int
     * }  $data
     */
    public function storeQuestion(ReadingQuestionGroup $group, array $data): ReadingQuestion
    {
        $this->assertMatchingGroup($group);

        return DB::transaction(function () use ($group, $data): ReadingQuestion {
            $questionNumber = (int) $data['question_number'];
            $this->assertQuestionNumberIsValid($group, $questionNumber);

            $sortOrder = (int) ($data['sort_order'] ?? ((int) $group->questions()->max('sort_order') + 1));

            /** @var ReadingQuestion $question */
            $question = $group->questions()->create([
                'question_number' => $questionNumber,
                'prompt' => (string) $data['prompt'],
                'explanation' => $data['explanation'] ?? null,
                'sort_order' => max(1, $sortOrder),
                'marks' => 1,
            ]);

            ReadingQuestionReferenceSupport::applyAttributes($question, $data);
            $question->save();

            if (! empty($data['correct_answer'])) {
                $this->syncCorrectAnswer($group, $question, (string) $data['correct_answer']);
            }

            return $question->load('correctAnswers');
        });
    }

    /**
     * @param  array{
     *     question_number?: int,
     *     prompt?: string,
     *     correct_answer?: ?string,
     *     paragraph_reference?: ?string,
     *     explanation?: ?string,
     *     sort_order?: ?int
     * }  $data
     */
    public function updateQuestion(ReadingQuestion $question, array $data): ReadingQuestion
    {
        return DB::transaction(function () use ($question, $data): ReadingQuestion {
            $group = $question->group()->firstOrFail();
            $this->assertMatchingGroup($group);

            if (isset($data['question_number'])) {
                $questionNumber = (int) $data['question_number'];
                $this->assertQuestionNumberIsValid($group, $questionNumber, $question);
                $question->question_number = $questionNumber;
            }

            if (isset($data['prompt'])) {
                $question->prompt = (string) $data['prompt'];
            }

            ReadingQuestionReferenceSupport::applyAttributes($question, $data);

            if (array_key_exists('explanation', $data)) {
                $question->explanation = $data['explanation'];
            }

            if (isset($data['sort_order'])) {
                $question->sort_order = max(1, (int) $data['sort_order']);
            }

            $question->save();

            if (array_key_exists('correct_answer', $data)) {
                if ($data['correct_answer'] === null || $data['correct_answer'] === '') {
                    $question->correctAnswers()->delete();
                } else {
                    $this->syncCorrectAnswer($group, $question, (string) $data['correct_answer']);
                }
            }

            return $question->load('correctAnswers');
        });
    }

    public function deleteQuestion(ReadingQuestion $question): void
    {
        DB::transaction(function () use ($question): void {
            $group = $question->group()->firstOrFail();
            $this->assertMatchingGroup($group);
            $question->delete();
        });
    }

    /**
     * @param  array{option_ids?: list<int>, question_ids?: list<int>}  $data
     */
    public function reorder(ReadingQuestionGroup $group, array $data): void
    {
        $this->assertMatchingGroup($group);

        DB::transaction(function () use ($group, $data): void {
            if (! empty($data['option_ids'])) {
                $this->reorderOptions($group, $data['option_ids']);
            }

            if (! empty($data['question_ids'])) {
                $this->reorderQuestions($group, $data['question_ids']);
            }
        });
    }

    /**
     * @param  array{options_text?: ?string, questions_text?: ?string}  $data
     */
    public function bulkImport(ReadingQuestionGroup $group, array $data): array
    {
        $this->assertMatchingGroup($group);

        return DB::transaction(function () use ($group, $data): array {
            $createdOptions = 0;
            $createdQuestions = 0;

            if (! empty($data['options_text'])) {
                foreach (MatchingBulkImportParser::parseOptions((string) $data['options_text']) as $row) {
                    if ($group->groupOptions()->where('option_key', $row['option_key'])->exists()) {
                        continue;
                    }

                    $this->storeOption($group, $row);
                    $createdOptions++;
                }
            }

            if (! empty($data['questions_text'])) {
                foreach (MatchingBulkImportParser::parseQuestions((string) $data['questions_text'], $group->question_type) as $row) {
                    if ($group->questions()->where('question_number', $row['question_number'])->exists()) {
                        continue;
                    }

                    $this->storeQuestion($group, $row);
                    $createdQuestions++;
                }
            }

            return [
                'options' => $createdOptions,
                'questions' => $createdQuestions,
            ];
        });
    }

    /**
     * @param  list<int>  $optionIds
     */
    private function reorderOptions(ReadingQuestionGroup $group, array $optionIds): void
    {
        $options = $group->groupOptions()->whereIn('id', $optionIds)->get()->keyBy('id');

        if ($options->count() !== count($optionIds)) {
            throw ValidationException::withMessages([
                'option_ids' => 'One or more options do not belong to this question group.',
            ]);
        }

        foreach (array_values($optionIds) as $index => $id) {
            /** @var ReadingQuestionOption $option */
            $option = $options->get($id);
            $option->forceFill(['sort_order' => $index + 1])->save();
        }
    }

    /**
     * @param  list<int>  $questionIds
     */
    private function reorderQuestions(ReadingQuestionGroup $group, array $questionIds): void
    {
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
    }

    private function assertOptionKeyIsUnique(ReadingQuestionGroup $group, string $key, ?int $exceptId = null): void
    {
        $query = $group->groupOptions()->where('option_key', $key);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'option_key' => "Option key [{$key}] already exists in this group.",
            ]);
        }
    }

    private function assertQuestionNumberIsValid(
        ReadingQuestionGroup $group,
        int $questionNumber,
        ?ReadingQuestion $except = null,
    ): void {
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

        $groupQuery = $group->questions()->where('question_number', $questionNumber);

        if ($except !== null) {
            $groupQuery->whereKeyNot($except->id);
        }

        if ($groupQuery->exists()) {
            throw ValidationException::withMessages([
                'question_number' => "Question number {$questionNumber} already exists in this group.",
            ]);
        }

        $test = $this->readingTestForGroup($group);
        $testQuery = $test->questions()->where('question_number', $questionNumber);

        if ($except !== null) {
            $testQuery->whereKeyNot($except->id);
        }

        if ($testQuery->exists()) {
            throw ValidationException::withMessages([
                'question_number' => "Question number {$questionNumber} is already used in this reading test.",
            ]);
        }
    }

    private function syncCorrectAnswer(ReadingQuestionGroup $group, ReadingQuestion $question, string $answerKey): void
    {
        $answerKey = trim($answerKey);

        if (! $group->groupOptions()->where('option_key', $answerKey)->exists()) {
            throw ValidationException::withMessages([
                'correct_answer' => "Correct answer [{$answerKey}] must match an existing option key.",
            ]);
        }

        /** @var ReadingCorrectAnswer $correct */
        $correct = $question->correctAnswers()->firstOrNew();
        $correct->forceFill([
            'answer' => $answerKey,
            'matching_key' => $answerKey,
        ])->save();
    }

    private function optionGroup(ReadingQuestionOption $option): ReadingQuestionGroup
    {
        if ($option->group_id === null) {
            throw ValidationException::withMessages([
                'option' => 'Only group-level matching options can be managed here.',
            ]);
        }

        return $option->group()->firstOrFail();
    }

    private function optionUsageCount(ReadingQuestionGroup $group, string $optionKey): int
    {
        return ReadingCorrectAnswer::query()
            ->where(function ($query) use ($optionKey): void {
                $query->where('answer', $optionKey)
                    ->orWhere('matching_key', $optionKey);
            })
            ->whereHas('question', fn ($query) => $query->where('group_id', $group->id))
            ->count();
    }

    private function rewriteCorrectAnswersForOptionKey(ReadingQuestionGroup $group, string $from, string $to): void
    {
        $answers = ReadingCorrectAnswer::query()
            ->whereHas('question', fn ($query) => $query->where('group_id', $group->id))
            ->where(function ($query) use ($from): void {
                $query->where('answer', $from)->orWhere('matching_key', $from);
            })
            ->get();

        foreach ($answers as $answer) {
            $answer->forceFill([
                'answer' => $to,
                'matching_key' => $to,
            ])->save();
        }
    }

    /**
     * @return Collection<int, ReadingQuestionOption>
     */
    public function groupOptionKeys(ReadingQuestionGroup $group): Collection
    {
        return $group->groupOptions()->orderBy('sort_order')->get();
    }
}
