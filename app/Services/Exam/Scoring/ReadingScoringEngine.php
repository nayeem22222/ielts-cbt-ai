<?php

declare(strict_types=1);

namespace App\Services\Exam\Scoring;

use App\Enums\Commerce\IeltsModule;
use App\Enums\Exam\ResultStatus;
use App\Enums\Exam\ScoringMethod;
use App\Enums\Exam\TestAttemptStatus;
use App\Models\BandScore;
use App\Models\Question;
use App\Models\Result;
use App\Models\ResultQuestionScore;
use App\Models\StudentAnswer;
use App\Models\TestAttempt;
use App\Models\TestQuestion;
use App\Services\Exam\Analytics\ReadingAnalyticsBuilder;
use App\Services\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReadingScoringEngine extends Service
{
    public function __construct(
        private readonly ReadingAnswerMatcher $matcher,
        private readonly ReadingBandConverter $bandConverter,
        private readonly ReadingResultStatisticsBuilder $statisticsBuilder,
        private readonly ReadingAnalyticsBuilder $analyticsBuilder,
    ) {
    }

    public function scoreAttempt(TestAttempt $attempt): Result
    {
        if ($attempt->result()->exists()) {
            return $attempt->result()->first()->load(['questionScores', 'statistics', 'bandScores']);
        }

        return DB::transaction(function () use ($attempt): Result {
            $attempt->load([
                'test',
                'answers',
                'module.sections',
            ]);

            $questions = $this->loadQuestions($attempt);
            $answers = $attempt->answers->keyBy('question_id');

            $outcomes = $questions->map(function (Question $question) use ($answers): array {
                $studentAnswer = $answers->get($question->id);
                $outcome = $this->matcher->score($question, $studentAnswer);
                $pivot = $question->getRelation('pivotQuestion');

                return compact('question', 'studentAnswer', 'outcome', 'pivot');
            });

            $rawScore = round($outcomes->sum(fn (array $row): float => $row['outcome']->scoreAwarded), 2);
            $maxScore = round($outcomes->sum(fn (array $row): float => $row['outcome']->maxScore), 2);
            $band = $this->bandConverter->bandFromScores($rawScore, $maxScore);
            $correctCount = $outcomes->filter(fn (array $row): bool => $row['outcome']->isCorrect)->count();

            $result = Result::query()->create([
                'test_attempt_id' => $attempt->id,
                'overall_band' => $band,
                'raw_score' => $rawScore,
                'max_score' => $maxScore,
                'status' => ResultStatus::Computed,
                'computed_at' => now(),
                'metadata' => [
                    'module' => IeltsModule::Reading->value,
                    'equivalent_raw_out_of_40' => $maxScore > 0 ? round(($rawScore / $maxScore) * 40, 2) : 0,
                ],
            ]);

            BandScore::query()->create([
                'result_id' => $result->id,
                'module' => IeltsModule::Reading->value,
                'band' => $band,
                'raw_score' => $rawScore,
                'max_score' => $maxScore,
                'correct_count' => $correctCount,
                'total_count' => $questions->count(),
                'scoring_method' => ScoringMethod::Auto,
            ]);

            $questionScores = $outcomes->map(function (array $row) use ($result): ResultQuestionScore {
                /** @var Question $question */
                $question = $row['question'];
                /** @var QuestionScoreOutcome $outcome */
                $outcome = $row['outcome'];
                /** @var StudentAnswer|null $studentAnswer */
                $studentAnswer = $row['studentAnswer'];
                /** @var TestQuestion|null $pivot */
                $pivot = $row['pivot'];

                return ResultQuestionScore::query()->create([
                    'result_id' => $result->id,
                    'question_id' => $question->id,
                    'student_answer_id' => $studentAnswer?->id,
                    'test_section_id' => $pivot?->test_section_id,
                    'question_type' => $question->type,
                    'question_number' => $question->question_number,
                    'student_response' => $outcome->studentResponse,
                    'expected_response' => $outcome->expectedResponse,
                    'is_correct' => $outcome->isCorrect,
                    'score_awarded' => $outcome->scoreAwarded,
                    'max_score' => $outcome->maxScore,
                    'partial_ratio' => $outcome->partialRatio,
                    'feedback' => $outcome->feedback,
                ]);
            });

            $this->statisticsBuilder->build($result, $attempt, $questionScores);

            StudentAnswer::query()
                ->where('test_attempt_id', $attempt->id)
                ->update(['is_final' => true]);

            $attempt->update([
                'status' => TestAttemptStatus::Completed,
                'submitted_at' => now(),
                'completed_at' => now(),
            ]);

            $result = $result->fresh(['questionScores', 'statistics', 'bandScores']);
            $this->analyticsBuilder->buildForAttempt($attempt->fresh(), $result);

            return $result;
        });
    }

    /**
     * @return Collection<int, Question>
     */
    private function loadQuestions(TestAttempt $attempt): Collection
    {
        $pivots = TestQuestion::query()
            ->where('test_id', $attempt->test_id)
            ->when($attempt->test_module_id, fn ($query) => $query->where('test_module_id', $attempt->test_module_id))
            ->with(['question.options', 'question.correctAnswer'])
            ->orderBy('sort_order')
            ->get();

        return $pivots->map(function (TestQuestion $pivot): Question {
            $question = $pivot->question;
            $question->setRelation('pivotQuestion', $pivot);

            return $question;
        });
    }
}
