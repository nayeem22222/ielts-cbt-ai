<?php

declare(strict_types=1);

namespace App\DTOs\Listening\Student;

final readonly class ListeningPlayerPayloadData
{
    /**
     * @param  list<array<string, mixed>>  $sections
     * @param  list<array<string, mixed>>  $groups
     * @param  list<array<string, mixed>>  $questions
     * @param  list<array<string, mixed>>  $palette
     * @param  list<array<string, mixed>>  $audioSections
     * @param  array<string, mixed>  $routes
     * @param  array<string, mixed>  $config
     * @param  array<string, mixed>  $recovery
     * @param  array<string, mixed>  $officialTimer
     * @param  array<string, mixed>  $phase
     * @param  array<string, mixed>  $audioFlow
     */
    public function __construct(
        public int $attemptId,
        public string $testTitle,
        public string $testTypeLabel,
        public int $currentSectionNumber,
        public int $currentQuestionNumber,
        public array $sections,
        public array $groups,
        public array $questions,
        public array $palette,
        public array $audioSections,
        public ListeningTimerPayloadData $timer,
        public array $routes,
        public array $config,
        public array $recovery = [],
        public array $officialTimer = [],
        public array $phase = [],
        public array $audioFlow = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'attempt_id' => $this->attemptId,
            'test_title' => $this->testTitle,
            'test_type_label' => $this->testTypeLabel,
            'current_section_number' => $this->currentSectionNumber,
            'current_question_number' => $this->currentQuestionNumber,
            'sections' => $this->sections,
            'groups' => $this->groups,
            'questions' => $this->questions,
            'palette' => $this->palette,
            'audio_sections' => $this->audioSections,
            'timer' => $this->timer->toArray(),
            'routes' => $this->routes,
            'config' => $this->config,
            'recovery' => $this->recovery,
            'official_timer' => $this->officialTimer,
            'phase' => $this->phase,
            'audio_flow' => $this->audioFlow,
        ];
    }
}
