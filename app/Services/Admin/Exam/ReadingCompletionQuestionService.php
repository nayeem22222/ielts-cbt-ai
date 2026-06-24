<?php

declare(strict_types=1);

namespace App\Services\Admin\Exam;

use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\ReadingCompletionAnswerRule;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingTest;
use App\Support\Reading\CompletionBulkImportParser;
use App\Support\Reading\CompletionPlaceholderParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReadingCompletionQuestionService
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

    public function assertCompletionGroup(ReadingQuestionGroup $group): void
    {
        if (! $group->question_type?->isCompletionBuilderType()) {
            throw ValidationException::withMessages([
                'question_type' => 'This question group does not use the completion question builder.',
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
     * @return array{answer_rule: string, custom_answer_rule: ?string, template_html: string, table_data: ?array, flow_steps: ?array}
     */
    public function groupBuilderSettings(ReadingQuestionGroup $group): array
    {
        $settings = $group->settings ?? [];

        return [
            'answer_rule' => (string) ($settings['answer_rule'] ?? ReadingCompletionAnswerRule::OneWordOnly->value),
            'custom_answer_rule' => $settings['custom_answer_rule'] ?? null,
            'template_html' => (string) ($settings['template_html'] ?? ''),
            'table_data' => $settings['table_data'] ?? null,
            'flow_steps' => $settings['flow_steps'] ?? null,
        ];
    }

    /**
     * @param  array{
     *     answer_rule: string,
     *     custom_answer_rule?: ?string,
     *     template_html: string,
     *     table_data?: ?array,
     *     flow_steps?: ?array,
     *     confirm_remove?: bool
     * }  $data
     */
    public function saveTemplate(ReadingQuestionGroup $group, array $data): ReadingQuestionGroup
    {
        $this->assertCompletionGroup($group);
        $this->assertTemplateGroup($group);

        return DB::transaction(function () use ($group, $data): ReadingQuestionGroup {
            $templateHtml = (string) ($data['template_html'] ?? '');
            $confirmRemove = (bool) ($data['confirm_remove'] ?? false);

            $sync = $this->template->syncQuestions($group, $templateHtml);

            if ($sync['removed_candidates'] !== [] && ! $confirmRemove) {
                throw ValidationException::withMessages([
                    'confirm_remove' => 'Removing placeholders will delete linked questions: '
                        .implode(', ', $sync['removed_candidates']).'. Confirm to continue.',
                ]);
            }

            $settings = $group->settings ?? [];
            $settings['answer_rule'] = (string) $data['answer_rule'];
            $settings['custom_answer_rule'] = $data['custom_answer_rule'] ?? null;
            $settings['template_html'] = $templateHtml;

            if ($group->question_type === OfficialReadingQuestionType::TableCompletion) {
                $settings['table_data'] = $data['table_data'] ?? null;
            }

            if ($group->question_type === OfficialReadingQuestionType::FlowChartCompletion) {
                $settings['flow_steps'] = $data['flow_steps'] ?? null;
            }

            $group->forceFill(['settings' => $settings])->save();

            if ($sync['removed_candidates'] !== [] && $confirmRemove) {
                $this->template->purgeRemovedQuestions($group, $sync['removed_candidates']);
            }

            return $this->loadGroupForBuilder($group->refresh());
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveTable(ReadingQuestionGroup $group, array $data): ReadingQuestionGroup
    {
        $this->assertCompletionGroup($group);

        if ($group->question_type !== OfficialReadingQuestionType::TableCompletion) {
            throw ValidationException::withMessages([
                'question_type' => 'Table save is only available for table completion groups.',
            ]);
        }

        $tableData = $data['table_data'] ?? [];
        $data['template_html'] = $this->compileTableHtml(is_array($tableData) ? $tableData : []);

        return $this->saveTemplate($group, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveFlowChart(ReadingQuestionGroup $group, array $data): ReadingQuestionGroup
    {
        $this->assertCompletionGroup($group);

        if ($group->question_type !== OfficialReadingQuestionType::FlowChartCompletion) {
            throw ValidationException::withMessages([
                'question_type' => 'Flow chart save is only available for flow chart completion groups.',
            ]);
        }

        $flowSteps = $data['flow_steps'] ?? [];
        $data['template_html'] = $this->compileFlowHtml(is_array($flowSteps) ? $flowSteps : []);

        return $this->saveTemplate($group, $data);
    }

    /**
     * @return array{
     *     placeholders: list<array<string, mixed>>,
     *     numbers: list<int>,
     *     count: int,
     *     removed_candidates: list<int>,
     *     valid: bool,
     *     error: ?string
     * }
     */
    public function liveDetectPreview(ReadingQuestionGroup $group, string $content): array
    {
        $this->assertCompletionGroup($group);

        try {
            $placeholders = $this->template->parseTemplate($content);
            $this->template->validatePlaceholders($group, $placeholders);
            $valid = true;
            $error = null;
        } catch (ValidationException $exception) {
            $placeholders = $this->safeParsePlaceholders($content);
            $valid = false;
            $error = collect($exception->errors())->flatten()->first();
        }

        $numbers = array_map(
            fn (array $placeholder): int => (int) $placeholder['question_number'],
            $placeholders,
        );

        return [
            'placeholders' => $placeholders,
            'numbers' => $numbers,
            'count' => count($numbers),
            'removed_candidates' => $this->template->detectRemovedQuestions($group, $numbers),
            'valid' => $valid,
            'error' => $error,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function safeParsePlaceholders(string $content): array
    {
        try {
            return $this->template->parseTemplate($content);
        } catch (ValidationException) {
            return [];
        }
    }

    /**
     * @param  array{
     *     question_number: int,
     *     prompt?: string,
     *     sentence_before?: ?string,
     *     sentence_after?: ?string,
     *     correct_answer: string,
     *     alternative_answers?: list<string>|null,
     *     explanation?: ?string,
     *     difficulty?: ?string,
     *     case_sensitive?: bool,
     *     sort_order?: ?int
     * }  $data
     */
    public function storeSentenceQuestion(ReadingQuestionGroup $group, array $data): ReadingQuestion
    {
        $this->assertCompletionGroup($group);
        $this->assertSentenceGroup($group);

        return DB::transaction(function () use ($group, $data): ReadingQuestion {
            $questionNumber = (int) $data['question_number'];
            $this->assertQuestionNumberIsValid($group, $questionNumber);

            $sortOrder = (int) ($data['sort_order'] ?? ((int) $group->questions()->max('sort_order') + 1));

            /** @var ReadingQuestion $question */
            $question = $group->questions()->create([
                'question_number' => $questionNumber,
                'prompt' => $this->buildSentencePrompt($data),
                'explanation' => $data['explanation'] ?? null,
                'difficulty' => $data['difficulty'] ?? 'medium',
                'sort_order' => max(1, $sortOrder),
                'marks' => 1,
                'metadata' => ['auto_generated' => false],
            ]);

            $this->syncCorrectAnswers($question, $data);

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
            $this->assertCompletionGroup($group);

            if ($group->question_type === OfficialReadingQuestionType::SentenceCompletion) {
                if (isset($data['question_number'])) {
                    $questionNumber = (int) $data['question_number'];
                    $this->assertQuestionNumberIsValid($group, $questionNumber, $question);
                    $question->question_number = $questionNumber;
                }

                if (isset($data['prompt']) || isset($data['sentence_before']) || isset($data['sentence_after'])) {
                    $question->prompt = $this->buildSentencePrompt($data, $question->prompt);
                }
            }

            if (array_key_exists('explanation', $data)) {
                $question->explanation = $data['explanation'];
            }

            if (isset($data['difficulty'])) {
                $question->difficulty = (string) $data['difficulty'];
            }

            $question->save();

            if (
                array_key_exists('correct_answer', $data)
                || array_key_exists('alternative_answers', $data)
                || array_key_exists('case_sensitive', $data)
            ) {
                $this->assertPublishedAnswerPresent($group, $data);
                $this->syncCorrectAnswers($question, $data);
            }

            return $question->load(['correctAnswers']);
        });
    }

    public function deleteQuestion(ReadingQuestion $question): void
    {
        DB::transaction(function () use ($question): void {
            $group = $question->group()->firstOrFail();
            $this->assertCompletionGroup($group);
            $question->delete();
        });
    }

    /**
     * @param  array{import_text?: ?string, confirm_remove?: bool}  $data
     */
    public function bulkImport(ReadingQuestionGroup $group, array $data): int
    {
        $this->assertCompletionGroup($group);

        $text = trim((string) ($data['import_text'] ?? ''));

        if ($text === '') {
            throw ValidationException::withMessages([
                'import_text' => 'Import text is required.',
            ]);
        }

        if ($group->question_type === OfficialReadingQuestionType::SentenceCompletion) {
            return DB::transaction(function () use ($group, $text): int {
                $created = 0;

                foreach (CompletionBulkImportParser::parseSentences($text) as $row) {
                    if ($group->questions()->where('question_number', $row['question_number'])->exists()) {
                        continue;
                    }

                    $this->storeSentenceQuestion($group, $row);
                    $created++;
                }

                return $created;
            });
        }

        $templateHtml = CompletionBulkImportParser::templateFromImport($text, $group->question_type);
        $settings = $this->groupBuilderSettings($group);

        $this->saveTemplate($group, [
            'answer_rule' => $settings['answer_rule'],
            'custom_answer_rule' => $settings['custom_answer_rule'],
            'template_html' => $templateHtml,
            'table_data' => $settings['table_data'],
            'flow_steps' => $settings['flow_steps'],
            'confirm_remove' => (bool) ($data['confirm_remove'] ?? false),
        ]);

        return count(CompletionPlaceholderParser::extractNumbers($templateHtml));
    }

    /**
     * @param  list<int>  $questionIds
     */
    public function reorderQuestions(ReadingQuestionGroup $group, array $questionIds): void
    {
        $this->assertCompletionGroup($group);

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
     * @return list<int>
     */
    public function detectPlaceholders(string $content): array
    {
        return CompletionPlaceholderParser::extractNumbers($content);
    }

    public function templateEngine(): ReadingCompletionTemplateService
    {
        return $this->template;
    }

    public function compileTableHtml(array $tableData): string
    {
        $rows = $tableData['rows'] ?? [];

        if ($rows === []) {
            return '';
        }

        $html = '<table class="completion-table w-full border-collapse"><tbody>';

        foreach ($rows as $rowIndex => $row) {
            $cells = $row['cells'] ?? [];
            $tag = ($row['is_header'] ?? false) || $rowIndex === 0 ? 'th' : 'td';
            $html .= '<tr>';

            foreach ($cells as $cell) {
                $content = (string) ($cell['content'] ?? '');
                $isBlank = (bool) ($cell['is_blank'] ?? false);
                $blankNumber = (int) ($cell['blank_number'] ?? 0);
                $colspan = (int) ($cell['colspan'] ?? 1);
                $rowspan = (int) ($cell['rowspan'] ?? 1);
                $attrs = '';

                if ($colspan > 1) {
                    $attrs .= ' colspan="'.$colspan.'"';
                }

                if ($rowspan > 1) {
                    $attrs .= ' rowspan="'.$rowspan.'"';
                }

                if ($isBlank && $blankNumber > 0) {
                    $html .= '<'.$tag.' class="border border-neutral-300 px-3 py-2"'.$attrs.'>{{'.$blankNumber.'}}</'.$tag.'>';
                } else {
                    $html .= '<'.$tag.' class="border border-neutral-300 px-3 py-2"'.$attrs.'>'.e($content).'</'.$tag.'>';
                }
            }

            $html .= '</tr>';
        }

        return $html.'</tbody></table>';
    }

    /**
     * @param  list<array{text?: string, is_blank?: bool, blank_number?: int}>  $steps
     */
    public function compileFlowHtml(array $steps): string
    {
        if ($steps === []) {
            return '';
        }

        $html = '<div class="completion-flow space-y-2">';

        foreach ($steps as $index => $step) {
            if ($index > 0) {
                $html .= '<div class="text-center text-lg font-bold text-neutral-500">↓</div>';
            }

            $text = (string) ($step['text'] ?? '');
            $isBlank = (bool) ($step['is_blank'] ?? false);
            $blankNumber = (int) ($step['blank_number'] ?? 0);

            if ($isBlank && $blankNumber > 0) {
                $html .= '<div class="rounded-xl border border-neutral-300 bg-white px-4 py-3 text-center">{{'.$blankNumber.'}}</div>';
            } else {
                $html .= '<div class="rounded-xl border border-neutral-300 bg-white px-4 py-3 text-center">'.e($text).'</div>';
            }
        }

        return $html.'</div>';
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildSentencePrompt(array $data, ?string $fallback = null): string
    {
        if (isset($data['sentence_before']) || isset($data['sentence_after'])) {
            $before = trim((string) ($data['sentence_before'] ?? ''));
            $after = trim((string) ($data['sentence_after'] ?? ''));

            return trim($before.' _________ '.$after);
        }

        if (isset($data['prompt']) && trim((string) $data['prompt']) !== '') {
            return (string) $data['prompt'];
        }

        return (string) ($fallback ?? '');
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
                'correct_answer' => 'Correct answer is required for published completion groups.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncCorrectAnswers(ReadingQuestion $question, array $data): void
    {
        $wordLimit = (string) ($data['word_limit'] ?? ReadingCompletionAnswerRule::OneWordOnly->value);

        $this->template->syncCorrectAnswers($question, [
            'correct_answer' => $data['correct_answer'] ?? null,
            'alternative_answers' => $data['alternative_answers'] ?? [],
            'case_sensitive' => (bool) ($data['case_sensitive'] ?? false),
            'word_limit' => $wordLimit,
            'regex' => $data['regex'] ?? null,
        ]);
    }

    private function assertTemplateGroup(ReadingQuestionGroup $group): void
    {
        if (! $group->question_type?->usesCompletionTemplate()) {
            throw ValidationException::withMessages([
                'question_type' => 'This completion type does not use a content template.',
            ]);
        }
    }

    private function assertSentenceGroup(ReadingQuestionGroup $group): void
    {
        if ($group->question_type !== OfficialReadingQuestionType::SentenceCompletion) {
            throw ValidationException::withMessages([
                'question_type' => 'Manual question entry is only available for sentence completion groups.',
            ]);
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
}
