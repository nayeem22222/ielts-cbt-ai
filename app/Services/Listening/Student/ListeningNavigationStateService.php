<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\DTOs\Listening\Student\NavigationStateData;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningQuestion;
use App\Repositories\Listening\Student\ListeningAttemptRepository;
use Illuminate\Validation\ValidationException;

class ListeningNavigationStateService
{
    public function __construct(
        private readonly ListeningAttemptRepository $attempts,
        private readonly ListeningQuestionPaletteService $palette,
        private readonly ListeningNavigationService $navigation,
    ) {}

    public function updateCurrentPosition(
        ListeningAttempt $attempt,
        int $sectionNumber,
        int $questionNumber,
    ): NavigationStateData {
        if (! $this->validatePosition($attempt, $sectionNumber, $questionNumber)) {
            throw ValidationException::withMessages([
                'current_question_number' => 'Invalid question position for this attempt.',
            ]);
        }

        $this->attempts->update($attempt, [
            'current_section_number' => $sectionNumber,
            'current_question_number' => $questionNumber,
        ]);

        $this->savePlayerState($attempt, [
            'current_section_number' => $sectionNumber,
            'current_question_number' => $questionNumber,
            'updated_at' => now()->toIso8601String(),
        ]);

        return $this->buildNavigationState($attempt->refresh());
    }

    public function validatePosition(ListeningAttempt $attempt, int $sectionNumber, int $questionNumber): bool
    {
        if ($questionNumber < 1 || $questionNumber > (int) $attempt->total_questions) {
            return false;
        }

        if ($sectionNumber < 1 || $sectionNumber > 4) {
            return false;
        }

        $questionExists = ListeningQuestion::query()
            ->where('listening_test_id', $attempt->listening_test_id)
            ->where('question_number', $questionNumber)
            ->where('is_active', true)
            ->exists();

        if (! $questionExists) {
            return false;
        }

        if (config('listening.navigation.validate_section_question_match', true)) {
            $expectedSection = $this->getSectionByQuestionNumber($attempt, $questionNumber);

            if ($expectedSection !== $sectionNumber) {
                return false;
            }
        }

        return true;
    }

    public function getNextQuestion(ListeningAttempt $attempt): ?int
    {
        $current = (int) $attempt->current_question_number;

        return $current < (int) $attempt->total_questions ? $current + 1 : null;
    }

    public function getPreviousQuestion(ListeningAttempt $attempt): ?int
    {
        $current = (int) $attempt->current_question_number;

        return $current > 1 ? $current - 1 : null;
    }

    public function getSectionByQuestionNumber(ListeningAttempt $attempt, int $questionNumber): int
    {
        $section = $attempt->test?->sections()
            ->where('is_active', true)
            ->where('start_question_number', '<=', $questionNumber)
            ->where('end_question_number', '>=', $questionNumber)
            ->first();

        if ($section !== null) {
            return (int) $section->section_number;
        }

        return $this->navigation->sectionForQuestionNumber($questionNumber);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function savePlayerState(ListeningAttempt $attempt, array $state): void
    {
        $resultMeta = is_array($attempt->result_meta) ? $attempt->result_meta : [];
        $player = is_array($resultMeta['player'] ?? null) ? $resultMeta['player'] : [];
        $navigation = is_array($player['navigation'] ?? null) ? $player['navigation'] : [];

        $player['navigation'] = array_merge($navigation, $state);
        $resultMeta['player'] = $player;

        $this->attempts->update($attempt, ['result_meta' => $resultMeta]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getPlayerState(ListeningAttempt $attempt): array
    {
        $resultMeta = is_array($attempt->result_meta) ? $attempt->result_meta : [];
        $player = is_array($resultMeta['player'] ?? null) ? $resultMeta['player'] : [];

        return is_array($player['navigation'] ?? null) ? $player['navigation'] : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getNavigationPayload(ListeningAttempt $attempt): array
    {
        return $this->buildNavigationState($attempt)->toArray();
    }

    private function buildNavigationState(ListeningAttempt $attempt): NavigationStateData
    {
        $currentQuestion = (int) ($attempt->current_question_number ?: 1);
        $currentSection = (int) ($attempt->current_section_number ?: $this->getSectionByQuestionNumber($attempt, $currentQuestion));

        return new NavigationStateData(
            currentSectionNumber: $currentSection,
            currentQuestionNumber: $currentQuestion,
            nextQuestionNumber: $this->getNextQuestion($attempt),
            previousQuestionNumber: $this->getPreviousQuestion($attempt),
            palette: $this->palette->build($attempt),
        );
    }
}
