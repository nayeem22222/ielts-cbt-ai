<?php

declare(strict_types=1);

namespace App\Support\Listening\Builder;

use Illuminate\Support\Collection;

final class ListeningBuilderQuestionView
{
    /**
     * @param  Collection<int, ListeningBuilderOptionView>  $options
     * @param  Collection<int, ListeningBuilderCorrectAnswerView>  $correctAnswers
     * @param  list<string>  $alternativeAnswers
     */
    public function __construct(
        public int $id,
        public int $question_number,
        public string $prompt,
        public Collection $options,
        public Collection $correctAnswers,
        public array $alternativeAnswers = [],
        public bool $case_sensitive = false,
        public ?string $explanation = null,
        public string $difficulty = 'medium',
        public ?string $reference_type = null,
        public ?string $reference_phrase = null,
        public ?string $reference_sentence = null,
        public ?string $reference_paragraph = null,
        public ?int $reference_start_offset = null,
        public ?int $reference_end_offset = null,
        public ?string $paragraph_reference = null,
    ) {}
}
