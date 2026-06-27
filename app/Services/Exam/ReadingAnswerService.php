<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\TestAttemptStatus;
use App\Models\ReadingAnswer;
use App\Models\ReadingAttempt;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestion;
use App\Models\ReadingTest;
use App\Services\Exam\ReadingTimerService;
use App\Models\User;
use App\Support\Reading\ReadingSecurityLogger;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ReadingAnswerService
{
    public function __construct(
        private readonly ReadingTestRendererService $renderer,
        private readonly ReadingTimerService $timer,
    ) {
    }

    public function getOrCreateAttempt(User $user, ReadingTest $test): ReadingAttempt
    {
        $existing = ReadingAttempt::query()
            ->where('user_id', $user->id)
            ->where('reading_test_id', $test->id)
            ->where('status', TestAttemptStatus::InProgress)
            ->latest('id')
            ->first();

        if ($existing !== null) {
            $this->timer->syncTimer($existing);

            return $existing;
        }

        $test = $this->renderer->cachedForRenderer($test);
        $firstPassage = $test->passages->first();
        $firstQuestion = $firstPassage
            ? $this->renderer->questionsForPassage($firstPassage)->first()
            : null;

        $attempt = ReadingAttempt::query()->create([
            'user_id' => $user->id,
            'reading_test_id' => $test->id,
            'status' => TestAttemptStatus::InProgress,
            'started_at' => now(),
            'current_passage_id' => $firstPassage?->id,
            'current_question_id' => $firstQuestion?->id,
            'remaining_seconds' => max(0, (int) $test->duration_minutes) * 60,
        ]);

        $this->timer->syncTimer($attempt);

        return $attempt->fresh();
    }

    public function assertWritableAttempt(ReadingAttempt $attempt, ?User $user = null): void
    {
        $user ??= auth()->user();

        if ($user === null || $attempt->user_id !== $user->id) {
            ReadingSecurityLogger::ownershipDenied('writable_attempt', $user?->id, $attempt);
            throw new AuthorizationException('This attempt does not belong to you.');
        }

        if ($attempt->status !== TestAttemptStatus::InProgress) {
            throw new ConflictHttpException('This attempt is no longer in progress.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function saveAnswer(ReadingAttempt $attempt, array $payload): array
    {
        $this->assertWritableAttempt($attempt);

        $question = $this->resolveQuestionForAttempt(
            $attempt,
            (int) $payload['question_id'],
            (int) $payload['passage_id'],
            (int) $payload['group_id'],
            (string) $payload['question_type'],
        );

        $answerText = isset($payload['answer']) ? trim((string) $payload['answer']) : null;
        $answerJson = $payload['answer_json'] ?? null;

        if ($answerText === '') {
            $answerText = null;
        }

        if (is_array($answerJson)) {
            $answerJson = array_values(array_filter(
                array_map(static fn ($value): string => trim((string) $value), $answerJson),
                static fn (string $value): bool => $value !== '',
            ));
            if ($answerJson === []) {
                $answerJson = null;
            }
        } else {
            $answerJson = null;
        }

        $type = $question->group?->question_type;
        $isAnswered = $this->isAnswered($type, $answerText, $answerJson);

        $record = ReadingAnswer::query()->updateOrCreate(
            [
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
            ],
            [
                'answer' => $type === OfficialReadingQuestionType::MultipleChoiceMultiple ? null : $answerText,
                'answer_json' => $type === OfficialReadingQuestionType::MultipleChoiceMultiple ? $answerJson : null,
                'answered_at' => $isAnswered ? now() : null,
            ],
        );

        return $this->buildSaveResponse($attempt, $question->question_number, $record);
    }

    /**
     * @return array<string, mixed>
     */
    public function toggleFlag(ReadingAttempt $attempt, ReadingQuestion $question, bool $flagged): array
    {
        $this->assertWritableAttempt($attempt);
        $this->assertQuestionBelongsToAttemptTest($attempt, $question);

        $record = ReadingAnswer::query()->updateOrCreate(
            [
                'attempt_id' => $attempt->id,
                'question_id' => $question->id,
            ],
            [
                'flagged' => $flagged,
            ],
        );

        return $this->buildSaveResponse($attempt, $question->question_number, $record);
    }

    /**
     * @return array<string, mixed>
     */
    public function savePosition(ReadingAttempt $attempt, int $passageId, int $questionId): array
    {
        $this->assertWritableAttempt($attempt);

        $passage = ReadingPassage::query()
            ->where('id', $passageId)
            ->where('reading_test_id', $attempt->reading_test_id)
            ->firstOrFail();

        $question = ReadingQuestion::query()
            ->where('id', $questionId)
            ->whereHas('group', fn ($query) => $query->where('passage_id', $passage->id))
            ->firstOrFail();

        $attempt->update([
            'current_passage_id' => $passage->id,
            'current_question_id' => $question->id,
        ]);

        return [
            'success' => true,
            'current_passage_id' => $passage->id,
            'current_question_id' => $question->id,
            'current_question_number' => $question->question_number,
            'navigator_status' => $this->buildNavigatorStatus($attempt->fresh()),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function loadSavedAnswers(ReadingAttempt $attempt): array
    {
        $answers = [];

        foreach ($attempt->answers()->with('question.group')->get() as $answer) {
            $question = $answer->question;
            if ($question === null) {
                continue;
            }

            $answers[$question->id] = [
                'question_id' => $question->id,
                'question_number' => $question->question_number,
                'group_id' => $question->group_id,
                'passage_id' => $question->group?->passage_id,
                'question_type' => $question->group?->question_type?->value,
                'answer' => $answer->answer,
                'answer_json' => $answer->answer_json,
                'flagged' => (bool) $answer->flagged,
                'answered' => $this->isAnswered(
                    $question->group?->question_type,
                    $answer->answer,
                    $answer->answer_json,
                ),
            ];
        }

        return $answers;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildNavigatorStatus(ReadingAttempt $attempt): array
    {
        $attempt->loadMissing('test');
        $test = $this->renderer->cachedForRenderer($attempt->test);

        $saved = ReadingAnswer::query()
            ->where('attempt_id', $attempt->id)
            ->get()
            ->keyBy('question_id');

        $questions = [];
        $parts = [];
        $answeredCount = 0;
        $totalQuestions = 0;

        foreach ($test->passages as $passage) {
            $partAnswered = 0;
            $partTotal = 0;

            foreach ($this->renderer->questionsForPassage($passage) as $question) {
                $totalQuestions++;
                $partTotal++;
                $answer = $saved->get($question->id);
                $type = $question->group?->question_type;
                $answered = $answer !== null && $this->isAnswered($type, $answer->answer, $answer->answer_json);
                $flagged = (bool) ($answer?->flagged ?? false);

                if ($answered) {
                    $answeredCount++;
                    $partAnswered++;
                }

                $questions[$question->question_number] = [
                    'question_id' => $question->id,
                    'question_number' => $question->question_number,
                    'passage_id' => $passage->id,
                    'answered' => $answered,
                    'flagged' => $flagged,
                    'status' => $this->questionStatus($answered, $flagged),
                ];
            }

            $parts[$passage->id] = [
                'passage_id' => $passage->id,
                'answered' => $partAnswered,
                'total' => $partTotal,
                'label' => "{$partAnswered} of {$partTotal} answered",
            ];
        }

        return [
            'answered_count' => $answeredCount,
            'total_questions' => $totalQuestions,
            'questions' => $questions,
            'parts' => $parts,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildAttemptPayload(ReadingAttempt $attempt, ReadingTest $test): array
    {
        $test = $this->renderer->cachedForRenderer($test);
        $navigator = $this->buildNavigatorStatus($attempt);
        $savedAnswers = $this->loadSavedAnswers($attempt);

        $currentQuestion = $attempt->currentQuestion;
        $currentPassage = $attempt->currentPassage;

        $initialQuestionNumber = $currentQuestion?->question_number
            ?? ($navigator['questions'] ? array_key_first($navigator['questions']) : null);

        $initialPassageId = $currentPassage?->id ?? $test->passages->first()?->id;

        $reviewService = app(ReadingReviewService::class);
        $submitService = app(ReadingSubmitService::class);
        $experienceService = app(ReadingStudentExperienceService::class);

        $endpoints = [
            'saveAnswer' => route('reading-attempts.answers.store', $attempt),
            'savePosition' => route('reading-attempts.position', $attempt),
            'toggleFlag' => route('reading-attempts.answers.flag', ['attempt' => $attempt, 'question' => '__QUESTION__']),
            'timer' => route('reading-attempts.timer', $attempt),
            'review' => route('reading-attempts.review', $attempt),
            'visited' => route('reading-attempts.visited', $attempt),
            'submit' => route('reading-attempts.submit', $attempt),
            'autoSubmit' => route('reading-attempts.auto-submit', $attempt),
            'submitted' => route('reading-attempts.submitted', $attempt),
            'result' => route('reading-attempts.result', $attempt),
            'resultReview' => route('reading-attempts.result.review', $attempt),
        ];

        $experience = $experienceService->augmentAttemptPayload($attempt, $endpoints);

        return [
            'attemptId' => $attempt->uuid,
            'attemptNumericId' => $attempt->id,
            'attemptStatus' => $attempt->status?->value,
            'isLocked' => $attempt->status !== TestAttemptStatus::InProgress,
            'savedAnswers' => $savedAnswers,
            'navigator' => $navigator,
            'review' => $reviewService->buildReviewSummary($attempt),
            'timer' => $this->timer->timerPayload($attempt),
            'visitedQuestions' => $submitService->visitedQuestions($attempt),
            'initialPassageId' => $initialPassageId,
            'initialQuestionNumber' => $initialQuestionNumber,
            'initialQuestionId' => $currentQuestion?->id,
            'endpoints' => $experience['endpoints'] ?? $endpoints,
            'highlights' => $experience['highlights'] ?? [],
            'notes' => $experience['notes'] ?? [],
        ];
    }

    private function resolveQuestionForAttempt(
        ReadingAttempt $attempt,
        int $questionId,
        int $passageId,
        int $groupId,
        string $questionType,
    ): ReadingQuestion {
        $question = ReadingQuestion::query()
            ->where('id', $questionId)
            ->where('group_id', $groupId)
            ->whereHas('group', fn ($query) => $query
                ->where('id', $groupId)
                ->where('passage_id', $passageId)
                ->whereHas('passage', fn ($passageQuery) => $passageQuery
                    ->where('id', $passageId)
                    ->where('reading_test_id', $attempt->reading_test_id)))
            ->firstOrFail();

        $actualType = $question->group?->question_type?->value;

        if ($actualType !== $questionType) {
            abort(422, 'Question type does not match the question group.');
        }

        return $question;
    }

    public function assertQuestionBelongsToAttemptTest(ReadingAttempt $attempt, ReadingQuestion $question): void
    {
        $belongs = ReadingQuestion::query()
            ->where('id', $question->id)
            ->whereHas('group.passage', fn ($query) => $query->where('reading_test_id', $attempt->reading_test_id))
            ->exists();

        if (! $belongs) {
            ReadingSecurityLogger::invalidAnswerSave('question_not_in_test', auth()->id(), $attempt);
            abort(422, 'Question does not belong to this reading test.');
        }
    }

    private function isAnswered(?OfficialReadingQuestionType $type, ?string $answer, ?array $answerJson): bool
    {
        if ($type === OfficialReadingQuestionType::MultipleChoiceMultiple) {
            return is_array($answerJson) && $answerJson !== [];
        }

        return trim((string) $answer) !== '';
    }

    private function questionStatus(bool $answered, bool $flagged): string
    {
        if ($answered && $flagged) {
            return 'answered-flagged';
        }

        if ($flagged) {
            return 'flagged';
        }

        if ($answered) {
            return 'answered';
        }

        return 'unanswered';
    }

    /**
     * @return array<string, mixed>
     */
    private function buildSaveResponse(ReadingAttempt $attempt, int $questionNumber, ReadingAnswer $record): array
    {
        $navigator = $this->buildNavigatorStatus($attempt);
        $questionStatus = $navigator['questions'][$questionNumber] ?? null;

        return [
            'success' => true,
            'question_number' => $questionNumber,
            'answered_status' => $questionStatus['status'] ?? 'unanswered',
            'answered_count' => $navigator['answered_count'],
            'total_questions' => $navigator['total_questions'],
            'part_status' => $navigator['parts'],
            'navigator_status' => $navigator,
            'answer' => [
                'question_id' => $record->question_id,
                'answer' => $record->answer,
                'answer_json' => $record->answer_json,
                'flagged' => (bool) $record->flagged,
            ],
        ];
    }
}
