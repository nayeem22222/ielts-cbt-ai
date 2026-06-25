<?php

declare(strict_types=1);

namespace App\Services\Admin\Exam;

use App\Enums\Exam\ReadingCompletionAnswerRule;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingTest;
use App\Support\Reading\ReadingQuestionReferenceSupport;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReadingShortAnswerQuestionService
{
    public function __construct(private readonly ReadingCompletionTemplateService $template)
    {
    }

    public function loadGroupForBuilder(ReadingQuestionGroup $group): ReadingQuestionGroup
    {
        return $group->load([
            'passage.test',
            'questions' => fn ($query) => $query
                ->with(['correctAnswers'])
                ->orderBy('sort_order'),
        ]);
    }

    public function assertShortAnswerGroup(ReadingQuestionGroup $group): void
    {
        if (! $group->question_type?->isShortAnswerBuilderType()) {
            throw ValidationException::withMessages([
                'question_type' => 'This question group does not use the short answer builder.',
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
     * @return array{answer_rule: string, custom_answer_rule: ?string}
     */
    public function groupBuilderSettings(ReadingQuestionGroup $group): array
    {
        $settings = $group->settings ?? [];

        return [
            'answer_rule' => (string) ($settings['answer_rule'] ?? ReadingCompletionAnswerRule::ThreeWords->value),
            'custom_answer_rule' => $settings['custom_answer_rule'] ?? null,
        ];
    }

    /**
     * @param  array{
     *     answer_rule?: string,
     *     custom_answer_rule?: ?string,
     *     question_number: int,
     *     prompt: string,
     *     correct_answer: string,
     *     alternative_answers?: list<string>|null,
     *     case_sensitive?: bool,
     *     explanation?: ?string,
     *     difficulty?: ?string,
     *     sort_order?: ?int
     * }  $data
     */
    public function storeQuestion(ReadingQuestionGroup $group, array $data): ReadingQuestion
    {
        $this->assertShortAnswerGroup($group);

        return DB::transaction(function () use ($group, $data): ReadingQuestion {
            if (isset($data['answer_rule'])) {
                $this->saveAnswerRule($group, $data);
            }

            $questionNumber = (int) $data['question_number'];
            $this->assertQuestionNumberIsValid($group, $questionNumber);

            $sortOrder = (int) ($data['sort_order'] ?? ((int) $group->questions()->max('sort_order') + 1));

            /** @var ReadingQuestion $question */
            $question = $group->questions()->create([
                'question_number' => $questionNumber,
                'prompt' => trim((string) $data['prompt']),
                'explanation' => $data['explanation'] ?? null,
                'difficulty' => $data['difficulty'] ?? 'medium',
                'sort_order' => max(1, $sortOrder),
                'marks' => 1,
                'metadata' => ['short_answer' => true],
            ]);

            ReadingQuestionReferenceSupport::applyAttributes($question, $data);
            $question->save();

            $this->assertPublishedAnswerPresent($group, $data);
            $this->syncCorrectAnswers($question, $data, $this->groupBuilderSettings($group)['answer_rule']);

            return $question->load(['correctAnswers']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateQuestion(ReadingQuestion $question, array $data): ReadingQuestion
    {
        return DB::transaction(function () use ($question, $data): ReadingQuestion {
            $group = $question->group()->firstOrFail();
            $this->assertShortAnswerGroup($group);

            if (isset($data['answer_rule'])) {
                $this->saveAnswerRule($group, $data);
            }

            if (isset($data['question_number'])) {
                $questionNumber = (int) $data['question_number'];
                $this->assertQuestionNumberIsValid($group, $questionNumber, $question);
                $question->question_number = $questionNumber;
            }

            if (isset($data['prompt'])) {
                $question->prompt = trim((string) $data['prompt']);
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

            ReadingQuestionReferenceSupport::applyAttributes($question, $data);

            $question->save();

            if (
                array_key_exists('correct_answer', $data)
                || array_key_exists('alternative_answers', $data)
                || array_key_exists('case_sensitive', $data)
            ) {
                $this->assertPublishedAnswerPresent($group, $data);
                $this->syncCorrectAnswers($question, $data, $this->groupBuilderSettings($group)['answer_rule']);
            }

            return $question->load(['correctAnswers']);
        });
    }

    public function deleteQuestion(ReadingQuestion $question): void
    {
        DB::transaction(function () use ($question): void {
            $group = $question->group()->firstOrFail();
            $this->assertShortAnswerGroup($group);
            $question->delete();
        });
    }

    /**
     * @param  list<int>  $questionIds
     */
    public function reorderQuestions(ReadingQuestionGroup $group, array $questionIds): void
    {
        $this->assertShortAnswerGroup($group);

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
     * @param  array<string, mixed>  $data
     */
    private function saveAnswerRule(ReadingQuestionGroup $group, array $data): void
    {
        $settings = $group->settings ?? [];
        $settings['answer_rule'] = (string) $data['answer_rule'];
        $settings['custom_answer_rule'] = $data['custom_answer_rule'] ?? null;
        $group->forceFill(['settings' => $settings])->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertPublishedAnswerPresent(ReadingQuestionGroup $group, array $data): void
    {
        if ($group->status?->value !== 'published') {
            return;
        }

        if (trim((string) ($data['correct_answer'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'correct_answer' => 'Correct answer is required for published short answer groups.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncCorrectAnswers(ReadingQuestion $question, array $data, string $wordLimit): void
    {
        $this->template->syncCorrectAnswers($question, [
            'correct_answer' => $data['correct_answer'] ?? null,
            'alternative_answers' => $data['alternative_answers'] ?? [],
            'case_sensitive' => (bool) ($data['case_sensitive'] ?? false),
            'word_limit' => $wordLimit,
            'regex' => $data['regex'] ?? null,
        ]);
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
}
