<?php

declare(strict_types=1);

namespace App\Services\Admin\Exam;

use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\PassageStatus;
use App\Enums\Exam\ReadingCompletionAnswerRule;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingQuestionOption;
use App\Models\ReadingTest;
use App\Support\Reading\CompletionAnswerPayload;
use App\Support\Reading\CompletionPlaceholderParser;
use App\Support\Reading\ReadingValidationIssue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReadingTestValidationService
{
    public function __construct(private readonly ReadingCompletionTemplateService $template)
    {
    }

    /**
     * @return array{
     *     is_valid: bool,
     *     errors: list<array<string, mixed>>,
     *     warnings: list<array<string, mixed>>,
     *     summary: array<string, int>
     * }
     */
    public function validateTest(ReadingTest $test): array
    {
        $test = $this->loadTest($test);
        $errors = [];
        $warnings = [];

        if (trim((string) $test->title) === '') {
            $errors[] = ReadingValidationIssue::make(
                'test_title_missing',
                'Reading test title is required.',
                'reading_test',
                $test->id,
                'Add a title in Reading Test settings.',
                route('admin.reading-tests.edit', $test),
            );
        }

        if (trim((string) $test->slug) === '') {
            $errors[] = ReadingValidationIssue::make(
                'test_slug_missing',
                'Reading test slug is required.',
                'reading_test',
                $test->id,
                'Add a unique slug in Reading Test settings.',
                route('admin.reading-tests.edit', $test),
            );
        } elseif (ReadingTest::query()->where('slug', $test->slug)->whereKeyNot($test->id)->exists()) {
            $errors[] = ReadingValidationIssue::make(
                'test_slug_duplicate',
                "Slug \"{$test->slug}\" is already used by another reading test.",
                'reading_test',
                $test->id,
                'Choose a unique slug.',
                route('admin.reading-tests.edit', $test),
            );
        }

        if (! $test->exam_type instanceof ExamType) {
            $errors[] = ReadingValidationIssue::make(
                'test_exam_type_invalid',
                'Reading test exam type is invalid.',
                'reading_test',
                $test->id,
                'Select Academic or General Training.',
                route('admin.reading-tests.edit', $test),
            );
        }

        if ((int) $test->duration_minutes < 1) {
            $errors[] = ReadingValidationIssue::make(
                'test_duration_invalid',
                'Reading test duration must be at least 1 minute.',
                'reading_test',
                $test->id,
                'Set a valid duration in test settings.',
                route('admin.reading-tests.edit', $test),
            );
        }

        if (! $test->status instanceof PublishStatus) {
            $errors[] = ReadingValidationIssue::make(
                'test_status_invalid',
                'Reading test status is invalid.',
                'reading_test',
                $test->id,
                'Set status to Draft, Published, or Archived.',
                route('admin.reading-tests.edit', $test),
            );
        }

        return $this->result($test, $errors, $warnings);
    }

    /**
     * @return array{
     *     is_valid: bool,
     *     errors: list<array<string, mixed>>,
     *     warnings: list<array<string, mixed>>,
     *     summary: array<string, int>
     * }
     */
    public function validatePassages(ReadingTest $test): array
    {
        $test = $this->loadTest($test);
        $errors = [];
        $warnings = [];
        $passages = $test->passages;
        $seenPartNumbers = [];
        $ranges = [];

        foreach ($passages as $passage) {
            if ((int) $passage->reading_test_id !== (int) $test->id) {
                $errors[] = ReadingValidationIssue::make(
                    'passage_wrong_test',
                    "Passage \"{$passage->title}\" does not belong to this reading test.",
                    'reading_passage',
                    $passage->id,
                    'Remove or reassign the passage.',
                    $this->builderLink($test, $passage),
                );
            }

            if ($passage->part_number === null || (int) $passage->part_number < 1) {
                $errors[] = ReadingValidationIssue::make(
                    'passage_part_missing',
                    "Passage \"{$passage->title}\" is missing a part number.",
                    'reading_passage',
                    $passage->id,
                    'Set part number 1–3.',
                    $this->builderLink($test, $passage),
                );
            } elseif (isset($seenPartNumbers[(int) $passage->part_number])) {
                $errors[] = ReadingValidationIssue::make(
                    'passage_part_duplicate',
                    "Duplicate part number {$passage->part_number} on passage \"{$passage->title}\".",
                    'reading_passage',
                    $passage->id,
                    'Assign unique part numbers within the test.',
                    $this->builderLink($test, $passage),
                );
            } else {
                $seenPartNumbers[(int) $passage->part_number] = true;
            }

            if (trim((string) $passage->title) === '') {
                $errors[] = ReadingValidationIssue::make(
                    'passage_title_missing',
                    'Passage title is required.',
                    'reading_passage',
                    $passage->id,
                    'Add a passage title.',
                    $this->builderLink($test, $passage),
                );
            }

            $content = trim(strip_tags((string) ($passage->content_html ?: $passage->content_text)));
            if ($content === '') {
                $errors[] = ReadingValidationIssue::make(
                    'passage_content_missing',
                    "Passage \"{$passage->title}\" has no content.",
                    'reading_passage',
                    $passage->id,
                    'Add passage text in the builder.',
                    $this->builderLink($test, $passage),
                );
            }

            if ($passage->start_question === null || $passage->end_question === null) {
                $errors[] = ReadingValidationIssue::make(
                    'passage_range_missing',
                    "Passage \"{$passage->title}\" is missing a question range.",
                    'reading_passage',
                    $passage->id,
                    'Set start and end question numbers.',
                    $this->builderLink($test, $passage),
                );
            } elseif ((int) $passage->start_question > (int) $passage->end_question) {
                $errors[] = ReadingValidationIssue::make(
                    'passage_range_invalid',
                    "Passage \"{$passage->title}\" has an invalid question range.",
                    'reading_passage',
                    $passage->id,
                    'Ensure start question is less than or equal to end question.',
                    $this->builderLink($test, $passage),
                );
            } else {
                $ranges[] = [
                    'passage_id' => $passage->id,
                    'title' => $passage->title,
                    'start' => (int) $passage->start_question,
                    'end' => (int) $passage->end_question,
                ];
            }

            if ((int) $passage->sort_order < 1) {
                $warnings[] = ReadingValidationIssue::make(
                    'passage_sort_order',
                    "Passage \"{$passage->title}\" has invalid sort order.",
                    'reading_passage',
                    $passage->id,
                    'Reorder passages in the builder.',
                    $this->builderLink($test, $passage),
                );
            }
        }

        foreach ($ranges as $index => $range) {
            foreach (array_slice($ranges, $index + 1) as $other) {
                if ($this->rangesOverlap($range['start'], $range['end'], $other['start'], $other['end'])) {
                    $errors[] = ReadingValidationIssue::make(
                        'passage_range_overlap',
                        "Passage ranges overlap: \"{$range['title']}\" (Q{$range['start']}–{$range['end']}) and \"{$other['title']}\" (Q{$other['start']}–{$other['end']}).",
                        'reading_passage',
                        $range['passage_id'],
                        'Adjust passage question ranges so they do not overlap.',
                        $this->builderLink($test, ReadingPassage::query()->find($range['passage_id'])),
                    );
                }
            }
        }

        if ($passages->isEmpty()) {
            $errors[] = ReadingValidationIssue::make(
                'passages_missing',
                'Reading test has no passages.',
                'reading_test',
                $test->id,
                'Add at least one passage before publishing.',
                route('admin.reading-tests.builder', $test),
            );
        }

        return $this->result($test, $errors, $warnings);
    }

    /**
     * @return array{
     *     is_valid: bool,
     *     errors: list<array<string, mixed>>,
     *     warnings: list<array<string, mixed>>,
     *     summary: array<string, int>
     * }
     */
    public function validateQuestionGroups(ReadingTest $test): array
    {
        $test = $this->loadTest($test);
        $errors = [];
        $warnings = [];

        foreach ($test->passages as $passage) {
            $groupRanges = [];

            foreach ($passage->groups as $group) {
                if ((int) $group->passage_id !== (int) $passage->id) {
                    $errors[] = ReadingValidationIssue::make(
                        'group_wrong_passage',
                        "Question group \"{$group->title}\" does not belong to passage \"{$passage->title}\".",
                        'reading_question_group',
                        $group->id,
                        'Reassign or recreate the group under the correct passage.',
                        $this->builderLink($test, $passage, $group),
                    );
                }

                if (! $group->question_type instanceof OfficialReadingQuestionType) {
                    $errors[] = ReadingValidationIssue::make(
                        'group_type_missing',
                        "Question group \"{$group->title}\" has no question type.",
                        'reading_question_group',
                        $group->id,
                        'Select an official IELTS question type.',
                        $this->builderLink($test, $passage, $group),
                    );
                }

                if ($group->start_question === null || $group->end_question === null) {
                    $errors[] = ReadingValidationIssue::make(
                        'group_range_missing',
                        "Question group \"{$group->title}\" is missing a question range.",
                        'reading_question_group',
                        $group->id,
                        'Set start and end question numbers.',
                        $this->builderLink($test, $passage, $group),
                    );
                } elseif ((int) $group->start_question > (int) $group->end_question) {
                    $errors[] = ReadingValidationIssue::make(
                        'group_range_invalid',
                        "Question group \"{$group->title}\" has start question greater than end question.",
                        'reading_question_group',
                        $group->id,
                        'Correct the group question range.',
                        $this->builderLink($test, $passage, $group),
                    );
                } else {
                    $groupRanges[] = [
                        'group_id' => $group->id,
                        'title' => $group->title,
                        'start' => (int) $group->start_question,
                        'end' => (int) $group->end_question,
                    ];

                    if ($passage->start_question !== null && $passage->end_question !== null) {
                        if ($group->start_question < $passage->start_question || $group->end_question > $passage->end_question) {
                            $errors[] = ReadingValidationIssue::make(
                                'group_outside_passage_range',
                                "Group \"{$group->title}\" range Q{$group->question_range_label} is outside passage range Q{$passage->start_question}–{$passage->end_question}.",
                                'reading_question_group',
                                $group->id,
                                'Adjust the group range to fit inside the passage range.',
                                $this->builderLink($test, $passage, $group),
                            );
                        }
                    }
                }

                if (trim((string) $group->instruction) === '') {
                    $warnings[] = ReadingValidationIssue::make(
                        'group_instruction_missing',
                        "Question group \"{$group->title}\" has no instruction text.",
                        'reading_question_group',
                        $group->id,
                        'Add an instruction for candidates.',
                        $this->builderLink($test, $passage, $group),
                    );
                }

                if ($group->status === PassageStatus::Published && $group->questions->where('question_number', '>', 0)->isEmpty()) {
                    $errors[] = ReadingValidationIssue::make(
                        'published_group_no_questions',
                        "Published group \"{$group->title}\" has no questions.",
                        'reading_question_group',
                        $group->id,
                        'Add questions or set group status to Draft.',
                        $this->builderLink($test, $passage, $group),
                    );
                }
            }

            foreach ($groupRanges as $index => $range) {
                foreach (array_slice($groupRanges, $index + 1) as $other) {
                    if ($this->rangesOverlap($range['start'], $range['end'], $other['start'], $other['end'])) {
                        $errors[] = ReadingValidationIssue::make(
                            'group_range_overlap',
                            "Group ranges overlap in passage \"{$passage->title}\": \"{$range['title']}\" and \"{$other['title']}\".",
                            'reading_question_group',
                            $range['group_id'],
                            'Adjust group ranges so they do not overlap.',
                            $this->builderLink($test, $passage, ReadingQuestionGroup::query()->find($range['group_id'])),
                        );
                    }
                }
            }
        }

        if ($test->passages->flatMap(fn (ReadingPassage $passage) => $passage->groups)->isEmpty()) {
            $errors[] = ReadingValidationIssue::make(
                'groups_missing',
                'Reading test has no question groups.',
                'reading_test',
                $test->id,
                'Add at least one question group before publishing.',
                route('admin.reading-tests.builder', $test),
            );
        }

        return $this->result($test, $errors, $warnings);
    }

    /**
     * @return array{
     *     is_valid: bool,
     *     errors: list<array<string, mixed>>,
     *     warnings: list<array<string, mixed>>,
     *     summary: array<string, int>
     * }
     */
    public function validateQuestions(ReadingTest $test): array
    {
        $test = $this->loadTest($test);
        $errors = [];
        $warnings = [];
        $questionNumbers = [];

        foreach ($test->passages as $passage) {
            foreach ($passage->groups as $group) {
                $sortOrders = [];

                foreach ($group->questions as $question) {
                    if ((int) $question->group_id !== (int) $group->id) {
                        $errors[] = ReadingValidationIssue::make(
                            'question_wrong_group',
                            "Question {$question->id} does not belong to group \"{$group->title}\".",
                            'reading_question',
                            $question->id,
                            'Move or delete the orphan question.',
                            $this->builderLink($test, $passage, $group),
                        );
                    }

                    $number = (int) $question->question_number;

                    if ($number < 1) {
                        $errors[] = ReadingValidationIssue::make(
                            'question_number_missing',
                            "Question in group \"{$group->title}\" is missing a question number.",
                            'reading_question',
                            $question->id,
                            'Assign a valid question number.',
                            $this->groupBuilderLink($group),
                        );

                        continue;
                    }

                    if ($group->start_question !== null && $group->end_question !== null) {
                        if ($number < $group->start_question || $number > $group->end_question) {
                            $errors[] = ReadingValidationIssue::make(
                                'question_outside_group_range',
                                "Question {$number} is outside group \"{$group->title}\" range Q{$group->question_range_label}.",
                                'reading_question',
                                $question->id,
                                'Move the question into the group range or update the group range.',
                                $this->builderLink($test, $passage, $group),
                            );
                        }
                    }

                    if (isset($questionNumbers[$number])) {
                        $errors[] = ReadingValidationIssue::make(
                            'question_number_duplicate',
                            "Duplicate question number {$number} in this reading test.",
                            'reading_question',
                            $question->id,
                            'Assign unique question numbers across the test.',
                            $this->builderLink($test, $passage, $group),
                        );
                    } else {
                        $questionNumbers[$number] = $question->id;
                    }

                    if ($this->questionRequiresPrompt($group) && trim((string) $question->prompt) === '' && ! $group->question_type?->usesCompletionTemplate() && ! $group->question_type?->isDiagramBuilderType()) {
                        $errors[] = ReadingValidationIssue::make(
                            'question_prompt_missing',
                            "Question {$number} in group \"{$group->title}\" is missing prompt text.",
                            'reading_question',
                            $question->id,
                            'Add question text in the group builder.',
                            $this->groupBuilderLink($group),
                        );
                    }

                    $sortOrder = (int) $question->sort_order;
                    if (isset($sortOrders[$sortOrder])) {
                        $warnings[] = ReadingValidationIssue::make(
                            'question_sort_duplicate',
                            "Duplicate sort order {$sortOrder} in group \"{$group->title}\".",
                            'reading_question',
                            $question->id,
                            'Reorder questions in the builder.',
                            $this->groupBuilderLink($group),
                        );
                    }
                    $sortOrders[$sortOrder] = true;

                    $answer = $question->correctAnswers->first();
                    if (trim((string) ($answer?->answer ?? '')) === '' && ! $this->hasStructuredAnswers($answer)) {
                        $errors[] = ReadingValidationIssue::make(
                            'question_answer_missing',
                            "Question {$number} is missing a correct answer.",
                            'reading_question',
                            $question->id,
                            'Add the correct answer in the question builder.',
                            $this->groupBuilderLink($group),
                        );
                    }
                }

                $this->validateGroupQuestionCount($group, $passage, $test, $errors);
                $this->validateCompletionTemplate($group, $passage, $test, $errors, $warnings);
                $this->validateDiagramGroup($group, $passage, $test, $errors, $warnings);
            }
        }

        if ($test->passages->flatMap(fn (ReadingPassage $p) => $p->groups->flatMap(fn (ReadingQuestionGroup $g) => $g->questions))->isEmpty()) {
            $errors[] = ReadingValidationIssue::make(
                'questions_missing',
                'Reading test has no questions.',
                'reading_test',
                $test->id,
                'Add questions to question groups before publishing.',
                route('admin.reading-tests.builder', $test),
            );
        }

        return $this->result($test, $errors, $warnings);
    }

    /**
     * @return array{
     *     is_valid: bool,
     *     errors: list<array<string, mixed>>,
     *     warnings: list<array<string, mixed>>,
     *     summary: array<string, int>
     * }
     */
    public function validateOptions(ReadingQuestionGroup $group): array
    {
        $group = $this->loadGroup($group);
        $errors = [];
        $warnings = [];
        $type = $group->question_type;
        $passage = $group->passage;
        $test = $passage?->test;

        if (! $type instanceof OfficialReadingQuestionType) {
            return $this->result($test ?? new ReadingTest, $errors, $warnings);
        }

        if ($type->isMatchingBuilderType()) {
            $options = $group->groupOptions;
            if ($options->isEmpty()) {
                $errors[] = ReadingValidationIssue::make(
                    'matching_options_missing',
                    "Matching group \"{$group->title}\" has no options.",
                    'reading_question_group',
                    $group->id,
                    'Add matching options in the builder.',
                    $this->groupBuilderLink($group),
                );
            }

            $keys = [];
            foreach ($options as $option) {
                $this->validateOptionRow($option, $group, $test, $passage, $errors, $keys, true);
            }

            foreach ($group->questions as $question) {
                $answerKey = (string) ($question->correctAnswers->first()?->answer ?? '');
                if ($answerKey !== '' && ! isset($keys[$answerKey])) {
                    $errors[] = ReadingValidationIssue::make(
                        'matching_answer_option_missing',
                        "Question {$question->question_number} correct answer \"{$answerKey}\" does not match any option key.",
                        'reading_question',
                        $question->id,
                        'Update the correct answer or add the missing option.',
                        $this->groupBuilderLink($group),
                    );
                }
            }
        }

        if ($type->usesPerQuestionOptions()) {
            foreach ($group->questions as $question) {
                if ($question->options->isEmpty()) {
                    $errors[] = ReadingValidationIssue::make(
                        'mcq_options_missing',
                        "Question {$question->question_number} has no MCQ options.",
                        'reading_question',
                        $question->id,
                        'Add at least two options.',
                        $this->groupBuilderLink($group),
                    );

                    continue;
                }

                $keys = [];
                foreach ($question->options as $option) {
                    $this->validateOptionRow($option, $group, $test, $passage, $errors, $keys, false);
                }

                $answer = $question->correctAnswers->first();
                if ($type->allowsMultipleCorrectAnswers()) {
                    $selected = is_array($answer?->answer_json) ? $answer->answer_json : [];
                    foreach ($selected as $key) {
                        if (! isset($keys[(string) $key])) {
                            $errors[] = ReadingValidationIssue::make(
                                'mcq_multiple_answer_option_missing',
                                "Question {$question->question_number} correct answer \"{$key}\" is not a valid option key.",
                                'reading_question',
                                $question->id,
                                'Select answers from existing option keys.',
                                $this->groupBuilderLink($group),
                            );
                        }
                    }
                } else {
                    $answerKey = (string) ($answer?->answer ?? '');
                    if ($answerKey !== '' && ! isset($keys[$answerKey])) {
                        $errors[] = ReadingValidationIssue::make(
                            'mcq_answer_option_missing',
                            "Question {$question->question_number} correct answer \"{$answerKey}\" is not a valid option key.",
                            'reading_question',
                            $question->id,
                            'Mark the correct option or update the answer key.',
                            $this->groupBuilderLink($group),
                        );
                    }
                }
            }
        }

        return $this->result($test ?? new ReadingTest, $errors, $warnings);
    }

    /**
     * @return array{
     *     is_valid: bool,
     *     errors: list<array<string, mixed>>,
     *     warnings: list<array<string, mixed>>,
     *     summary: array<string, int>
     * }
     */
    public function validateCorrectAnswers(ReadingQuestionGroup $group): array
    {
        $group = $this->loadGroup($group);
        $errors = [];
        $warnings = [];
        $test = $group->passage?->test;

        foreach ($group->questions as $question) {
            $correct = $question->correctAnswers->first();

            if ($correct === null) {
                $errors[] = ReadingValidationIssue::make(
                    'correct_answer_missing',
                    "Question {$question->question_number} has no correct answer record.",
                    'reading_question',
                    $question->id,
                    'Save a correct answer for this question.',
                    $this->groupBuilderLink($group),
                );

                continue;
            }

            if (trim((string) $correct->answer) === '' && ! $this->hasStructuredAnswers($correct)) {
                $errors[] = ReadingValidationIssue::make(
                    'correct_answer_empty',
                    "Question {$question->question_number} has an empty correct answer.",
                    'reading_correct_answer',
                    $correct->id,
                    'Enter the primary correct answer.',
                    $this->groupBuilderLink($group),
                );
            }

            $json = $correct->answer_json;
            if ($json !== null && ! is_array($json)) {
                $errors[] = ReadingValidationIssue::make(
                    'correct_answer_json_invalid',
                    "Question {$question->question_number} has invalid answer_json.",
                    'reading_correct_answer',
                    $correct->id,
                    'Re-save the answer using the builder form.',
                    $this->groupBuilderLink($group),
                );

                continue;
            }

            if (is_array($json)) {
                if ($group->question_type?->isCompletionBuilderType() || $group->question_type?->isDiagramBuilderType() || $group->question_type?->isShortAnswerBuilderType()) {
                    $answers = CompletionAnswerPayload::answers($correct);
                    if ($answers === [] && trim((string) $correct->answer) === '') {
                        $errors[] = ReadingValidationIssue::make(
                            'completion_answers_missing',
                            "Question {$question->question_number} has no accepted answers in answer_json.",
                            'reading_correct_answer',
                            $correct->id,
                            'Add at least one accepted answer.',
                            $this->groupBuilderLink($group),
                        );
                    }

                    $wordLimit = CompletionAnswerPayload::wordLimit($correct);
                    if ($wordLimit !== null && ! $this->isValidWordLimit($wordLimit)) {
                        $errors[] = ReadingValidationIssue::make(
                            'word_limit_invalid',
                            "Question {$question->question_number} has invalid word_limit \"{$wordLimit}\".",
                            'reading_correct_answer',
                            $correct->id,
                            'Use a supported answer rule value.',
                            $this->groupBuilderLink($group),
                        );
                    }
                }

                if ($group->question_type?->allowsMultipleCorrectAnswers() && ! is_array($json)) {
                    $errors[] = ReadingValidationIssue::make(
                        'mcq_multiple_json_invalid',
                        "Question {$question->question_number} must store multiple correct answers in answer_json array.",
                        'reading_correct_answer',
                        $correct->id,
                        'Select multiple correct options and save.',
                        $this->groupBuilderLink($group),
                    );
                }

                if (isset($json['regex']) && $json['regex'] !== null && @preg_match('/'.str_replace('/', '\\/', (string) $json['regex']).'/', '') === false) {
                    $errors[] = ReadingValidationIssue::make(
                        'answer_regex_invalid',
                        "Question {$question->question_number} has invalid regex in answer_json.",
                        'reading_correct_answer',
                        $correct->id,
                        'Fix or remove the custom regex pattern.',
                        $this->groupBuilderLink($group),
                    );
                }
            }
        }

        return $this->result($test ?? new ReadingTest, $errors, $warnings);
    }

    /**
     * @return array{
     *     is_valid: bool,
     *     errors: list<array<string, mixed>>,
     *     warnings: list<array<string, mixed>>,
     *     summary: array<string, int>
     * }
     */
    public function validateNumbering(ReadingTest $test): array
    {
        $test = $this->loadTest($test);
        $errors = [];
        $warnings = [];
        $seen = [];

        foreach ($test->passages as $passage) {
            $expected = [];
            if ($passage->start_question !== null && $passage->end_question !== null) {
                for ($n = (int) $passage->start_question; $n <= (int) $passage->end_question; $n++) {
                    $expected[$n] = false;
                }
            }

            foreach ($passage->groups as $group) {
                for ($n = (int) $group->start_question; $n <= (int) $group->end_question; $n++) {
                    $expected[$n] = $expected[$n] ?? false;
                }

                $groupNumbers = $group->questions
                    ->pluck('question_number')
                    ->map(fn ($value) => (int) $value)
                    ->filter(fn (int $value) => $value > 0)
                    ->values()
                    ->all();

                $missing = [];
                for ($n = (int) $group->start_question; $n <= (int) $group->end_question; $n++) {
                    if (! in_array($n, $groupNumbers, true)) {
                        $missing[] = $n;
                    }
                }

                if ($missing !== []) {
                    $errors[] = ReadingValidationIssue::make(
                        'group_question_numbers_missing',
                        'Group "'.$group->title.'" is missing question number(s): '.implode(', ', $missing).'.',
                        'reading_question_group',
                        $group->id,
                        'Create questions for every number in the group range.',
                        $this->builderLink($test, $passage, $group),
                    );
                }
            }

            foreach ($passage->groups->flatMap(fn (ReadingQuestionGroup $group) => $group->questions) as $question) {
                $number = (int) $question->question_number;
                if ($number < 1) {
                    continue;
                }

                if (isset($seen[$number])) {
                    $errors[] = ReadingValidationIssue::make(
                        'numbering_duplicate',
                        "Duplicate question number {$number}.",
                        'reading_question',
                        $question->id,
                        'Assign unique numbers across the test.',
                        route('admin.reading-tests.builder', $test),
                    );
                } else {
                    $seen[$number] = $question->id;
                }

                if ($passage->start_question !== null && $passage->end_question !== null) {
                    if ($number < $passage->start_question || $number > $passage->end_question) {
                        $errors[] = ReadingValidationIssue::make(
                            'numbering_outside_passage',
                            "Question {$number} is outside passage \"{$passage->title}\" range.",
                            'reading_question',
                            $question->id,
                            'Move the question into the passage range.',
                            $this->builderLink($test, $passage),
                        );
                    }
                }
            }
        }

        return $this->result($test, $errors, $warnings);
    }

    /**
     * @return array{
     *     is_valid: bool,
     *     errors: list<array<string, mixed>>,
     *     warnings: list<array<string, mixed>>,
     *     summary: array<string, int>
     * }
     */
    public function validatePublishReady(ReadingTest $test): array
    {
        $test = $this->loadTest($test);
        $sections = [
            $this->validateTest($test),
            $this->validatePassages($test),
            $this->validateQuestionGroups($test),
            $this->validateQuestions($test),
            $this->validateNumbering($test),
        ];

        foreach ($test->passages->flatMap(fn (ReadingPassage $passage) => $passage->groups) as $group) {
            $sections[] = $this->validateOptions($group);
            $sections[] = $this->validateCorrectAnswers($group);
        }

        $errors = [];
        $warnings = [];

        foreach ($sections as $section) {
            $errors = array_merge($errors, $section['errors']);
            $warnings = array_merge($warnings, $section['warnings']);
        }

        return $this->result($test, $errors, $warnings);
    }

    public function assertPublishReady(ReadingTest $test): void
    {
        $result = $this->validatePublishReady($test);

        if ($result['is_valid']) {
            return;
        }

        throw ValidationException::withMessages([
            'publish' => collect($result['errors'])->pluck('message')->take(5)->implode(' '),
        ]);
    }

    public function loadTest(ReadingTest $test): ReadingTest
    {
        return $test->load([
            'passages' => fn ($query) => $query->orderBy('sort_order'),
            'passages.groups' => fn ($query) => $query->orderBy('sort_order')->with([
                'questions' => fn ($inner) => $inner->with(['options', 'correctAnswers'])->orderBy('sort_order'),
                'groupOptions',
            ]),
        ]);
    }

    public function loadGroup(ReadingQuestionGroup $group): ReadingQuestionGroup
    {
        return $group->load([
            'passage.test',
            'questions' => fn ($query) => $query->with(['options', 'correctAnswers'])->orderBy('sort_order'),
            'groupOptions',
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $errors
     * @param  list<array<string, mixed>>  $warnings
     * @return array{
     *     is_valid: bool,
     *     errors: list<array<string, mixed>>,
     *     warnings: list<array<string, mixed>>,
     *     summary: array<string, int>
     * }
     */
    private function result(ReadingTest $test, array $errors, array $warnings): array
    {
        $test = $test->relationLoaded('passages') ? $test : $this->loadTest($test);

        $questions = $test->passages
            ->flatMap(fn (ReadingPassage $passage) => $passage->groups->flatMap(fn (ReadingQuestionGroup $group) => $group->questions));

        $missingAnswers = $questions->filter(function (ReadingQuestion $question): bool {
            $answer = $question->correctAnswers->first();

            return trim((string) ($answer?->answer ?? '')) === '' && ! $this->hasStructuredAnswers($answer);
        })->count();

        $duplicateCount = collect($questions->pluck('question_number')->filter(fn ($n) => (int) $n > 0)->all())
            ->countBy()
            ->filter(fn (int $count) => $count > 1)
            ->count();

        return [
            'is_valid' => $errors === [],
            'errors' => array_values($errors),
            'warnings' => array_values($warnings),
            'summary' => [
                'passages' => $test->passages->count(),
                'groups' => $test->passages->sum(fn (ReadingPassage $passage) => $passage->groups->count()),
                'questions' => $questions->count(),
                'missing_answers' => $missingAnswers,
                'duplicates' => $duplicateCount,
            ],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $errors
     * @param  list<array<string, mixed>>  $warnings
     */
    private function validateCompletionTemplate(
        ReadingQuestionGroup $group,
        ReadingPassage $passage,
        ReadingTest $test,
        array &$errors,
        array &$warnings,
    ): void {
        if (! $group->question_type?->usesCompletionTemplate()) {
            return;
        }

        $settings = $group->settings ?? [];
        $template = trim((string) ($settings['template_html'] ?? ''));

        if ($template === '') {
            $errors[] = ReadingValidationIssue::make(
                'completion_template_missing',
                "Completion group \"{$group->title}\" has no template content.",
                'reading_question_group',
                $group->id,
                'Add a template with placeholders in the completion builder.',
                $this->groupBuilderLink($group),
            );

            return;
        }

        try {
            $placeholders = $this->template->parseTemplate($template);
            $this->template->validatePlaceholders($group, $placeholders);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first() ?? 'Invalid completion template.';
            $errors[] = ReadingValidationIssue::make(
                'completion_template_invalid',
                "Group \"{$group->title}\": {$message}",
                'reading_question_group',
                $group->id,
                'Fix template placeholders in the completion builder.',
                $this->groupBuilderLink($group),
            );

            return;
        }

        $numbers = array_map(fn (array $placeholder): int => (int) $placeholder['question_number'], $placeholders);
        $questionNumbers = $group->questions->pluck('question_number')->map(fn ($n) => (int) $n)->filter(fn (int $n) => $n > 0)->all();

        if (count($numbers) !== count($questionNumbers)) {
            $warnings[] = ReadingValidationIssue::make(
                'completion_placeholder_count_mismatch',
                "Group \"{$group->title}\" placeholder count (".count($numbers).') does not match saved questions ('.count($questionNumbers).').',
                'reading_question_group',
                $group->id,
                'Re-save the template to sync questions.',
                $this->groupBuilderLink($group),
            );
        }

        $removed = $this->template->detectRemovedQuestions($group, $numbers);
        if ($removed !== []) {
            $warnings[] = ReadingValidationIssue::make(
                'completion_orphan_questions',
                'Group "'.$group->title.'" has question(s) '.implode(', ', $removed).' no longer in the template.',
                'reading_question_group',
                $group->id,
                'Re-save the template and confirm removal, or restore placeholders.',
                $this->groupBuilderLink($group),
            );
        }
    }

    /**
     * @param  list<array<string, mixed>>  $errors
     * @param  list<array<string, mixed>>  $warnings
     */
    private function validateDiagramGroup(
        ReadingQuestionGroup $group,
        ReadingPassage $passage,
        ReadingTest $test,
        array &$errors,
        array &$warnings,
    ): void {
        if (! $group->question_type?->isDiagramBuilderType()) {
            return;
        }

        $settings = $group->settings ?? [];
        $imagePath = $settings['diagram_image'] ?? null;

        if (! is_string($imagePath) || $imagePath === '') {
            $errors[] = ReadingValidationIssue::make(
                'diagram_image_missing',
                "Diagram group \"{$group->title}\" has no uploaded diagram image.",
                'reading_question_group',
                $group->id,
                'Upload a diagram image in the diagram builder.',
                route('admin.reading-question-groups.diagram-questions.index', $group),
            );
        } elseif (! Storage::disk('uploads')->exists($imagePath)) {
            $errors[] = ReadingValidationIssue::make(
                'diagram_image_missing_file',
                "Diagram image file is missing for group \"{$group->title}\".",
                'reading_question_group',
                $group->id,
                'Re-upload the diagram image.',
                route('admin.reading-question-groups.diagram-questions.index', $group),
            );
        }

        $labels = is_array($settings['labels'] ?? null) ? $settings['labels'] : [];
        if ($labels === []) {
            $errors[] = ReadingValidationIssue::make(
                'diagram_labels_missing',
                "Diagram group \"{$group->title}\" has no labels.",
                'reading_question_group',
                $group->id,
                'Place labels on the diagram and save.',
                route('admin.reading-question-groups.diagram-questions.index', $group),
            );
        }

        foreach ($labels as $index => $label) {
            $number = (int) ($label['question_number'] ?? 0);
            $x = (float) ($label['x'] ?? -1);
            $y = (float) ($label['y'] ?? -1);

            if ($x < 0 || $x > 100 || $y < 0 || $y > 100) {
                $errors[] = ReadingValidationIssue::make(
                    'diagram_label_coordinates_invalid',
                    "Diagram label {$number} has invalid coordinates (x={$x}, y={$y}).",
                    'reading_question_group',
                    $group->id,
                    'Set label positions between 0 and 100 percent.',
                    route('admin.reading-question-groups.diagram-questions.index', $group),
                );
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $errors
     */
    private function validateGroupQuestionCount(
        ReadingQuestionGroup $group,
        ReadingPassage $passage,
        ReadingTest $test,
        array &$errors,
    ): void {
        $expected = $group->expected_questions_count;
        $created = $group->questions->where('question_number', '>', 0)->count();

        if ($expected > 0 && $created !== $expected) {
            $errors[] = ReadingValidationIssue::make(
                'group_question_count_mismatch',
                "Group \"{$group->title}\" expects {$expected} questions but has {$created}.",
                'reading_question_group',
                $group->id,
                'Add or remove questions until the count matches the group range.',
                $this->builderLink($test, $passage, $group),
            );
        }
    }

    /**
     * @param  array<string, bool>  $keys
     * @param  list<array<string, mixed>>  $errors
     */
    private function validateOptionRow(
        ReadingQuestionOption $option,
        ReadingQuestionGroup $group,
        ?ReadingTest $test,
        ?ReadingPassage $passage,
        array &$errors,
        array &$keys,
        bool $labelRequired,
    ): void {
        $key = trim((string) $option->option_key);
        if ($key === '') {
            $errors[] = ReadingValidationIssue::make(
                'option_key_missing',
                'An option in group "'.$group->title.'" has an empty option key.',
                'reading_question_option',
                $option->id,
                'Provide a unique option key.',
                $this->groupBuilderLink($group),
            );

            return;
        }

        if (isset($keys[$key])) {
            $errors[] = ReadingValidationIssue::make(
                'option_key_duplicate',
                "Duplicate option key \"{$key}\" in group \"{$group->title}\".",
                'reading_question_option',
                $option->id,
                'Use unique option keys.',
                $this->groupBuilderLink($group),
            );
        } else {
            $keys[$key] = true;
        }

        if ($labelRequired && trim((string) $option->option_label) === '') {
            $errors[] = ReadingValidationIssue::make(
                'option_label_missing',
                "Option \"{$key}\" in group \"{$group->title}\" is missing a label.",
                'reading_question_option',
                $option->id,
                'Add descriptive option text.',
                $this->groupBuilderLink($group),
            );
        }
    }

    private function questionRequiresPrompt(ReadingQuestionGroup $group): bool
    {
        return $group->question_type?->isObjectiveBuilderType()
            || $group->question_type?->isMatchingBuilderType()
            || $group->question_type?->isShortAnswerBuilderType()
            || $group->question_type === OfficialReadingQuestionType::SentenceCompletion;
    }

    private function hasStructuredAnswers(?object $correct): bool
    {
        if ($correct === null) {
            return false;
        }

        $answers = CompletionAnswerPayload::answers($correct);

        return $answers !== [];
    }

    private function isValidWordLimit(string $wordLimit): bool
    {
        $normalized = strtoupper(str_replace(['-', ' '], '_', trim($wordLimit)));

        return in_array($normalized, [
            'ONE_WORD',
            'ONE_WORD_ONLY',
            'ONE_WORD_AND_OR_A_NUMBER',
            'ONE_WORD_AND_OR_NUMBER',
            'TWO_WORDS',
            'THREE_WORDS',
            'CUSTOM',
        ], true) || ReadingCompletionAnswerRule::tryFrom(strtolower($wordLimit)) !== null;
    }

    private function rangesOverlap(int $startA, int $endA, int $startB, int $endB): bool
    {
        return $startA <= $endB && $startB <= $endA;
    }

    private function builderLink(ReadingTest $test, ?ReadingPassage $passage = null, ?ReadingQuestionGroup $group = null): string
    {
        $params = ['readingTest' => $test];

        if ($passage !== null) {
            $params['passage'] = $passage->id;
        }

        if ($group !== null) {
            $params['question_group'] = $group->id;
        }

        return route('admin.reading-tests.builder', $params);
    }

    private function groupBuilderLink(ReadingQuestionGroup $group): string
    {
        $group = $group->relationLoaded('passage') ? $group : $this->loadGroup($group);
        $passage = $group->passage;
        $test = $passage?->test;

        if ($group->question_type?->isMatchingBuilderType()) {
            return route('admin.reading-question-groups.questions.index', $group);
        }

        if ($group->question_type?->isObjectiveBuilderType()) {
            return route('admin.reading-question-groups.objective-questions.index', $group);
        }

        if ($group->question_type?->isCompletionBuilderType()) {
            return route('admin.reading-question-groups.completion-questions.index', $group);
        }

        if ($group->question_type?->isDiagramBuilderType()) {
            return route('admin.reading-question-groups.diagram-questions.index', $group);
        }

        if ($group->question_type?->isShortAnswerBuilderType()) {
            return route('admin.reading-question-groups.short-answer-questions.index', $group);
        }

        return $test ? $this->builderLink($test, $passage, $group) : '#';
    }
}
