<?php

declare(strict_types=1);

namespace App\Services\Listening\Builders;

use App\Enums\Exam\ReadingCompletionAnswerRule;
use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Services\Listening\Builders\Concerns\ManagesListeningBuilderGroup;
use App\Services\Listening\ListeningQuestionGroupService;
use App\Services\Listening\ListeningQuestionService;
use App\Services\Listening\QuestionTypes\CompletionBlankParser;
use App\Support\Listening\Builder\ListeningBuilderPresenter;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ListeningCompletionQuestionBuilderService
{
    use ManagesListeningBuilderGroup;

    public function __construct(
        private readonly ListeningQuestionService $questions,
        private readonly ListeningQuestionGroupService $groups,
        private readonly ListeningBuilderPresenter $presenter,
        private readonly CompletionBlankParser $blankParser,
    ) {}

    public function assertCompletionGroup(ListeningQuestionGroup $group): void
    {
        if (! $group->question_type?->isCompletionBuilderType()) {
            throw ValidationException::withMessages([
                'question_type' => 'This question group does not use the completion question builder.',
            ]);
        }
    }

    /**
     * @return array{answer_rule: string, custom_answer_rule: ?string, template_html: string, table_data: ?array, flow_steps: ?array}
     */
    public function groupBuilderSettings(ListeningQuestionGroup $group): array
    {
        $settings = is_array($group->settings) ? $group->settings : [];

        return [
            'answer_rule' => (string) ($settings['answer_rule'] ?? ReadingCompletionAnswerRule::OneWordOnly->value),
            'custom_answer_rule' => $settings['custom_answer_rule'] ?? null,
            'template_html' => (string) ($group->content ?? $settings['template_html'] ?? ''),
            'table_data' => $settings['table_data'] ?? null,
            'flow_steps' => $settings['flow_steps'] ?? null,
        ];
    }

    /**
     * @return Collection<int, \App\Support\Listening\Builder\ListeningBuilderQuestionView>
     */
    public function presentQuestions(ListeningQuestionGroup $group): Collection
    {
        return $this->presenter->presentQuestions($group, $this->questions->listForGroup($group));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveTemplate(ListeningQuestionGroup $group, array $data): ListeningQuestionGroup
    {
        return $this->saveStructuredContent($group, $data, 'template_html');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveTable(ListeningQuestionGroup $group, array $data): ListeningQuestionGroup
    {
        $tableData = $this->normalizeStructuredPayload($data['table_data'] ?? []);
        $data['table_data'] = $tableData;
        $data['template_html'] = $this->compileTableHtml($tableData);

        return $this->saveStructuredContent($group, $data, 'table_data');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveFlowChart(ListeningQuestionGroup $group, array $data): ListeningQuestionGroup
    {
        $flowSteps = $this->normalizeStructuredPayload($data['flow_steps'] ?? []);
        $data['flow_steps'] = $flowSteps;
        $data['template_html'] = $this->compileFlowHtml($flowSteps);

        return $this->saveStructuredContent($group, $data, 'flow_steps');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveStructuredContent(
        ListeningQuestionGroup $group,
        array $data,
        string $contentKey,
        bool $settingsOnly = false,
    ): ListeningQuestionGroup {
        $this->assertCompletionGroup($group);
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);

        return DB::transaction(function () use ($group, $data, $test, $section, $contentKey, $settingsOnly): ListeningQuestionGroup {
            $settings = is_array($group->settings) ? $group->settings : [];
            $settings['answer_rule'] = (string) ($data['answer_rule'] ?? ReadingCompletionAnswerRule::OneWordOnly->value);
            $settings['custom_answer_rule'] = $data['custom_answer_rule'] ?? null;

            $content = $settingsOnly
                ? (string) ($group->content ?? '')
                : (string) ($data['template_html'] ?? $data[$contentKey] ?? '');

            if ($contentKey === 'table_data') {
                $settings['table_data'] = $data['table_data'] ?? null;
            }

            if ($contentKey === 'flow_steps') {
                $settings['flow_steps'] = $data['flow_steps'] ?? null;
            }

            if (! $settingsOnly) {
                $settings['template_html'] = $content;
                $this->syncQuestionsFromTemplate($group, $content, (bool) ($data['confirm_remove'] ?? false));
            }

            $this->groups->updateBuilderState($test, $section, $group, [
                'content' => $content,
                'settings' => $settings,
            ]);

            return $group->refresh();
        });
    }

    private function syncQuestionsFromTemplate(ListeningQuestionGroup $group, string $content, bool $confirmRemove): void
    {
        $placeholders = $this->blankParser->parsePlaceholders($content);
        $blankNumbers = array_map(
            fn (array $placeholder): int => (int) $placeholder['question_number'],
            $placeholders,
        );

        if ($blankNumbers === [] && trim(strip_tags($content)) === '') {
            throw ValidationException::withMessages([
                'template_html' => 'Template cannot be empty.',
            ]);
        }

        if ($blankNumbers === [] && trim(strip_tags($content)) !== '') {
            throw ValidationException::withMessages([
                'template_html' => 'Template must include at least one valid placeholder such as {{27}} or [blank:27].',
            ]);
        }

        $rangeErrors = $this->validatePlaceholderRange($group, $placeholders);

        if ($rangeErrors !== []) {
            throw ValidationException::withMessages(['template_html' => $rangeErrors[0]]);
        }

        $existing = $this->questions->listForGroup($group);
        $existingNumbers = $existing->pluck('question_number')->map(fn ($n) => (int) $n)->all();
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);

        foreach ($blankNumbers as $number) {
            $this->questions->syncQuestionSlot($test, $section, $group, $number, [
                'question_type' => $group->question_type?->value,
                'question_text' => "Blank {$number}",
                'correct_answer' => [],
                'answer_format' => ListeningAnswerFormat::Text->value,
                'word_limit' => (int) ($group->settings['word_limit'] ?? 2),
                'marks' => 1,
                'is_active' => true,
                'is_required' => true,
            ]);
        }

        $removed = array_diff($existingNumbers, $blankNumbers);

        if ($removed !== [] && ! $confirmRemove) {
            throw ValidationException::withMessages([
                'confirm_remove' => 'Removing placeholders will delete linked questions: Q'
                    .implode(', Q', $removed).'. Confirm to continue.',
            ]);
        }

        if ($removed !== [] && $confirmRemove) {
            foreach ($existing as $question) {
                if (in_array((int) $question->question_number, $removed, true)) {
                    $this->questions->delete($question);
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storeSentenceQuestion(ListeningQuestionGroup $group, array $data): ListeningQuestion
    {
        $this->assertCompletionGroup($group);
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);
        $questionNumber = (int) $data['question_number'];

        return $this->questions->syncQuestionSlot($test, $section, $group, $questionNumber, [
            'question_type' => $group->question_type?->value,
            'question_text' => $this->buildSentencePrompt($data),
            'correct_answer' => $this->mapTextAnswer($data['correct_answer'] ?? ''),
            'accepted_answers' => $this->mapAcceptedAnswers($data['alternative_answers'] ?? []),
            'answer_format' => ListeningAnswerFormat::Text->value,
            'word_limit' => $this->wordLimitFromRule((string) ($data['answer_rule'] ?? '')),
            'case_sensitive' => (bool) ($data['case_sensitive'] ?? false),
            'explanation' => $data['explanation'] ?? null,
            'marks' => 1,
            'is_active' => true,
            'is_required' => true,
            'meta' => ['difficulty' => $data['difficulty'] ?? 'medium'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateQuestion(ListeningQuestion $question, array $data): ListeningQuestion
    {
        $group = $question->group ?? abort(404);
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);

        if (isset($data['question_number'])) {
            $questionNumber = (int) $data['question_number'];
            $this->assertQuestionNumberIsValid($group, $questionNumber, $question);
        }

        $questionText = $question->question_text;

        if (isset($data['prompt']) || isset($data['sentence_before']) || isset($data['sentence_after']) || isset($data['sentence'])) {
            $questionText = $this->buildSentencePrompt($data, $questionText);
        }

        return $this->questions->update($test, $section, $group, $question, [
            'question_number' => (int) ($data['question_number'] ?? $question->question_number),
            'question_text' => $questionText,
            'correct_answer' => $this->mapTextAnswer($data['correct_answer'] ?? $this->firstAnswerValue($question)),
            'accepted_answers' => $this->mapAcceptedAnswers($data['alternative_answers'] ?? []),
            'answer_format' => ListeningAnswerFormat::Text->value,
            'word_limit' => $this->wordLimitFromRule((string) ($data['answer_rule'] ?? '')),
            'case_sensitive' => (bool) ($data['case_sensitive'] ?? $question->case_sensitive),
            'explanation' => $data['explanation'] ?? $question->explanation,
            'meta' => array_merge($question->meta ?? [], ['difficulty' => $data['difficulty'] ?? ($question->meta['difficulty'] ?? 'medium')]),
        ]);
    }

    public function deleteQuestion(ListeningQuestion $question): void
    {
        $this->questions->delete($question);
    }

    /**
     * @param  list<int>  $questionIds
     */
    public function reorderQuestions(ListeningQuestionGroup $group, array $questionIds): void
    {
        $this->questions->reorder($group, $questionIds);
    }

    /**
     * @param  array{import_text: string, confirm_remove?: bool}  $data
     */
    public function bulkImport(ListeningQuestionGroup $group, array $data): int
    {
        $count = 0;

        foreach (preg_split('/\R/', trim((string) ($data['import_text'] ?? ''))) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = array_map('trim', explode('|', $line));
            $this->storeSentenceQuestion($group, [
                'question_number' => (int) ($parts[0] ?? 0),
                'prompt' => (string) ($parts[1] ?? ''),
                'correct_answer' => (string) ($parts[2] ?? ''),
            ]);
            $count++;
        }

        return $count;
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
    public function liveDetectPreview(ListeningQuestionGroup $group, string $content): array
    {
        $this->assertCompletionGroup($group);

        try {
            $placeholders = $this->blankParser->parsePlaceholders($content);
            $rangeErrors = $this->validatePlaceholderRange($group, $placeholders);

            if ($rangeErrors !== []) {
                throw ValidationException::withMessages(['template' => $rangeErrors[0]]);
            }

            $valid = true;
            $error = null;
        } catch (ValidationException $exception) {
            $placeholders = $this->blankParser->parsePlaceholders($content);
            $valid = false;
            $error = collect($exception->errors())->flatten()->first() ?? 'Invalid placeholders.';
        }

        $numbers = array_map(
            fn (array $placeholder): int => (int) $placeholder['question_number'],
            $placeholders,
        );

        $existingNumbers = $this->questions->listForGroup($group)
            ->pluck('question_number')
            ->map(fn ($number) => (int) $number)
            ->all();

        return [
            'placeholders' => $placeholders,
            'numbers' => $numbers,
            'count' => count($numbers),
            'removed_candidates' => array_values(array_diff($existingNumbers, $numbers)),
            'valid' => $valid,
            'error' => $error,
        ];
    }

    /**
     * @return array{detected: list<int>, count: int}
     */
    public function detectBlanks(ListeningQuestionGroup $group, string $templateHtml): array
    {
        $preview = $this->liveDetectPreview($group, $templateHtml);

        return [
            'detected' => $preview['numbers'],
            'count' => $preview['count'],
        ];
    }

    public function previewHtml(ListeningQuestionGroup $group): string
    {
        $content = (string) ($group->content ?? '');

        return $this->blankParser->replaceBlanksForAdminPreview($content);
    }

    /**
     * @return list<array{value: string, type: string}>
     */
    private function mapTextAnswer(string $answer): array
    {
        $answer = trim($answer);

        return $answer === '' ? [] : [['value' => $answer, 'type' => 'text']];
    }

    /**
     * @param  list<string>|mixed  $answers
     * @return list<array{value: string, type: string}>
     */
    private function mapAcceptedAnswers(mixed $answers): array
    {
        if (! is_array($answers)) {
            return [];
        }

        return array_values(array_map(
            fn (string $answer): array => ['value' => trim($answer), 'type' => 'text'],
            array_filter(array_map('strval', $answers), fn (string $a): bool => trim($a) !== ''),
        ));
    }

    private function firstAnswerValue(ListeningQuestion $question): string
    {
        $answers = is_array($question->correct_answer) ? $question->correct_answer : [];

        return trim((string) ($answers[0]['value'] ?? ''));
    }

    private function wordLimitFromRule(string $rule): int
    {
        return match (ReadingCompletionAnswerRule::tryFrom(strtolower($rule))) {
            ReadingCompletionAnswerRule::OneWord, ReadingCompletionAnswerRule::OneWordOnly, ReadingCompletionAnswerRule::OneWordAndOrNumber => 1,
            ReadingCompletionAnswerRule::TwoWords => 2,
            ReadingCompletionAnswerRule::ThreeWords => 3,
            default => 2,
        };
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

        if (isset($data['sentence']) && trim((string) $data['sentence']) !== '') {
            return (string) $data['sentence'];
        }

        return (string) ($fallback ?? '');
    }

    /**
     * @param  list<array<string, mixed>>  $placeholders
     * @return list<string>
     */
    private function validatePlaceholderRange(ListeningQuestionGroup $group, array $placeholders): array
    {
        $errors = [];
        $seen = [];
        $duplicates = [];
        $outOfRange = [];

        foreach ($placeholders as $placeholder) {
            $number = (int) $placeholder['question_number'];

            if ($number < 1) {
                $errors[] = 'Placeholder question numbers must be positive integers.';

                continue;
            }

            if (isset($seen[$number])) {
                $duplicates[] = $number;
            }

            $seen[$number] = true;

            if ($number < (int) $group->start_question_number || $number > (int) $group->end_question_number) {
                $outOfRange[] = $number;
            }
        }

        if ($duplicates !== []) {
            $errors[] = 'Duplicate placeholders found for question number(s): '
                .implode(', ', array_values(array_unique($duplicates))).'.';
        }

        if ($outOfRange !== []) {
            $errors[] = 'Placeholder number(s) outside group range ('.$group->question_range_label.'): '
                .implode(', ', array_values(array_unique($outOfRange))).'.';
        }

        return $errors;
    }

    /**
     * @return array<string, mixed>|list<mixed>
     */
    private function normalizeStructuredPayload(mixed $payload): array
    {
        if (is_string($payload)) {
            $decoded = json_decode($payload, true);

            return is_array($decoded) ? $decoded : [];
        }

        return is_array($payload) ? $payload : [];
    }

    /**
     * @param  array<string, mixed>  $tableData
     */
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
}
