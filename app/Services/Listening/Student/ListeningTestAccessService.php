<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\Actions\Listening\PublishListeningTestAction;
use App\Enums\Commerce\IeltsModule;
use App\Enums\Listening\ListeningTestStatus;
use App\Models\Listening\ListeningTest;
use App\Models\User;
use App\Services\Enrollment\PackageAccessService;

class ListeningTestAccessService
{
    public function __construct(
        private readonly PublishListeningTestAction $publishValidator,
        private readonly PackageAccessService $packageAccess,
        private readonly ListeningAudioAccessService $audioAccess,
    ) {}

    public function isPlayable(ListeningTest $test): bool
    {
        return $this->isStartable($test);
    }

    public function isStartable(ListeningTest $test): bool
    {
        return $this->startBlockingReasons($test) === [];
    }

    public function isListable(ListeningTest $test): bool
    {
        return $this->listingBlockingReasons($test) === [];
    }

    public function isOfficialReady(ListeningTest $test): bool
    {
        return $this->publishValidator->validate($test)['success'];
    }

    /**
     * @return list<string>
     */
    public function listingBlockingReasons(ListeningTest $test): array
    {
        $reasons = [];

        if ($test->trashed()) {
            $reasons[] = 'This listening test has been removed.';
        }

        if ($test->status !== ListeningTestStatus::Published) {
            $reasons[] = 'This listening test is not published.';
        }

        if (! $test->is_active) {
            $reasons[] = 'This listening test is not active.';
        }

        return array_values(array_unique($reasons));
    }

    /**
     * @return list<string>
     */
    public function startBlockingReasons(ListeningTest $test): array
    {
        $reasons = $this->listingBlockingReasons($test);

        $test->loadMissing([
            'sections' => fn ($query) => $query->where('is_active', true),
            'questions' => fn ($query) => $query->where('is_active', true),
        ]);

        $minimumSections = (int) config('listening.student_access.minimum_active_sections', 1);
        $minimumQuestions = (int) config('listening.student_access.minimum_active_questions', 1);

        if ($test->sections->count() < $minimumSections) {
            $reasons[] = 'This listening test has no active sections.';
        }

        if ($test->questions->count() < $minimumQuestions) {
            $reasons[] = 'This listening test has no active questions.';
        }

        return array_values(array_unique($reasons));
    }

    /**
     * @return list<string>
     */
    public function unavailabilityReasons(ListeningTest $test): array
    {
        return $this->startBlockingReasons($test);
    }

    /**
     * Non-blocking warnings shown before start and in the player.
     *
     * @return list<string>
     */
    public function readinessWarnings(ListeningTest $test): array
    {
        $warnings = [];

        $test->loadMissing([
            'sections' => fn ($query) => $query->where('is_active', true)->with('audio'),
        ]);

        foreach ($test->sections as $section) {
            if ($section->audio_id === null || $section->audio === null) {
                $warnings[] = "Section {$section->section_number} is missing audio.";

                continue;
            }

            if (! $this->audioAccess->sectionHasPlayableAudio($section)) {
                $warnings[] = "Section {$section->section_number} audio is not ready.";
            }
        }

        if (! $this->isOfficialReady($test)) {
            $warnings[] = 'This test is not fully configured yet. Some sections or questions may be incomplete.';
        }

        return array_values(array_unique($warnings));
    }

    /**
     * @return list<string>
     */
    public function debugVisibilityReasons(ListeningTest $test, ?User $user = null): array
    {
        if (! config('listening.student_access.show_debug_unavailability', false)) {
            return [];
        }

        $reasons = [];

        if ($test->status !== ListeningTestStatus::Published) {
            $reasons[] = 'unpublished';
        }

        if (! $test->is_active) {
            $reasons[] = 'inactive';
        }

        if ($test->trashed()) {
            $reasons[] = 'soft_deleted';
        }

        if ($user !== null && ! $this->packageAccess->canAccessModule($user, IeltsModule::Listening)) {
            $reasons[] = 'no_enrollment';
        }

        $test->loadMissing([
            'sections' => fn ($query) => $query->where('is_active', true)->with('audio'),
            'questions' => fn ($query) => $query->where('is_active', true),
        ]);

        if ($test->sections->isEmpty()) {
            $reasons[] = 'no_sections';
        }

        if ($test->questions->isEmpty()) {
            $reasons[] = 'no_questions';
        }

        $hasAudioGap = $test->sections->contains(
            fn ($section): bool => $section->audio_id === null
                || $section->audio === null
                || ! $this->audioAccess->sectionHasPlayableAudio($section),
        );

        if ($hasAudioGap) {
            $reasons[] = 'audio_not_ready';
        }

        if (! $this->isOfficialReady($test)) {
            $reasons[] = 'not_officially_ready';
        }

        return array_values(array_unique($reasons));
    }
}
