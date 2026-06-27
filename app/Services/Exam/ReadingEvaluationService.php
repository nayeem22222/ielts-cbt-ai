<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Enums\Course\ExamType;
use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\ReadingCompletionAnswerRule;
use App\Enums\Exam\TestAttemptStatus;
use App\Models\ReadingAnswer;
use App\Models\ReadingAttempt;
use App\Models\ReadingCorrectAnswer;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingQuestionOption;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ReadingEvaluationService
{
    public function __construct(
        private readonly ReadingTestRendererService $renderer,
        private readonly ReadingBandScoreService $bands,
        private readonly ReadingTimerService $timer,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function evaluateAttempt(ReadingAttempt $attempt, bool $force = false): array
    {
        if ($attempt->status === TestAttemptStatus::InProgress) {
            throw new ConflictHttpException('Cannot evaluate an in-progress attempt.');
        }

        if (! $force && $this->isAlreadyEvaluated($attempt)) {
            return $this->buildAttemptSummary($attempt->fresh(['test', 'answers.question.group.passage']));
        }

        return DB::transaction(function () use ($attempt, $force): array {
            $attempt = $attempt->fresh(['test']);
            $test = $this->renderer->cachedForRenderer($attempt->test);
            $test->load([
                'passages.groups.questions.correctAnswers',
                'passages.groups.questions.options',
                'passages.groups.groupOptions',
            ]);
            $attempt->setRelation('test', $test);

            $savedAnswers = $attempt->answers()->get()->keyBy('question_id');
            $outcomes = [];
            $answerUpdates = [];
            $rawScore = 0.0;
            $maxScore = 0.0;
            $correctCount = 0;
            $incorrectCount = 0;
            $unansweredCount = 0;
            $attemptedCount = 0;
            $totalQuestions = 0;
            $evaluatedAt = now();

            foreach ($test->passages as $passage) {
                foreach ($this->renderer->questionsForPassage($passage) as $question) {
                    $totalQuestions++;
                    $maxMarks = (float) ($question->marks ?: 1);
                    $maxScore += $maxMarks;

                    $answer = $savedAnswers->get($question->id);

                    if ($answer === null) {
                        $answer = ReadingAnswer::query()->create([
                            'attempt_id' => $attempt->id,
                            'question_id' => $question->id,
                        ]);
                        $savedAnswers->put($question->id, $answer);
                    }

                    $outcome = $this->evaluateAnswer($answer, $question);
                    $outcomes[] = $outcome;

                    $answerUpdates[] = [
                        'id' => $answer->id,
                        'is_correct' => $outcome['is_correct'],
                        'marks_awarded' => $outcome['marks_awarded'],
                        'evaluated_at' => $evaluatedAt,
                        'evaluation_json' => $outcome['evaluation_json'],
                    ];

                    $rawScore += (float) $outcome['marks_awarded'];

                    match ($outcome['status']) {
                        'correct' => $correctCount++,
                        'incorrect' => $incorrectCount++,
                        default => $unansweredCount++,
                    };

                    if ($outcome['status'] !== 'unanswered') {
                        $attemptedCount++;
                    }
                }
            }

            foreach ($answerUpdates as $row) {
                ReadingAnswer::query()->whereKey($row['id'])->update([
                    'is_correct' => $row['is_correct'],
                    'marks_awarded' => $row['marks_awarded'],
                    'evaluated_at' => $row['evaluated_at'],
                    'evaluation_json' => $row['evaluation_json'],
                ]);
            }

            $examType = $attempt->test?->exam_type?->value ?? ExamType::Academic->value;
            $band = $this->bands->getBandFromRatio($rawScore, max(1, $totalQuestions), $examType);
            $timeSpent = $this->calculateTimeSpent($attempt);

            $attempt->forceFill([
                'status' => TestAttemptStatus::Completed,
                'score' => round($rawScore, 2),
                'band' => $band,
                'time_spent' => $timeSpent,
                'evaluated_at' => $evaluatedAt,
                'metadata' => array_merge($attempt->metadata ?? [], [
                    'evaluated_at' => $evaluatedAt->toIso8601String(),
                    'evaluation' => [
                        'raw_score' => round($rawScore, 2),
                        'max_score' => round($maxScore, 2),
                        'total_questions' => $totalQuestions,
                        'correct' => $correctCount,
                        'incorrect' => $incorrectCount,
                        'unanswered' => $unansweredCount,
                        'attempted' => $attemptedCount,
                        'forced' => $force,
                    ],
                ]),
            ])->save();

            return $this->buildAttemptSummary($attempt->fresh(['test', 'answers.question.group.passage']), $outcomes);
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function evaluateAnswer(ReadingAnswer $answer, ?ReadingQuestion $question = null): array
    {
        $question ??= $answer->question ?? ReadingQuestion::query()
            ->with(['correctAnswers', 'options', 'group.groupOptions', 'group.passage'])
            ->findOrFail($answer->question_id);

        $question->loadMissing(['correctAnswers', 'options', 'group.groupOptions', 'group.passage']);
        $type = $question->group?->question_type;
        $maxMarks = (float) ($question->marks ?: 1);
        $correct = $question->correctAnswers->first();

        if ($this->isStudentAnswerEmpty($answer, $type)) {
            return $this->markUnanswered($question, $maxMarks, $correct);
        }

        if ($correct === null) {
            return $this->buildOutcome(
                question: $question,
                status: 'incorrect',
                isCorrect: false,
                marksAwarded: 0,
                maxMarks: $maxMarks,
                studentDisplay: $this->studentDisplayValue($answer, $question),
                correctDisplay: '—',
                correctRaw: null,
                feedback: 'No correct answer configured.',
            );
        }

        if ($type === OfficialReadingQuestionType::MultipleChoiceMultiple) {
            return $this->compareMultiple($answer, $question, $correct, $maxMarks);
        }

        if ($type?->isCompletionBuilderType() || $type === OfficialReadingQuestionType::ShortAnswer || $type === OfficialReadingQuestionType::DiagramLabelCompletion) {
            return $this->compareAlternatives($answer, $question, $correct, $maxMarks);
        }

        return $this->compareSingle($answer, $question, $correct, $maxMarks);
    }

    public function normalizeAnswer(string $answer, array $settings = []): string
    {
        $caseSensitive = (bool) ($settings['case_sensitive'] ?? false);
        $allowHyphenSpace = (bool) ($settings['allow_hyphen_space'] ?? $settings['normalize_hyphen_space'] ?? true);

        $value = html_entity_decode(strip_tags($answer));
        $value = trim($value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = str_replace(["\u{2018}", "\u{2019}", '`'], "'", $value);

        if ($allowHyphenSpace) {
            $value = preg_replace('/[\-–—]+/u', ' ', $value) ?? $value;
            $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        }

        $value = trim($value, " \t\n\r\0\x0B.,;:!?\"'");

        if (! $caseSensitive) {
            $value = mb_strtolower($value);
        }

        return $this->normalizeObjectiveToken($value);
    }

    /**
     * @return array<string, mixed>
     */
    public function compareSingle(
        ReadingAnswer $answer,
        ReadingQuestion $question,
        ReadingCorrectAnswer $correct,
        float $maxMarks,
    ): array {
        $type = $question->group?->question_type;
        $studentRaw = trim((string) ($answer->answer ?? ''));
        $expectedRaw = trim((string) ($correct->answer ?? $correct->matching_key ?? ''));

        if ($type?->objectiveAnswerChoices() !== null) {
            $studentKey = $this->canonicalizeObjectiveAnswer($studentRaw, $type);
            $expectedKey = $this->canonicalizeObjectiveAnswer($expectedRaw, $type);
            $isCorrect = $studentKey !== '' && $studentKey === $expectedKey;

            return $this->buildOutcome(
                question: $question,
                status: $isCorrect ? 'correct' : 'incorrect',
                isCorrect: $isCorrect,
                marksAwarded: $isCorrect ? $maxMarks : 0,
                maxMarks: $maxMarks,
                studentDisplay: $this->formatObjectiveDisplay($studentKey, $type),
                correctDisplay: $this->formatObjectiveDisplay($expectedKey, $type),
                correctRaw: $expectedKey,
                studentRaw: $studentKey,
            );
        }

        if ($type?->isMatchingBuilderType()) {
            $studentKey = $this->canonicalizeOptionKey($studentRaw, $question);
            $expectedKey = $this->canonicalizeOptionKey($expectedRaw, $question);
            $isCorrect = $studentKey !== '' && $studentKey === $expectedKey;

            return $this->buildOutcome(
                question: $question,
                status: $isCorrect ? 'correct' : 'incorrect',
                isCorrect: $isCorrect,
                marksAwarded: $isCorrect ? $maxMarks : 0,
                maxMarks: $maxMarks,
                studentDisplay: $this->formatOptionDisplay($studentKey, $question),
                correctDisplay: $this->formatOptionDisplay($expectedKey, $question),
                correctRaw: $expectedKey,
                studentRaw: $studentKey,
            );
        }

        if ($type === OfficialReadingQuestionType::MultipleChoiceSingle) {
            $studentKey = $this->canonicalizeOptionKey($studentRaw, $question);
            $expectedKey = $this->canonicalizeOptionKey($expectedRaw, $question);
            $isCorrect = $studentKey !== '' && $studentKey === $expectedKey;

            return $this->buildOutcome(
                question: $question,
                status: $isCorrect ? 'correct' : 'incorrect',
                isCorrect: $isCorrect,
                marksAwarded: $isCorrect ? $maxMarks : 0,
                maxMarks: $maxMarks,
                studentDisplay: $this->formatOptionDisplay($studentKey, $question),
                correctDisplay: $this->formatOptionDisplay($expectedKey, $question),
                correctRaw: $expectedKey,
                studentRaw: $studentKey,
            );
        }

        return $this->compareAlternatives($answer, $question, $correct, $maxMarks);
    }

    /**
     * @return array<string, mixed>
     */
    public function compareMultiple(
        ReadingAnswer $answer,
        ReadingQuestion $question,
        ReadingCorrectAnswer $correct,
        float $maxMarks,
    ): array {
        $expected = collect($this->expectedSelectionKeys($correct))
            ->map(fn (string $value): string => $this->canonicalizeOptionKey($value, $question))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $student = collect(is_array($answer->answer_json) ? $answer->answer_json : [])
            ->map(fn (mixed $value): string => $this->canonicalizeOptionKey((string) $value, $question))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($expected->isEmpty()) {
            return $this->buildOutcome(
                question: $question,
                status: 'incorrect',
                isCorrect: false,
                marksAwarded: 0,
                maxMarks: $maxMarks,
                studentDisplay: $this->formatOptionListDisplay($student->all(), $question),
                correctDisplay: '—',
                correctRaw: null,
                feedback: 'No correct answer configured.',
            );
        }

        $allowPartial = (bool) ($question->metadata['allow_partial_marks'] ?? false);
        $exactMatch = $expected->values()->all() === $student->values()->all();
        $isCorrect = $exactMatch;

        if ($isCorrect) {
            $marksAwarded = $maxMarks;
        } elseif ($allowPartial) {
            $matched = $expected->intersect($student)->count();
            $extraWrong = $student->diff($expected)->count();
            $ratio = max(0, min(1, ($matched - $extraWrong) / max(1, $expected->count())));
            $marksAwarded = round($maxMarks * $ratio, 2);
            $isCorrect = $ratio >= 1;
        } else {
            $marksAwarded = 0;
        }

        return $this->buildOutcome(
            question: $question,
            status: $isCorrect ? 'correct' : 'incorrect',
            isCorrect: $isCorrect,
            marksAwarded: $marksAwarded,
            maxMarks: $maxMarks,
            studentDisplay: $this->formatOptionListDisplay($student->all(), $question),
            correctDisplay: $this->formatOptionListDisplay($expected->all(), $question),
            correctRaw: $expected->implode(', '),
            studentRaw: $student->implode(', '),
            extra: ['partial' => $allowPartial && ! $exactMatch && $marksAwarded > 0],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function compareAlternatives(
        ReadingAnswer $answer,
        ReadingQuestion $question,
        ReadingCorrectAnswer $correct,
        float $maxMarks,
    ): array {
        $settings = $this->answerSettings($question, $correct);
        $studentRaw = trim((string) ($answer->answer ?? ''));

        if ($this->violatesWordLimit($studentRaw, (string) ($settings['word_limit'] ?? ''))) {
            return $this->buildOutcome(
                question: $question,
                status: 'incorrect',
                isCorrect: false,
                marksAwarded: 0,
                maxMarks: $maxMarks,
                studentDisplay: $studentRaw,
                correctDisplay: $this->formatAcceptedAnswers($settings),
                correctRaw: $settings['answers'] ?? [],
                feedback: 'Answer exceeds the allowed word limit.',
                extra: ['word_limit_violation' => true],
            );
        }

        $studentNormalized = $this->normalizeAnswer($studentRaw, $settings);
        $acceptable = collect($settings['answers'] ?? [])
            ->map(fn (string $value): string => $this->normalizeAnswer($value, $settings))
            ->filter()
            ->unique()
            ->values();

        if (! empty($settings['regex'])) {
            $pattern = (string) $settings['regex'];
            $isRegexMatch = @preg_match($pattern, $studentRaw) === 1;

            if ($isRegexMatch) {
                return $this->buildOutcome(
                    question: $question,
                    status: 'correct',
                    isCorrect: true,
                    marksAwarded: $maxMarks,
                    maxMarks: $maxMarks,
                    studentDisplay: $studentRaw,
                    correctDisplay: $this->formatAcceptedAnswers($settings),
                    correctRaw: $settings['answers'] ?? [],
                    extra: ['matched_via' => 'regex'],
                );
            }
        }

        $isCorrect = $studentNormalized !== '' && $acceptable->contains($studentNormalized);

        return $this->buildOutcome(
            question: $question,
            status: $isCorrect ? 'correct' : 'incorrect',
            isCorrect: $isCorrect,
            marksAwarded: $isCorrect ? $maxMarks : 0,
            maxMarks: $maxMarks,
            studentDisplay: $studentRaw,
            correctDisplay: $this->formatAcceptedAnswers($settings),
            correctRaw: $settings['answers'] ?? [],
            studentRaw: $studentNormalized,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function markUnanswered(
        ReadingQuestion $question,
        float $maxMarks,
        ?ReadingCorrectAnswer $correct = null,
    ): array {
        $settings = $correct !== null ? $this->answerSettings($question, $correct) : ['answers' => []];

        return $this->buildOutcome(
            question: $question,
            status: 'unanswered',
            isCorrect: false,
            marksAwarded: 0,
            maxMarks: $maxMarks,
            studentDisplay: '—',
            correctDisplay: $correct !== null ? $this->formatCorrectDisplayForQuestion($question, $correct) : '—',
            correctRaw: $settings['answers'] ?? $correct?->answer,
            feedback: 'Unanswered',
        );
    }

    /**
     * @param  list<array<string, mixed>>|null  $outcomes
     * @return array<string, mixed>
     */
    public function calculateScore(?array $outcomes = null): array
    {
        $outcomes ??= [];

        $raw = round(collect($outcomes)->sum(fn (array $row): float => (float) ($row['marks_awarded'] ?? 0)), 2);
        $max = round(collect($outcomes)->sum(fn (array $row): float => (float) ($row['max_marks'] ?? 0)), 2);

        return [
            'raw_score' => $raw,
            'max_score' => $max,
            'correct' => collect($outcomes)->where('status', 'correct')->count(),
            'incorrect' => collect($outcomes)->where('status', 'incorrect')->count(),
            'unanswered' => collect($outcomes)->where('status', 'unanswered')->count(),
        ];
    }

    public function assertCanEvaluate(ReadingAttempt $attempt, ?int $userId = null): void
    {
        $userId ??= auth()->id();

        if ($userId === null || $attempt->user_id !== $userId) {
            throw new AuthorizationException('This attempt does not belong to you.');
        }
    }

    public function assertCanViewResult(ReadingAttempt $attempt): void
    {
        $user = auth()->user();

        if ($user === null) {
            abort(403);
        }

        if ($user->id === $attempt->user_id) {
            return;
        }

        if ($user->can('tests.view')) {
            return;
        }

        abort(403);
    }

    public function assertResultAvailable(ReadingAttempt $attempt): void
    {
        if ($attempt->status === TestAttemptStatus::InProgress) {
            abort(403, 'Results are not available for in-progress attempts.');
        }

        if (! $this->isAlreadyEvaluated($attempt) && $attempt->status !== TestAttemptStatus::Submitted) {
            abort(403, 'Results are not available yet.');
        }
    }

    private function isAlreadyEvaluated(ReadingAttempt $attempt): bool
    {
        return $attempt->status === TestAttemptStatus::Completed
            && $attempt->evaluated_at !== null;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function formatAcceptedAnswers(array $settings): string
    {
        $answers = $settings['answers'] ?? [];

        return is_array($answers) && $answers !== []
            ? implode(' / ', array_map('strval', $answers))
            : '—';
    }

    /**
     * @return array<string, mixed>
     */
    private function answerSettings(ReadingQuestion $question, ReadingCorrectAnswer $correct): array
    {
        $payload = is_array($correct->answer_json) ? $correct->answer_json : [];
        $answers = [];

        if (isset($payload['answers']) && is_array($payload['answers'])) {
            $answers = array_values(array_filter(array_map(
                static fn (mixed $value): string => trim((string) $value),
                $payload['answers'],
            )));
        }

        if ($answers === [] && filled($correct->answer)) {
            $answers = [trim((string) $correct->answer)];
        }

        if (isset($payload['alternatives']) && is_array($payload['alternatives'])) {
            $answers = array_values(array_unique(array_merge(
                $answers,
                array_map(static fn (mixed $value): string => trim((string) $value), $payload['alternatives']),
            )));
        }

        $wordLimit = (string) ($payload['word_limit'] ?? $question->group?->settings['answer_rule'] ?? ReadingCompletionAnswerRule::ThreeWords->value);

        return [
            'answers' => $answers,
            'case_sensitive' => (bool) ($payload['case_sensitive'] ?? false),
            'word_limit' => $wordLimit,
            'regex' => $payload['regex'] ?? null,
            'allow_hyphen_space' => (bool) ($payload['allow_hyphen_space'] ?? $payload['normalize_hyphen_space'] ?? true),
        ];
    }

    private function violatesWordLimit(string $answer, string $wordLimit): bool
    {
        if ($answer === '' || $wordLimit === '') {
            return false;
        }

        $rule = ReadingCompletionAnswerRule::tryFrom(mb_strtolower($wordLimit))
            ?? ReadingCompletionAnswerRule::tryFrom(str_replace(' ', '_', mb_strtolower($wordLimit)));

        $wordCount = $this->countWords($answer);

        return match ($rule) {
            ReadingCompletionAnswerRule::OneWord,
            ReadingCompletionAnswerRule::OneWordOnly,
            ReadingCompletionAnswerRule::OneWordAndOrNumber => $wordCount > 1,
            ReadingCompletionAnswerRule::TwoWords => $wordCount > 2,
            ReadingCompletionAnswerRule::ThreeWords => $wordCount > 3,
            ReadingCompletionAnswerRule::Custom => false,
            default => false,
        };
    }

    private function countWords(string $answer): int
    {
        $parts = preg_split('/\s+/u', trim($answer), -1, PREG_SPLIT_NO_EMPTY);

        return $parts === false ? 0 : count($parts);
    }

    /**
     * @return list<string>
     */
    private function expectedSelectionKeys(ReadingCorrectAnswer $correct): array
    {
        if (is_array($correct->answer_json) && $correct->answer_json !== []) {
            return array_values(array_filter(array_map(
                static fn (mixed $value): string => strtoupper(trim((string) $value)),
                $correct->answer_json,
            )));
        }

        if (filled($correct->answer)) {
            return [strtoupper(trim((string) $correct->answer))];
        }

        return [];
    }

    private function canonicalizeOptionKey(string $value, ReadingQuestion $question): string
    {
        $normalized = mb_strtoupper(trim($value));

        if ($normalized === '') {
            return '';
        }

        foreach ($this->optionsForQuestion($question) as $option) {
            $key = mb_strtoupper(trim((string) $option->option_key));
            $label = mb_strtoupper(trim(strip_tags((string) $option->option_label)));

            if ($normalized === $key || $normalized === $label) {
                return $key;
            }
        }

        return $normalized;
    }

    private function canonicalizeObjectiveAnswer(string $value, OfficialReadingQuestionType $type): string
    {
        $normalized = mb_strtoupper(trim($value));
        $normalized = str_replace(' ', '_', $normalized);

        return match ($type) {
            OfficialReadingQuestionType::TrueFalseNotGiven => match ($normalized) {
                'T', 'TRUE' => 'TRUE',
                'F', 'FALSE' => 'FALSE',
                'NG', 'NOT_GIVEN', 'NOTGIVEN' => 'NOT_GIVEN',
                default => $normalized,
            },
            OfficialReadingQuestionType::YesNoNotGiven => match ($normalized) {
                'Y', 'YES' => 'YES',
                'N', 'NO' => 'NO',
                'NG', 'NOT_GIVEN', 'NOTGIVEN' => 'NOT_GIVEN',
                default => $normalized,
            },
            default => $normalized,
        };
    }

    private function normalizeObjectiveToken(string $value): string
    {
        return match ($value) {
            't', 'true' => 'true',
            'f', 'false' => 'false',
            'ng', 'not given', 'notgiven', 'not_given' => 'not given',
            'y', 'yes' => 'yes',
            'n', 'no' => 'no',
            default => $value,
        };
    }

    /**
     * @return Collection<int, ReadingQuestionOption>
     */
    private function optionsForQuestion(ReadingQuestion $question): Collection
    {
        if ($question->options->isNotEmpty()) {
            return $question->options;
        }

        return $question->group?->groupOptions ?? collect();
    }

    private function formatOptionDisplay(string $key, ReadingQuestion $question): string
    {
        if ($key === '') {
            return '—';
        }

        foreach ($this->optionsForQuestion($question) as $option) {
            if (mb_strtoupper((string) $option->option_key) === $key) {
                return trim($key.' — '.strip_tags((string) $option->option_label));
            }
        }

        return $key;
    }

    /**
     * @param  list<string>  $keys
     */
    private function formatOptionListDisplay(array $keys, ReadingQuestion $question): string
    {
        if ($keys === []) {
            return '—';
        }

        return implode(', ', array_map(fn (string $key): string => $this->formatOptionDisplay($key, $question), $keys));
    }

    private function formatObjectiveDisplay(string $key, OfficialReadingQuestionType $type): string
    {
        if ($key === '') {
            return '—';
        }

        return match ($type) {
            OfficialReadingQuestionType::TrueFalseNotGiven => match ($key) {
                'TRUE' => 'True',
                'FALSE' => 'False',
                'NOT_GIVEN' => 'Not Given',
                default => $key,
            },
            OfficialReadingQuestionType::YesNoNotGiven => match ($key) {
                'YES' => 'Yes',
                'NO' => 'No',
                'NOT_GIVEN' => 'Not Given',
                default => $key,
            },
            default => $key,
        };
    }

    private function formatCorrectDisplayForQuestion(ReadingQuestion $question, ReadingCorrectAnswer $correct): string
    {
        $type = $question->group?->question_type;

        if ($type === OfficialReadingQuestionType::MultipleChoiceMultiple) {
            return $this->formatOptionListDisplay($this->expectedSelectionKeys($correct), $question);
        }

        if ($type?->objectiveAnswerChoices() !== null) {
            return $this->formatObjectiveDisplay(
                $this->canonicalizeObjectiveAnswer((string) ($correct->answer ?? ''), $type),
                $type,
            );
        }

        if ($type?->isMatchingBuilderType() || $type === OfficialReadingQuestionType::MultipleChoiceSingle) {
            return $this->formatOptionDisplay(
                $this->canonicalizeOptionKey((string) ($correct->answer ?? $correct->matching_key ?? ''), $question),
                $question,
            );
        }

        return $this->formatAcceptedAnswers($this->answerSettings($question, $correct));
    }

    private function studentDisplayValue(ReadingAnswer $answer, ReadingQuestion $question): string
    {
        $type = $question->group?->question_type;

        if ($type === OfficialReadingQuestionType::MultipleChoiceMultiple) {
            $keys = is_array($answer->answer_json) ? $answer->answer_json : [];

            return $this->formatOptionListDisplay(
                array_map(fn (mixed $value): string => $this->canonicalizeOptionKey((string) $value, $question), $keys),
                $question,
            );
        }

        return trim((string) ($answer->answer ?? '')) ?: '—';
    }

    private function isStudentAnswerEmpty(ReadingAnswer $answer, ?OfficialReadingQuestionType $type): bool
    {
        if ($type === OfficialReadingQuestionType::MultipleChoiceMultiple) {
            return ! is_array($answer->answer_json) || $answer->answer_json === [];
        }

        return trim((string) ($answer->answer ?? '')) === '';
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function buildOutcome(
        ReadingQuestion $question,
        string $status,
        bool $isCorrect,
        float $marksAwarded,
        float $maxMarks,
        string $studentDisplay,
        string $correctDisplay,
        mixed $correctRaw,
        ?string $studentRaw = null,
        ?string $feedback = null,
        array $extra = [],
    ): array {
        $group = $question->group;

        return array_merge([
            'question_id' => $question->id,
            'question_number' => $question->question_number,
            'question_type' => $group?->question_type?->value,
            'question_type_label' => $group?->question_type?->label(),
            'passage_id' => $group?->passage_id,
            'passage_title' => $group?->passage?->title,
            'paragraph_reference' => $question->paragraph_reference,
            'prompt' => $question->prompt,
            'explanation' => $question->explanation,
            'status' => $status,
            'is_correct' => $isCorrect,
            'marks_awarded' => round($marksAwarded, 2),
            'max_marks' => round($maxMarks, 2),
            'student_answer' => $studentRaw ?? $studentDisplay,
            'student_answer_display' => $studentDisplay,
            'correct_answer' => $correctRaw,
            'correct_answer_display' => $correctDisplay,
            'feedback' => $feedback,
            'evaluation_json' => [
                'status' => $status,
                'feedback' => $feedback,
            ],
        ], $extra);
    }

    private function calculateTimeSpent(ReadingAttempt $attempt): int
    {
        $duration = $this->timer->durationSeconds($attempt);

        if ($attempt->submitted_at !== null && $attempt->started_at !== null) {
            return (int) max(0, min($duration, $attempt->started_at->diffInSeconds($attempt->submitted_at)));
        }

        $remaining = max(0, (int) $attempt->remaining_seconds);

        return max(0, $duration - $remaining);
    }

    /**
     * @param  list<array<string, mixed>>|null  $outcomes
     * @return array<string, mixed>
     */
    public function buildAttemptSummary(ReadingAttempt $attempt, ?array $outcomes = null): array
    {
        $metadata = $attempt->metadata['evaluation'] ?? [];
        $totalQuestions = (int) ($metadata['total_questions'] ?? 0);

        if ($totalQuestions === 0) {
            $attempt->loadMissing('test');
            $test = $this->renderer->loadForRenderer($attempt->test);
            foreach ($test->passages as $passage) {
                $totalQuestions += $this->renderer->questionsForPassage($passage)->count();
            }
        }

        $rawScore = (float) ($attempt->score ?? $metadata['raw_score'] ?? 0);
        $correct = (int) ($metadata['correct'] ?? 0);
        $incorrect = (int) ($metadata['incorrect'] ?? 0);
        $unanswered = (int) ($metadata['unanswered'] ?? 0);
        $attempted = (int) ($metadata['attempted'] ?? ($correct + $incorrect));

        return [
            'attempt_id' => $attempt->uuid,
            'status' => $attempt->status?->value,
            'test_title' => $attempt->test?->title,
            'exam_type' => $attempt->test?->exam_type?->label(),
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            'evaluated_at' => $attempt->evaluated_at?->toIso8601String(),
            'duration_minutes' => $attempt->test?->duration_minutes,
            'time_spent_seconds' => (int) $attempt->time_spent,
            'total_questions' => $totalQuestions,
            'attempted' => $attempted,
            'unanswered' => $unanswered,
            'correct' => $correct,
            'incorrect' => $incorrect,
            'raw_score' => $rawScore,
            'max_score' => (float) ($metadata['max_score'] ?? $totalQuestions),
            'band' => (float) ($attempt->band ?? 0),
            'outcomes' => $outcomes,
        ];
    }
}
