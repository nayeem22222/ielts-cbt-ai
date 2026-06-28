<?php

declare(strict_types=1);

namespace App\Actions\Listening;

use App\Actions\Listening\QuestionTypes\ValidateQuestionTypePayloadAction;
use App\Enums\Listening\ListeningAudioProcessingStatus;
use App\Enums\Listening\ListeningAudioValidationStatus;
use App\Enums\Listening\ListeningConstants;
use App\Enums\Listening\ListeningTestStatus;
use App\Models\Listening\ListeningQuestionGroup;
use App\Models\Listening\ListeningTest;
use Illuminate\Support\Facades\DB;

class PublishListeningTestAction
{
    public function __construct(
        private readonly ValidateQuestionTypePayloadAction $validateQuestionType,
    ) {}

    /**
     * @return array{success: bool, errors: list<string>}
     */
    public function validate(ListeningTest $test): array
    {
        $test->loadMissing([
            'setting',
            'sections' => fn ($query) => $query->where('is_active', true),
            'sections.audio',
            'questions' => fn ($query) => $query->where('is_active', true),
            'questionGroups' => fn ($query) => $query->where('is_active', true),
        ]);

        $errors = [];

        if ($test->status === ListeningTestStatus::Archived) {
            $errors[] = 'Archived listening tests cannot be published.';
        }

        $activeSections = $test->sections;

        if ($activeSections->count() !== ListeningConstants::TOTAL_SECTIONS) {
            $errors[] = 'Listening test must have exactly '.ListeningConstants::TOTAL_SECTIONS.' active sections.';
        }

        $activeQuestions = $test->questions;

        if ($activeQuestions->count() !== ListeningConstants::TOTAL_QUESTIONS) {
            $errors[] = 'Listening test must have exactly '.ListeningConstants::TOTAL_QUESTIONS.' active questions.';
        }

        foreach ($activeSections as $section) {
            if ($section->audio_id === null) {
                $errors[] = "Section {$section->section_number} is missing audio.";
            } else {
                $audio = $section->audio;

                if ($audio === null) {
                    $errors[] = "Section {$section->section_number} references missing audio.";
                } else {
                    // Check processing completion
                    if (config('listening.publishing.require_processed_audio', true)
                        && $audio->processing_status !== ListeningAudioProcessingStatus::Completed) {
                        $errors[] = "Section {$section->section_number} audio is not processed.";
                    }

                    // Check validation status
                    if (config('listening.publishing.require_valid_audio', true)
                        && $audio->validation_status !== ListeningAudioValidationStatus::Valid) {
                        $errors[] = "Section {$section->section_number} audio has failed validation.";
                    }

                    // Check playable path (meta.audio.playable_path)
                    $playablePath = null;
                    if (is_array($audio->meta)) {
                        $audioMeta = is_array($audio->meta['audio'] ?? null) ? $audio->meta['audio'] : [];
                        $playablePath = is_string($audioMeta['playable_path'] ?? null) ? $audioMeta['playable_path'] : null;
                    }

                    if ($audio->processing_status === ListeningAudioProcessingStatus::Completed && $playablePath === null) {
                        $errors[] = "Section {$section->section_number} audio playable file is missing.";
                    }

                    // Verify playable file exists on disk
                    if ($playablePath !== null) {
                        $disk = \Illuminate\Support\Facades\Storage::disk((string) config('listening.audio.disk', 'public'));
                        if (! $disk->exists($playablePath)) {
                            $errors[] = "Section {$section->section_number} audio playable file is missing from storage.";
                        }
                    }

                    // Check duration
                    if ($audio->duration_seconds === null || (int) $audio->duration_seconds <= 0) {
                        $errors[] = "Section {$section->section_number} audio duration is missing.";
                    }

                    // Check waveform
                    if (config('listening.publishing.require_waveform', false) && blank($audio->waveform_json_path)) {
                        $errors[] = "Section {$section->section_number} audio waveform is missing.";
                    }
                }
            }

            $sectionQuestionCount = $activeQuestions->where('listening_section_id', $section->id)->count();

            if ($sectionQuestionCount !== (int) $section->total_questions) {
                $errors[] = "Section {$section->section_number} must have {$section->total_questions} active questions (currently {$sectionQuestionCount}).";
            }

            if (config('listening.transcript.require_for_publish', false) && $section->transcript_id === null) {
                $errors[] = "Section {$section->section_number} is missing a transcript.";
            }

            $expectedRange = ListeningConstants::SECTION_QUESTION_RANGES[$section->section_number] ?? null;

            if ($expectedRange === null) {
                $errors[] = "Section {$section->section_number} has an invalid section number.";

                continue;
            }

            if (
                (int) $section->start_question_number !== $expectedRange['start']
                || (int) $section->end_question_number !== $expectedRange['end']
            ) {
                $errors[] = "Section {$section->section_number} must cover questions {$expectedRange['start']}–{$expectedRange['end']}.";
            }
        }

        $questionNumbers = $activeQuestions->pluck('question_number')->map(fn ($number) => (int) $number);

        if ($questionNumbers->duplicates()->isNotEmpty()) {
            $errors[] = 'Duplicate question numbers exist in this listening test.';
        }

        for ($number = ListeningConstants::MIN_QUESTION_NUMBER; $number <= ListeningConstants::MAX_QUESTION_NUMBER; $number++) {
            if (! $questionNumbers->contains($number)) {
                $errors[] = "Question number {$number} is missing.";
            }
        }

        foreach ($activeQuestions as $question) {
            if ($question->correct_answer === null || $question->correct_answer === [] || $question->correct_answer === '') {
                $errors[] = "Question {$question->question_number} is missing a correct answer.";
            }
        }

        $groups = $test->questionGroups->sortBy('start_question_number')->values();

        /** @var ListeningQuestionGroup|null $previous */
        $previous = null;

        foreach ($groups as $group) {
            if ((int) $group->start_question_number > (int) $group->end_question_number) {
                $errors[] = "Question group \"{$group->title}\" has an invalid question range.";
            }

            $section = $activeSections->firstWhere('id', $group->listening_section_id);

            if ($section !== null) {
                if (
                    (int) $group->start_question_number < (int) $section->start_question_number
                    || (int) $group->end_question_number > (int) $section->end_question_number
                ) {
                    $errors[] = "Question group \"{$group->title}\" is outside section {$section->section_number} range.";
                }
            }

            if ($previous !== null && (int) $group->start_question_number <= (int) $previous->end_question_number) {
                $errors[] = 'Overlapping question group ranges detected.';
            }

            $previous = $group;
        }

        foreach ($test->questionGroups as $group) {
            $section = $activeSections->firstWhere('id', $group->listening_section_id);
            $sectionLabel = $section ? "Section {$section->section_number}" : 'Section';
            $type = $group->question_type;

            if ($type === null) {
                $errors[] = "{$sectionLabel}: Question group \"{$group->title}\" has no question type.";

                continue;
            }

            $groupQuestions = $activeQuestions->where('listening_question_group_id', $group->id);
            $groupErrors = $this->validateQuestionType->execute(
                'group',
                [
                    'content' => $group->content,
                    'options' => $group->options,
                    'settings' => $group->settings,
                    'image_path' => $group->image_path,
                    'question_type' => $type->value,
                ],
                $type,
                $group,
                null,
                $groupQuestions,
            );

            foreach ($groupErrors as $error) {
                $errors[] = "{$sectionLabel}, Group \"{$group->title}\": {$error}";
            }

            foreach ($groupQuestions as $question) {
                $questionErrors = $this->validateQuestionType->execute(
                    'question',
                    [
                        'question_text' => $question->question_text,
                        'word_limit' => $question->word_limit,
                        'correct_answer' => $question->correct_answer,
                        'options' => $question->options,
                        'answer_format' => $question->answer_format?->value,
                    ],
                    $type,
                    $group,
                    $question,
                    $groupQuestions,
                );

                foreach ($questionErrors as $error) {
                    $errors[] = "{$sectionLabel}, Q{$question->question_number}: {$error}";
                }
            }
        }

        if ($test->setting === null) {
            $errors[] = 'Listening test settings are missing.';
        }

        return [
            'success' => $errors === [],
            'errors' => $errors,
        ];
    }

    /**
     * @return array{success: bool, errors: list<string>, test?: ListeningTest}
     */
    public function execute(ListeningTest $test): array
    {
        $validation = $this->validate($test);

        if (! $validation['success']) {
            return $validation;
        }

        $published = DB::transaction(function () use ($test): ListeningTest {
            $test->forceFill([
                'status' => ListeningTestStatus::Published,
                'is_active' => true,
                'published_at' => $test->published_at ?? now(),
                'updated_by' => auth()->id(),
            ])->save();

            return $test->refresh();
        });

        return [
            'success' => true,
            'errors' => [],
            'test' => $published,
        ];
    }
}
