<?php

declare(strict_types=1);

use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\PassageStatus;
use App\Models\ReadingAnswer;
use App\Models\ReadingQuestionGroup;
use App\Services\Exam\ReadingMultipleChoiceMultipleCountingService;
use Illuminate\Support\Collection;

it('marks every number in a mcq multiple group answered only when selection count matches range', function (): void {
    $service = app(ReadingMultipleChoiceMultipleCountingService::class);

    $group = new ReadingQuestionGroup([
        'question_type' => OfficialReadingQuestionType::MultipleChoiceMultiple,
        'start_question' => 1,
        'end_question' => 3,
        'status' => PassageStatus::Published,
    ]);

    expect($service->groupQuestionCount($group))->toBe(3);
    expect($service->groupQuestionNumbers($group))->toBe([1, 2, 3]);

    $partial = new ReadingAnswer(['answer_json' => ['A', 'B']]);
    $complete = new ReadingAnswer(['answer_json' => ['A', 'B', 'C']]);

    expect($service->isQuestionNumberAnsweredInGroup(1, $group, $partial))->toBeFalse();
    expect($service->isQuestionNumberAnsweredInGroup(1, $group, $complete))->toBeTrue();
    expect($service->isQuestionNumberAnsweredInGroup(3, $group, $complete))->toBeTrue();
});

it('maps every number in a mcq multiple range when group is complete', function (): void {
    $service = app(ReadingMultipleChoiceMultipleCountingService::class);

    $group = new ReadingQuestionGroup([
        'question_type' => OfficialReadingQuestionType::MultipleChoiceMultiple,
        'start_question' => 3,
        'end_question' => 4,
        'status' => PassageStatus::Published,
    ]);

    $complete = new ReadingAnswer(['answer_json' => ['C', 'E']]);

    expect($service->answeredStateByQuestionNumber($group, $complete))->toBe([
        3 => true,
        4 => true,
    ]);
});

it('resolves the primary answer from the first question with selections', function (): void {
    $service = app(ReadingMultipleChoiceMultipleCountingService::class);

    $group = new ReadingQuestionGroup([
        'question_type' => OfficialReadingQuestionType::MultipleChoiceMultiple,
        'start_question' => 1,
        'end_question' => 2,
        'status' => PassageStatus::Published,
    ]);

    $question = new \App\Models\ReadingQuestion([
        'id' => 99,
        'question_number' => 1,
        'prompt' => 'Choose two',
        'marks' => 1,
        'sort_order' => 1,
    ]);

    $group->setRelation('questions', collect([$question]));

    $answer = new ReadingAnswer([
        'question_id' => $question->id,
        'answer_json' => ['B', 'D'],
    ]);

    $resolved = $service->resolvePrimaryAnswer($group, Collection::make([
        $question->id => $answer,
    ]));

    expect($resolved?->answer_json)->toBe(['B', 'D']);
    expect($service->countSelected($resolved?->answer_json))->toBe(2);
});
