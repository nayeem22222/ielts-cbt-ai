<?php

declare(strict_types=1);

namespace App\Services\Listening;

use App\Actions\Listening\CreateQuestionsFromGroupRangeAction;
use App\Actions\Listening\NormalizeListeningAnswerDataAction;
use App\Actions\Listening\ReorderListeningQuestionsAction;
use App\Actions\Listening\QuestionTypes\NormalizeQuestionTypePayloadAction;
use App\Actions\Listening\QuestionTypes\ValidateQuestionTypePayloadAction;
use App\Actions\Listening\ValidateListeningQuestionNumberAction;
use App\Enums\Listening\ListeningQuestionType;
use App\Enums\Listening\ListeningTestStatus;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use App\Repositories\Listening\ListeningQuestionRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListeningQuestionService
{
    public function __construct(
        private readonly ListeningQuestionRepository $questions,
        private readonly ListeningQuestionGroupService $groups,
        private readonly ValidateListeningQuestionNumberAction $validateQuestionNumber,
        private readonly NormalizeListeningAnswerDataAction $normalizeAnswers,
        private readonly CreateQuestionsFromGroupRangeAction $bulkCreate,
        private readonly ReorderListeningQuestionsAction $reorderQuestions,
        private readonly NormalizeQuestionTypePayloadAction $normalizeQuestionType,
        private readonly ValidateQuestionTypePayloadAction $validateQuestionType,
    ) {}

    /**
     * @return Collection<int, ListeningQuestion>
     */
    public function listForGroup(ListeningQuestionGroup $group): Collection
    {
        return $this->questions->forGroup($group);
    }

    public function findByNumberForGroup(
        ListeningQuestionGroup $group,
        int $questionNumber,
        bool $withTrashed = false,
    ): ?ListeningQuestion {
        return $this->questions->findByNumberForGroup($group, $questionNumber, $withTrashed);
    }

    public function findByNumberForTest(
        ListeningTest $test,
        int $questionNumber,
        bool $withTrashed = false,
    ): ?ListeningQuestion {
        return $this->questions->findByNumberForTest($test, $questionNumber, $withTrashed);
    }

    /**
     * Create or reclaim a question slot for builder sync flows.
     *
     * @param  array<string, mixed>  $data
     */
    public function syncQuestionSlot(
        ListeningTest $test,
        ListeningSection $section,
        ListeningQuestionGroup $group,
        int $questionNumber,
        array $data,
    ): ListeningQuestion {
        $payload = array_merge($data, ['question_number' => $questionNumber]);

        $existingInGroup = $this->findByNumberForGroup($group, $questionNumber, withTrashed: true);

        if ($existingInGroup !== null) {
            if ($existingInGroup->trashed()) {
                $existingInGroup->restore();
            }

            return $this->update($test, $section, $group, $existingInGroup, $payload);
        }

        $existingOnTest = $this->findByNumberForTest($test, $questionNumber, withTrashed: true);

        if ($existingOnTest !== null) {
            if ($existingOnTest->trashed()) {
                $existingOnTest->restore();

                return $this->update($test, $section, $group, $existingOnTest, $payload);
            }

            if ((int) $existingOnTest->listening_question_group_id !== (int) $group->id) {
                throw ValidationException::withMessages([
                    'question_number' => "Question number {$questionNumber} is already assigned to another group in this test.",
                ]);
            }

            return $this->update($test, $section, $group, $existingOnTest, $payload);
        }

        return $this->create($test, $section, $group, $payload);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(
        ListeningTest $test,
        ListeningSection $section,
        ListeningQuestionGroup $group,
        array $data,
    ): ListeningQuestion {
        $this->groups->ensureSectionBelongsToTest($test, $section);
        $this->groups->ensureGroupBelongsToSection($section, $group);
        $this->assertTestAllowsQuestionChanges($test);

        return DB::transaction(function () use ($test, $section, $group, $data): ListeningQuestion {
            $payload = $this->preparePayload($test, $section, $group, $data);
            $type = $group->question_type ?? ListeningQuestionType::from((string) ($payload['question_type'] ?? ListeningQuestionType::FormCompletion->value));
            $group->loadMissing('questions');
            $questionStub = new ListeningQuestion([
                'question_number' => (int) $payload['question_number'],
                'question_text' => $payload['question_text'] ?? null,
            ]);
            $payload = $this->normalizeQuestionType->execute($payload, $type, $group, $questionStub);
            $this->validateQuestionType->executeOrFail('question', $payload, $type, $group, $questionStub, $group->questions);
            $errors = $this->validateQuestionNumber->execute($test, $section, $group, (int) $payload['question_number']);

            if ($errors !== []) {
                throw ValidationException::withMessages(['question_number' => $errors[0]]);
            }

            $this->assertCorrectAnswerPresent($payload['correct_answer'] ?? []);

            return $this->questions->create($payload);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(
        ListeningTest $test,
        ListeningSection $section,
        ListeningQuestionGroup $group,
        ListeningQuestion $question,
        array $data,
    ): ListeningQuestion {
        $this->groups->ensureSectionBelongsToTest($test, $section);
        $this->groups->ensureGroupBelongsToSection($section, $group);
        $this->assertTestAllowsQuestionChanges($test);

        return DB::transaction(function () use ($test, $section, $group, $question, $data): ListeningQuestion {
            $payload = $this->preparePayload($test, $section, $group, $data, $question);
            $this->ensureQuestionBelongsToGroupOrReassigning($group, $question, $payload);
            $type = $group->question_type ?? ListeningQuestionType::from((string) ($payload['question_type'] ?? ListeningQuestionType::FormCompletion->value));
            $group->load('questions');
            $payload = $this->normalizeQuestionType->execute($payload, $type, $group, $question);
            $this->validateQuestionType->executeOrFail('question', $payload, $type, $group, $question, $group->questions);
            $errors = $this->validateQuestionNumber->execute(
                $test,
                $section,
                $group,
                (int) $payload['question_number'],
                $question->id,
            );

            if ($errors !== []) {
                throw ValidationException::withMessages(['question_number' => $errors[0]]);
            }

            $this->assertCorrectAnswerPresent($payload['correct_answer'] ?? []);

            return $this->questions->update($question, $payload);
        });
    }

    public function delete(ListeningQuestion $question): bool
    {
        $test = $question->test ?? $question->test()->first();

        if ($test !== null) {
            $this->assertTestAllowsQuestionChanges($test, allowDelete: true);
        }

        return DB::transaction(fn (): bool => $this->questions->delete($question));
    }

    /**
     * @return array{created: int, skipped: int}
     */
    public function bulkCreateFromGroupRange(ListeningQuestionGroup $group): array
    {
        $group->loadMissing(['test', 'section']);
        $test = $group->test;
        $section = $group->section;

        if ($test === null || $section === null) {
            throw ValidationException::withMessages(['group' => 'Group context is invalid.']);
        }

        $this->assertTestAllowsQuestionChanges($test);

        return DB::transaction(fn (): array => $this->bulkCreate->execute($group));
    }

    /**
     * @param  list<int>  $orderedQuestionIds
     */
    public function reorder(ListeningQuestionGroup $group, array $orderedQuestionIds): void
    {
        $group->loadMissing('test');

        if ($group->test !== null) {
            $this->assertTestAllowsQuestionChanges($group->test);
        }

        DB::transaction(function () use ($group, $orderedQuestionIds): void {
            $this->reorderQuestions->execute($group, $orderedQuestionIds);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function getQuestionReadiness(ListeningQuestion $question): array
    {
        $question->loadMissing(['group', 'section']);
        $missing = [];
        $hasNumber = $question->question_number > 0;
        $hasCorrect = $this->hasCorrectAnswer($question->correct_answer);
        $hasValidTimestamp = $this->hasValidTimestamp($question);
        $type = $question->question_type ?? $question->group?->question_type;

        if ($type !== null) {
            try {
                $registry = app(\App\Services\Listening\QuestionTypes\ListeningQuestionTypeRegistry::class);

                if ($registry->isEnabled($type)) {
                    $typeErrors = $registry->serviceFor($type)->validatePayload(
                        [
                            'question_text' => $question->question_text,
                            'word_limit' => $question->word_limit,
                            'correct_answer' => $question->correct_answer,
                            'options' => $question->options ?? $question->group?->options,
                        ],
                        $question->group,
                        $question,
                        $question->group?->questions,
                    );
                    $missing = array_merge($missing, $typeErrors);
                    $hasCorrect = $hasCorrect && ! in_array('Correct answer is required.', $typeErrors, true);
                }
            } catch (\Throwable) {
                // fallback to generic readiness
            }
        }

        $hasOptions = $this->hasRequiredOptions($question);

        if (! $hasNumber) {
            $missing[] = 'Question number is missing.';
        }

        if (! $hasCorrect && ! config('listening.questions.allow_draft_without_answer', true)) {
            $missing[] = 'Correct answer is required.';
        }

        if (! $hasOptions) {
            $missing[] = 'Options are required for this question type.';
        }

        if (! $hasValidTimestamp) {
            $missing[] = 'Audio timestamp range is invalid.';
        }

        return [
            'has_question_number' => $hasNumber,
            'has_valid_range' => true,
            'has_correct_answer' => $hasCorrect,
            'has_valid_answer_format' => $question->answer_format !== null,
            'has_required_options' => $hasOptions,
            'has_valid_timestamp' => $hasValidTimestamp,
            'is_ready' => $missing === [] && $hasCorrect,
            'missing' => array_values(array_unique($missing)),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function normalizeAnswerData(array|string|null $answer, string $defaultType = 'text'): array
    {
        return $this->normalizeAnswers->execute($answer, $defaultType);
    }

    public function ensureQuestionBelongsToGroup(ListeningQuestionGroup $group, ListeningQuestion $question): void
    {
        if ((int) $question->listening_question_group_id !== (int) $group->id) {
            throw ValidationException::withMessages(['question' => 'Question does not belong to this group.']);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function ensureQuestionBelongsToGroupOrReassigning(
        ListeningQuestionGroup $group,
        ListeningQuestion $question,
        array $payload,
    ): void {
        if ((int) $question->listening_question_group_id === (int) $group->id) {
            return;
        }

        if (
            (int) ($payload['listening_question_group_id'] ?? 0) === (int) $group->id
            && (int) ($payload['listening_test_id'] ?? 0) === (int) $group->listening_test_id
        ) {
            return;
        }

        throw ValidationException::withMessages(['question' => 'Question does not belong to this group.']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function preparePayload(
        ListeningTest $test,
        ListeningSection $section,
        ListeningQuestionGroup $group,
        array $data,
        ?ListeningQuestion $existing = null,
    ): array {
        $answerFormat = (string) ($data['answer_format'] ?? $existing?->answer_format?->value ?? 'text');
        $correct = $this->normalizeAnswers->execute($data['correct_answer'] ?? $existing?->correct_answer, $answerFormat);
        $accepted = $this->normalizeAnswers->execute($data['accepted_answers'] ?? $existing?->accepted_answers ?? [], $answerFormat);

        return [
            'listening_test_id' => $test->id,
            'listening_section_id' => $section->id,
            'listening_question_group_id' => $group->id,
            'question_number' => (int) ($data['question_number'] ?? $existing?->question_number),
            'question_type' => $data['question_type'] ?? $existing?->question_type ?? $group->question_type,
            'question_text' => $data['question_text'] ?? $existing?->question_text,
            'question_html' => $data['question_html'] ?? $existing?->question_html,
            'instruction' => $data['instruction'] ?? $existing?->instruction,
            'options' => $data['options'] ?? $existing?->options,
            'correct_answer' => $correct,
            'accepted_answers' => $accepted,
            'answer_format' => $answerFormat,
            'word_limit' => $data['word_limit'] ?? $existing?->word_limit,
            'case_sensitive' => (bool) ($data['case_sensitive'] ?? $existing?->case_sensitive ?? false),
            'order_sensitive' => (bool) ($data['order_sensitive'] ?? $existing?->order_sensitive ?? false),
            'allow_plural' => (bool) ($data['allow_plural'] ?? $existing?->allow_plural ?? true),
            'allow_articles' => (bool) ($data['allow_articles'] ?? $existing?->allow_articles ?? true),
            'allow_punctuation_variation' => (bool) ($data['allow_punctuation_variation'] ?? $existing?->allow_punctuation_variation ?? true),
            'marks' => $data['marks'] ?? $existing?->marks ?? config('listening.questions.default_marks', 1),
            'explanation' => $data['explanation'] ?? $existing?->explanation,
            'transcript_location' => $data['transcript_location'] ?? $existing?->transcript_location,
            'audio_timestamp_start' => $data['audio_timestamp_start'] ?? $existing?->audio_timestamp_start,
            'audio_timestamp_end' => $data['audio_timestamp_end'] ?? $existing?->audio_timestamp_end,
            'display_order' => $data['display_order'] ?? $existing?->display_order ?? ($data['question_number'] ?? $existing?->question_number),
            'is_required' => (bool) ($data['is_required'] ?? $existing?->is_required ?? true),
            'is_active' => (bool) ($data['is_active'] ?? $existing?->is_active ?? true),
            'meta' => $data['meta'] ?? $existing?->meta,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $correctAnswer
     */
    private function assertCorrectAnswerPresent(array $correctAnswer): void
    {
        if (config('listening.questions.allow_draft_without_answer', true)) {
            return;
        }

        if (! $this->hasCorrectAnswer($correctAnswer)) {
            throw ValidationException::withMessages(['correct_answer' => 'Correct answer is required.']);
        }
    }

    private function hasCorrectAnswer(mixed $correctAnswer): bool
    {
        if ($correctAnswer === null || $correctAnswer === '' || $correctAnswer === []) {
            return false;
        }

        if (! is_array($correctAnswer)) {
            return trim((string) $correctAnswer) !== '';
        }

        foreach ($correctAnswer as $item) {
            if (is_array($item) && trim((string) ($item['value'] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    private function hasRequiredOptions(ListeningQuestion $question): bool
    {
        $type = $question->question_type;

        if (! $type instanceof ListeningQuestionType) {
            return true;
        }

        $requiresOptions = in_array($type, [
            ListeningQuestionType::MCQ,
            ListeningQuestionType::MultipleAnswer,
            ListeningQuestionType::Matching,
            ListeningQuestionType::MapLabelling,
            ListeningQuestionType::PlanLabelling,
            ListeningQuestionType::DiagramLabelling,
        ], true);

        if (! $requiresOptions) {
            return true;
        }

        return is_array($question->options) && $question->options !== [];
    }

    private function hasValidTimestamp(ListeningQuestion $question): bool
    {
        if ($question->audio_timestamp_start === null && $question->audio_timestamp_end === null) {
            return true;
        }

        $start = (float) $question->audio_timestamp_start;
        $end = $question->audio_timestamp_end !== null ? (float) $question->audio_timestamp_end : null;

        if ($start < 0) {
            return false;
        }

        if ($end !== null && $end < $start) {
            return false;
        }

        $section = $question->section;
        $duration = $section?->audio?->duration_seconds;

        if ($duration !== null && $end !== null && $end > (float) $duration) {
            return false;
        }

        return true;
    }

    private function assertTestAllowsQuestionChanges(ListeningTest $test, bool $allowDelete = false): void
    {
        if ($test->status === ListeningTestStatus::Archived) {
            throw ValidationException::withMessages([
                'listening_test' => 'Archived listening tests cannot be modified.',
            ]);
        }

        if ($test->status === ListeningTestStatus::Published) {
            throw ValidationException::withMessages([
                'listening_test' => 'Unpublish the listening test before changing questions.',
            ]);
        }
    }
}
