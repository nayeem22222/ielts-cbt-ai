<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\Enums\Listening\ListeningQuestionType;
use App\Services\Listening\QuestionTypes\CompletionBlankParser;

class ListeningGroupRendererService
{
    public function __construct(
        private readonly CompletionBlankParser $blankParser,
    ) {}

    /**
     * @param  array<string, mixed>  $group
     * @param  list<array<string, mixed>>  $questions
     */
    public function render(array $group, array $questions): string
    {
        $type = ListeningQuestionType::tryFrom((string) ($group['question_type'] ?? ''))
            ?? ListeningQuestionType::ShortAnswer;

        return match ($type) {
            ListeningQuestionType::FormCompletion,
            ListeningQuestionType::NoteCompletion,
            ListeningQuestionType::SummaryCompletion,
            ListeningQuestionType::SentenceCompletion,
            ListeningQuestionType::TableCompletion,
            ListeningQuestionType::FlowchartCompletion => $this->renderCompletion($group, $questions),
            ListeningQuestionType::MCQ => $this->renderMcq($group, $questions),
            ListeningQuestionType::MultipleAnswer => $this->renderMultipleAnswer($group, $questions),
            ListeningQuestionType::Matching => $this->renderMatching($group, $questions),
            ListeningQuestionType::MapLabelling,
            ListeningQuestionType::PlanLabelling,
            ListeningQuestionType::DiagramLabelling => $this->renderLabelling($group, $questions),
            ListeningQuestionType::ShortAnswer => $this->renderShortAnswer($group, $questions),
        };
    }

    /**
     * @param  array<string, mixed>  $group
     * @param  list<array<string, mixed>>  $questions
     */
    private function renderCompletion(array $group, array $questions): string
    {
        $content = (string) ($group['content'] ?? '');

        if ($content === '') {
            return '';
        }

        $questionsByNumber = $this->questionsByNumber($questions);

        return '<div class="listening-completion-card"><div class="listening-completion-template">'
            .$this->blankParser->renderStudentInteractive($content, $questionsByNumber)
            .'</div></div>';
    }

    /**
     * @param  array<string, mixed>  $group
     * @param  list<array<string, mixed>>  $questions
     */
    private function renderMcq(array $group, array $questions): string
    {
        $options = $this->resolveOptionList($group['options'] ?? null);
        $html = '<div class="listening-mcq-group">';

        foreach ($questions as $question) {
            $number = (int) ($question['question_number'] ?? 0);
            $questionId = (int) ($question['id'] ?? 0);
            $saved = $this->savedLetterValue($question);

            $html .= '<div class="listening-question-card listening-mcq-item" data-question-number="'.$number.'" data-question-id="'.$questionId.'">';

            if (trim((string) ($question['question_text'] ?? '')) !== '') {
                $html .= '<p class="listening-mcq-stem"><span class="listening-question-prefix">'.$number.'.</span> '.e((string) $question['question_text']).'</p>';
            }

            $html .= '<div class="listening-mcq-options">';

            foreach ($options as $option) {
                $key = (string) ($option['key'] ?? '');
                $checked = $saved !== '' && strtoupper($saved) === strtoupper($key) ? ' checked' : '';

                $html .= '<label class="listening-mcq-option">';
                $html .= '<input type="radio" name="listening_q_'.$number.'" value="'.e($key).'" class="listening-answer-input listening-mcq-radio" data-question-id="'.$questionId.'" data-question-number="'.$number.'" data-answer-type="letter"'.$checked.'>';
                $html .= '<span class="listening-mcq-option-label"><span class="listening-mcq-option-key">'.e($key).'.</span> '.e((string) ($option['text'] ?? '')).'</span>';
                $html .= '</label>';
            }

            $html .= '</div></div>';
        }

        return $html.'</div>';
    }

    /**
     * @param  array<string, mixed>  $group
     * @param  list<array<string, mixed>>  $questions
     */
    private function renderMultipleAnswer(array $group, array $questions): string
    {
        $options = $this->resolveOptionList($group['options'] ?? null);
        $settings = is_array($group['settings'] ?? null) ? $group['settings'] : [];
        $requiredAnswers = max(1, (int) ($settings['required_answers'] ?? 2));
        $rangeLabel = $this->formatGroupQuestionRangeLabel($group);
        $html = '<div class="listening-multiple-answer-group">';

        foreach ($questions as $question) {
            $number = (int) ($question['question_number'] ?? 0);
            $questionId = (int) ($question['id'] ?? 0);
            $saved = $this->savedLetterValues($question);

            $html .= '<div class="listening-question-card listening-mcq-item" data-question-number="'.$number.'" data-question-id="'.$questionId.'">';

            if (trim((string) ($question['question_text'] ?? '')) !== '') {
                $html .= '<p class="listening-mcq-stem"><span class="listening-question-prefix">'.e($rangeLabel).'.</span> '.e((string) $question['question_text']).'</p>';
            }

            $html .= '<div class="listening-mcq-options" data-required-answers="'.$requiredAnswers.'">';

            foreach ($options as $option) {
                $key = (string) ($option['key'] ?? '');
                $checked = in_array(strtoupper($key), array_map('strtoupper', $saved), true) ? ' checked' : '';

                $html .= '<label class="listening-multiple-answer-option">';
                $html .= '<input type="checkbox" name="listening_q_'.$number.'[]" value="'.e($key).'" class="listening-answer-input listening-multiple-answer-checkbox" data-question-id="'.$questionId.'" data-question-number="'.$number.'" data-answer-type="letter"'.$checked.'>';
                $html .= '<span class="listening-multiple-answer-option-label"><span class="listening-multiple-answer-option-key">'.e($key).'.</span> '.e((string) ($option['text'] ?? '')).'</span>';
                $html .= '</label>';
            }

            $html .= '</div></div>';
        }

        return $html.'</div>';
    }

    /**
     * @param  array<string, mixed>  $group
     * @param  list<array<string, mixed>>  $questions
     */
    private function renderMatching(array $group, array $questions): string
    {
        $options = is_array($group['options'] ?? null) ? $group['options'] : [];
        $choices = is_array($options['choices'] ?? null) ? $options['choices'] : [];
        $items = is_array($options['items'] ?? null) ? $options['items'] : [];

        if ($items === []) {
            $items = array_map(
                fn (array $question): array => [
                    'key' => (string) ($question['question_number'] ?? ''),
                    'text' => (string) ($question['question_text'] ?? ''),
                ],
                $questions,
            );
        }

        $questionsByKey = [];
        foreach ($questions as $question) {
            $questionsByKey[(string) ($question['question_number'] ?? '')] = $question;
        }

        $html = '<div class="listening-matching-group">';

        if ($choices !== []) {
            $html .= '<div class="listening-matching-options-box" role="list" aria-label="Options">';

            foreach ($choices as $choice) {
                $html .= '<div class="listening-matching-option-chip" role="listitem">';
                $html .= '<span class="listening-option-letter">'.e((string) ($choice['key'] ?? '')).'</span>';
                $html .= '<span class="listening-matching-option-text">'.e((string) ($choice['text'] ?? '')).'</span>';
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        $html .= '<div class="listening-matching-table-wrap overflow-x-auto">';
        $html .= '<table class="listening-matching-table">';
        $html .= '<thead><tr>';
        $html .= '<th class="listening-matching-col-num">#</th>';
        $html .= '<th class="listening-matching-col-text">Statement</th>';

        foreach ($choices as $choice) {
            $html .= '<th class="listening-matching-col-option">'.e((string) ($choice['key'] ?? '')).'</th>';
        }

        $html .= '<th class="listening-matching-col-action">Report</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($items as $item) {
            $key = (string) ($item['key'] ?? '');
            $question = $questionsByKey[$key] ?? $this->questionByNumber($questions, (int) $key);
            $questionId = (int) ($question['id'] ?? 0);
            $number = (int) ($question['question_number'] ?? (int) $key);
            $saved = $question !== null ? $this->savedLetterValue($question) : '';
            $isFlagged = ($question['is_flagged'] ?? false) === true;

            $html .= '<tr class="listening-matching-row listening-matching-question-row" data-question-number="'.$number.'" data-question-id="'.$questionId.'">';
            $html .= '<td class="listening-matching-qnum listening-matching-col-num">'.$number.'</td>';
            $html .= '<td class="listening-matching-text listening-matching-col-text">'.e((string) ($item['text'] ?? $key)).'</td>';

            foreach ($choices as $choice) {
                $choiceKey = (string) ($choice['key'] ?? '');
                $checked = $saved !== '' && strtoupper($saved) === strtoupper($choiceKey) ? ' checked' : '';

                $html .= '<td class="listening-matching-col-option">';
                $html .= '<input type="radio" name="listening_matching_q_'.$questionId.'" value="'.e($choiceKey).'" class="listening-answer-input listening-matching-radio" data-question-id="'.$questionId.'" data-question-number="'.$number.'" data-item-key="'.e($key).'" aria-label="Question '.$number.' option '.e($choiceKey).'"'.$checked.'>';
                $html .= '</td>';
            }

            $html .= '<td class="listening-matching-col-action">';
            $html .= '<button type="button" class="listening-row-flag'.($isFlagged ? ' is-flagged' : '').'" data-question-id="'.$questionId.'" data-question-number="'.$number.'" aria-pressed="'.($isFlagged ? 'true' : 'false').'">';
            $html .= '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><line x1="4" y1="22" x2="4" y2="15"/></svg>';
            $html .= '<span>Flag</span></button>';
            $html .= '</td></tr>';
        }

        $html .= '</tbody></table></div>';

        return $html.'</div>';
    }

    /**
     * @param  array<string, mixed>  $group
     * @param  list<array<string, mixed>>  $questions
     */
    private function renderLabelling(array $group, array $questions): string
    {
        $options = is_array($group['options'] ?? null) ? $group['options'] : [];
        $points = is_array($options['points'] ?? null) ? $options['points'] : [];
        $labels = is_array($options['labels'] ?? null) ? $options['labels'] : [];
        $questionsByNumber = $this->questionsByNumber($questions);

        $html = '<div class="listening-labelling-group">';

        if (! empty($group['image_url'])) {
            $html .= '<div class="listening-labelling-image-wrap">';
            $html .= '<img src="'.e((string) $group['image_url']).'" alt="Diagram" class="listening-labelling-image">';

            foreach ($points as $point) {
                $number = (int) ($point['number'] ?? 0);
                $question = $questionsByNumber[$number] ?? null;
                $questionId = (int) ($question['id'] ?? 0);
                $saved = $this->savedTextValue($question);
                $valueAttr = $saved !== '' ? ' value="'.e($saved).'"' : '';

                $html .= '<div class="listening-labelling-marker listening-inline-field" data-question-number="'.$number.'" style="left:'.(float) ($point['x'] ?? 0).'%;top:'.(float) ($point['y'] ?? 0).'%;">';
                $html .= '<span class="listening-blank-number" aria-hidden="true">'.$number.'</span>';
                $html .= '<input type="text" class="listening-answer-input listening-blank-input listening-labelling-input" data-question-id="'.$questionId.'" data-question-number="'.$number.'" maxlength="120" autocomplete="off" spellcheck="false"'.$valueAttr.'>';
                $html .= '</div>';
            }

            $html .= '</div>';
        } else {
            $html .= '<div class="listening-labelling-placeholder" role="img" aria-label="Diagram not available">';
            $html .= '<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>';
            $html .= '<span>Diagram image is not available.</span>';
            $html .= '</div>';
        }

        if ($points === [] && $questions !== []) {
            $html .= '<div class="listening-labelling-list">';

            foreach ($questions as $question) {
                $number = (int) ($question['question_number'] ?? 0);
                $questionId = (int) ($question['id'] ?? 0);
                $saved = $this->savedTextValue($question);
                $valueAttr = $saved !== '' ? ' value="'.e($saved).'"' : '';

                $html .= '<div class="listening-labelling-list-item listening-inline-field" data-question-number="'.$number.'" data-question-id="'.$questionId.'">';
                $html .= '<span class="listening-blank-number" aria-hidden="true">'.$number.'</span>';
                $html .= '<input type="text" class="listening-answer-input listening-blank-input" data-question-id="'.$questionId.'" data-question-number="'.$number.'" maxlength="120" autocomplete="off" spellcheck="false"'.$valueAttr.'>';
                $html .= '</div>';
            }

            $html .= '</div>';
        }

        if ($labels !== []) {
            $html .= '<div class="listening-labelling-choices"><p class="listening-group-subheading">Labels</p><ul class="listening-labelling-choice-list">';

            foreach ($labels as $label) {
                $html .= '<li><strong>'.e((string) ($label['key'] ?? '')).'</strong> '.e((string) ($label['text'] ?? '')).'</li>';
            }

            $html .= '</ul></div>';
        }

        return $html.'</div>';
    }

    /**
     * @param  array<string, mixed>  $group
     * @param  list<array<string, mixed>>  $questions
     */
    private function renderShortAnswer(array $group, array $questions): string
    {
        $html = '<div class="listening-short-answer-group">';

        foreach ($questions as $question) {
            $number = (int) ($question['question_number'] ?? 0);
            $questionId = (int) ($question['id'] ?? 0);
            $saved = $this->savedTextValue($question);
            $valueAttr = $saved !== '' ? ' value="'.e($saved).'"' : '';

            $html .= '<div class="listening-question-card listening-short-answer-item" data-question-number="'.$number.'" data-question-id="'.$questionId.'">';
            $html .= '<p class="listening-short-answer-stem"><span class="listening-question-prefix">'.$number.'.</span> '.e((string) ($question['question_text'] ?? '')).'</p>';
            $html .= '<div class="listening-inline-field" data-question-number="'.$number.'">';
            $html .= '<span class="listening-blank-number" aria-hidden="true">'.$number.'</span>';
            $html .= '<input type="text" class="listening-answer-input listening-blank-input" data-question-id="'.$questionId.'" data-question-number="'.$number.'" maxlength="120" autocomplete="off" spellcheck="false"'.$valueAttr.'>';
            $html .= '</div>';
            $html .= '</div>';
        }

        return $html.'</div>';
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @return array<int, array<string, mixed>>
     */
    private function questionsByNumber(array $questions): array
    {
        $indexed = [];

        foreach ($questions as $question) {
            $indexed[(int) ($question['question_number'] ?? 0)] = $question;
        }

        return $indexed;
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     * @return array<string, mixed>|null
     */
    private function questionByNumber(array $questions, int $number): ?array
    {
        foreach ($questions as $question) {
            if ((int) ($question['question_number'] ?? 0) === $number) {
                return $question;
            }
        }

        return null;
    }

    /**
     * @return list<array{key: string, text: string}>
     */
    private function resolveOptionList(mixed $options): array
    {
        if (! is_array($options)) {
            return [];
        }

        if (array_is_list($options)) {
            return array_values(array_map(fn (array $option): array => [
                'key' => (string) ($option['key'] ?? ''),
                'text' => (string) ($option['text'] ?? $option['label'] ?? ''),
            ], $options));
        }

        $choices = is_array($options['choices'] ?? null) ? $options['choices'] : [];

        return array_values(array_map(fn (array $option): array => [
            'key' => (string) ($option['key'] ?? ''),
            'text' => (string) ($option['text'] ?? $option['label'] ?? ''),
        ], $choices));
    }

    /**
     * @param  array<string, mixed>|null  $question
     */
    private function savedTextValue(?array $question): string
    {
        if ($question === null) {
            return '';
        }

        $answers = $question['student_answer'] ?? null;

        if (! is_array($answers)) {
            return '';
        }

        foreach ($answers as $answer) {
            if (! is_array($answer)) {
                continue;
            }

            $value = trim((string) ($answer['value'] ?? ''));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>|null  $question
     */
    private function savedLetterValue(?array $question): string
    {
        return $this->savedTextValue($question);
    }

    /**
     * @param  array<string, mixed>|null  $question
     * @return list<string>
     */
    private function savedLetterValues(?array $question): array
    {
        if ($question === null) {
            return [];
        }

        $answers = $question['student_answer'] ?? null;

        if (! is_array($answers)) {
            return [];
        }

        $values = [];

        foreach ($answers as $answer) {
            if (! is_array($answer)) {
                continue;
            }

            $value = trim((string) ($answer['value'] ?? ''));

            if ($value !== '') {
                $values[] = $value;
            }
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $group
     */
    private function formatGroupQuestionRangeLabel(array $group): string
    {
        $start = (int) ($group['start_question_number'] ?? 0);
        $end = (int) ($group['end_question_number'] ?? $start);

        if ($start <= 0) {
            return '';
        }

        if ($end <= $start) {
            return (string) $start;
        }

        return $start.'–'.$end;
    }
}
