<?php

declare(strict_types=1);

use App\Enums\Listening\ListeningMatchStatus;
use App\Models\Listening\ListeningAttemptAnswerEvaluation;
use App\Models\Listening\ListeningSection;
use App\Services\Listening\Result\ListeningResultBreakdownService;
use Illuminate\Support\Collection;

it('calculates section and type breakdown totals', function (): void {
    $rows = collect([
        makeResultAnswerEvaluationRow(1, 'short_answer', ListeningMatchStatus::Correct, 1),
        makeResultAnswerEvaluationRow(2, 'mcq', ListeningMatchStatus::Incorrect, 0),
    ]);

    $sections = Collection::make([
        new ListeningSection([
            'section_number' => 1,
            'start_question_number' => 1,
            'end_question_number' => 2,
        ]),
    ]);

    $breakdown = app(ListeningResultBreakdownService::class);

    $sectionData = $breakdown->buildSectionBreakdown($rows, $sections);
    $typeData = $breakdown->buildQuestionTypeBreakdown($rows);
    $totals = $breakdown->calculateTotals($rows);

    expect($sectionData)->toHaveCount(1)
        ->and($sectionData[0]['correct'])->toBe(1.0)
        ->and($sectionData[0]['incorrect'])->toBe(1.0)
        ->and($typeData)->toHaveCount(2)
        ->and($totals['raw_score'])->toBe(1.0)
        ->and($totals['total_unanswered'])->toBe(0);
});

it('builds question summary items without normalization debug for student mapping', function (): void {
    $rows = collect([
        makeResultAnswerEvaluationRow(1, 'short_answer', ListeningMatchStatus::Correct, 1),
    ]);

    $sections = Collection::make([
        new ListeningSection([
            'section_number' => 1,
            'start_question_number' => 1,
            'end_question_number' => 1,
        ]),
    ]);

    $items = app(ListeningResultBreakdownService::class)->buildQuestionSummaryItems($rows, $sections);
    $studentView = $items[0]->toStudentArray(true, false);

    expect($studentView)->toHaveKeys(['question_number', 'student_answer', 'is_correct', 'correct_answer'])
        ->and($studentView)->not->toHaveKey('normalization_steps')
        ->and($studentView)->not->toHaveKey('normalized_answer');
});

function makeResultAnswerEvaluationRow(
    int $questionNumber,
    string $type,
    ListeningMatchStatus $status,
    float $awarded,
): ListeningAttemptAnswerEvaluation {
    return new ListeningAttemptAnswerEvaluation([
        'listening_attempt_evaluation_id' => 1,
        'listening_attempt_id' => 1,
        'listening_question_id' => $questionNumber,
        'question_number' => $questionNumber,
        'question_type' => $type,
        'student_answer_snapshot' => [['value' => 'test']],
        'normalized_student_answer' => ['value' => 'test'],
        'correct_answer_snapshot' => [['value' => 'test']],
        'accepted_answers_snapshot' => [['value' => 'alt']],
        'is_correct' => $status === ListeningMatchStatus::Correct,
        'marks_available' => 1,
        'marks_awarded' => $awarded,
        'match_status' => $status,
        'match_reason' => 'exact_match',
        'normalization_steps' => [['step' => 'trim']],
    ]);
}
