<?php

declare(strict_types=1);

namespace App\Support\Listening\Builder;

use App\Enums\Listening\ListeningAnswerFormat;
use App\Enums\Listening\ListeningQuestionType;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use Illuminate\Support\Collection;

final class ListeningBuilderPresenter
{
    /**
     * @param  Collection<int, ListeningQuestion>  $questions
     * @return Collection<int, ListeningBuilderQuestionView>
     */
    public function presentQuestions(ListeningQuestionGroup $group, Collection $questions): Collection
    {
        $groupOptions = $this->presentGroupMcqOptions($group);

        return $questions->map(function (ListeningQuestion $question) use ($group, $groupOptions): ListeningBuilderQuestionView {
            $options = $this->presentQuestionOptions($question, $group, $groupOptions);

            return new ListeningBuilderQuestionView(
                id: (int) $question->id,
                question_number: (int) $question->question_number,
                prompt: (string) ($question->question_text ?? ''),
                options: $options,
                correctAnswers: $this->presentCorrectAnswers($question, $group->question_type),
                alternativeAnswers: $this->presentAlternativeAnswers($question),
                case_sensitive: (bool) $question->case_sensitive,
                explanation: $question->explanation,
                difficulty: (string) ($question->meta['difficulty'] ?? 'medium'),
                reference_type: $question->meta['reference_type'] ?? null,
                reference_phrase: $question->meta['reference_phrase'] ?? null,
                reference_sentence: $question->meta['reference_sentence'] ?? null,
                reference_paragraph: $question->meta['reference_paragraph'] ?? null,
                reference_start_offset: isset($question->meta['reference_start_offset']) ? (int) $question->meta['reference_start_offset'] : null,
                reference_end_offset: isset($question->meta['reference_end_offset']) ? (int) $question->meta['reference_end_offset'] : null,
                paragraph_reference: $question->meta['paragraph_reference'] ?? null,
            );
        });
    }

    /**
     * @return Collection<int, ListeningBuilderOptionView>
     */
    public function presentMatchingOptions(ListeningQuestionGroup $group): Collection
    {
        $choices = is_array($group->options['choices'] ?? null) ? $group->options['choices'] : [];

        return collect($choices)->values()->map(
            fn (array $choice, int $index): ListeningBuilderOptionView => new ListeningBuilderOptionView(
                id: $index,
                option_key: (string) ($choice['key'] ?? ''),
                option_label: (string) ($choice['text'] ?? ''),
                sort_order: $index + 1,
            ),
        );
    }

    /**
     * @return Collection<int, ListeningBuilderOptionView>
     */
    public function presentGroupMcqOptions(ListeningQuestionGroup $group): Collection
    {
        $options = is_array($group->options) && array_is_list($group->options) ? $group->options : [];

        return collect($options)->values()->map(
            fn (array $option, int $index): ListeningBuilderOptionView => new ListeningBuilderOptionView(
                id: $index,
                option_key: (string) ($option['key'] ?? ''),
                option_label: (string) ($option['text'] ?? ''),
                sort_order: $index + 1,
            ),
        );
    }

    /**
     * @param  Collection<int, ListeningBuilderOptionView>  $groupOptions
     * @return Collection<int, ListeningBuilderOptionView>
     */
    private function presentQuestionOptions(
        ListeningQuestion $question,
        ListeningQuestionGroup $group,
        Collection $groupOptions,
    ): Collection {
        if ($group->question_type === ListeningQuestionType::MCQ || $group->question_type === ListeningQuestionType::MultipleAnswer) {
            return $groupOptions;
        }

        if (is_array($question->options) && array_is_list($question->options)) {
            return collect($question->options)->values()->map(
                fn (array $option, int $index): ListeningBuilderOptionView => new ListeningBuilderOptionView(
                    id: $index,
                    option_key: (string) ($option['key'] ?? $option['option_key'] ?? ''),
                    option_label: (string) ($option['text'] ?? $option['option_label'] ?? ''),
                    sort_order: $index + 1,
                ),
            );
        }

        return collect();
    }

    /**
     * @return list<string>
     */
    public function presentAlternativeAnswers(ListeningQuestion $question): array
    {
        $answers = is_array($question->accepted_answers) ? $question->accepted_answers : [];

        return array_values(array_filter(array_map(function (mixed $answer): string {
            if (is_array($answer)) {
                return trim((string) ($answer['value'] ?? ''));
            }

            return trim((string) $answer);
        }, $answers), fn (string $value): bool => $value !== ''));
    }

    /**
     * @return Collection<int, ListeningBuilderCorrectAnswerView>
     */
    private function presentCorrectAnswers(ListeningQuestion $question, ?ListeningQuestionType $type): Collection
    {
        $answers = is_array($question->correct_answer) ? $question->correct_answer : [];
        $values = [];

        foreach ($answers as $answer) {
            if (is_array($answer)) {
                $values[] = (string) ($answer['value'] ?? $answer['item_key'] ?? '');
            } elseif (is_string($answer)) {
                $values[] = $answer;
            }
        }

        $values = array_values(array_filter($values, fn (string $value): bool => $value !== ''));

        if ($values === []) {
            return collect();
        }

        if ($type === ListeningQuestionType::MultipleAnswer) {
            return collect([
                new ListeningBuilderCorrectAnswerView(
                    answer: implode(',', $values),
                    answer_json: $values,
                ),
            ]);
        }

        return collect([
            new ListeningBuilderCorrectAnswerView(
                answer: $values[0],
                answer_json: count($values) > 1 ? $values : null,
            ),
        ]);
    }

    /**
     * @param  array<string, mixed>  $readingStyle
     * @return array<string, mixed>
     */
    public function mapObjectiveQuestionPayload(ListeningQuestionGroup $group, array $readingStyle): array
    {
        $type = $group->question_type;
        $correct = $readingStyle['correct_answer'] ?? null;
        $correctAnswers = $readingStyle['correct_answers'] ?? null;

        if ($type === ListeningQuestionType::MultipleAnswer && is_array($correctAnswers)) {
            $correct = array_map(
                fn (string $key): array => ['value' => strtoupper(trim($key)), 'type' => 'letter'],
                $correctAnswers,
            );
        } elseif (is_string($correct) && $correct !== '') {
            $correct = [['value' => strtoupper(trim($correct)), 'type' => 'letter']];
        } else {
            $correct = [];
        }

        $options = null;

        if (isset($readingStyle['options']) && is_array($readingStyle['options'])) {
            $options = array_values(array_map(fn (array $option): array => [
                'key' => strtoupper(trim((string) ($option['option_key'] ?? ''))),
                'text' => trim((string) ($option['option_label'] ?? '')),
            ], $readingStyle['options']));
        }

        $meta = array_filter([
            'difficulty' => $readingStyle['difficulty'] ?? 'medium',
            'reference_type' => $readingStyle['reference_type'] ?? null,
            'reference_phrase' => $readingStyle['reference_phrase'] ?? null,
            'reference_sentence' => $readingStyle['reference_sentence'] ?? null,
            'reference_paragraph' => $readingStyle['reference_paragraph'] ?? null,
            'reference_start_offset' => $readingStyle['reference_start_offset'] ?? null,
            'reference_end_offset' => $readingStyle['reference_end_offset'] ?? null,
            'paragraph_reference' => $readingStyle['reference_paragraph'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        return array_filter([
            'question_number' => (int) ($readingStyle['question_number'] ?? 0),
            'question_type' => $type?->value,
            'question_text' => (string) ($readingStyle['prompt'] ?? ''),
            'correct_answer' => $correct,
            'accepted_answers' => [],
            'answer_format' => $type === ListeningQuestionType::MultipleAnswer
                ? ListeningAnswerFormat::Multiple->value
                : ListeningAnswerFormat::Letter->value,
            'options' => $options,
            'explanation' => $readingStyle['explanation'] ?? null,
            'marks' => 1,
            'is_active' => true,
            'is_required' => true,
            'meta' => $meta !== [] ? $meta : null,
        ], fn ($value) => $value !== null);
    }

    /**
     * @param  array<string, mixed>  $readingStyle
     * @return array<string, mixed>
     */
    public function mapMatchingQuestionPayload(array $readingStyle): array
    {
        $correct = $readingStyle['correct_answer'] ?? null;

        return [
            'question_number' => (int) ($readingStyle['question_number'] ?? 0),
            'question_type' => ListeningQuestionType::Matching->value,
            'question_text' => (string) ($readingStyle['prompt'] ?? ''),
            'correct_answer' => is_string($correct) && $correct !== ''
                ? [['value' => strtoupper(trim($correct)), 'type' => 'letter']]
                : [],
            'answer_format' => ListeningAnswerFormat::Letter->value,
            'explanation' => $readingStyle['explanation'] ?? null,
            'marks' => 1,
            'is_active' => true,
            'is_required' => true,
            'meta' => array_filter([
                'difficulty' => $readingStyle['difficulty'] ?? 'medium',
                'paragraph_reference' => $readingStyle['paragraph_reference'] ?? null,
            ], fn ($value) => $value !== null && $value !== ''),
        ];
    }
}
