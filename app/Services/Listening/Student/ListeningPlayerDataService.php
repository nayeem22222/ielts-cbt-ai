<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\Enums\Listening\ListeningAnswerStatus;
use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;

class ListeningPlayerDataService
{
    public function __construct(
        private readonly ListeningNavigationService $navigation,
        private readonly ListeningTimerService $timer,
        private readonly ListeningAudioAccessService $audioAccess,
        private readonly ListeningGroupRendererService $groupRenderer,
    ) {}

    /**
     * @param  list<array<string, mixed>>  $groupQuestions
     * @return array<string, mixed>
     */
    public function sanitizeGroup(ListeningQuestionGroup $group, ListeningAttempt $attempt, array $groupQuestions = []): array
    {
        $sanitized = [
            'id' => $group->id,
            'title' => $group->title,
            'instruction' => $group->instruction,
            'question_type' => $group->question_type?->value,
            'type_label' => $group->question_type?->label() ?? 'Questions',
            'start_question_number' => (int) $group->start_question_number,
            'end_question_number' => (int) $group->end_question_number,
            'layout_type' => $group->layout_type?->value ?? 'default',
            'content' => $group->content,
            'options' => $this->sanitizeOptions($group->options, $group->question_type?->value),
            'settings' => $this->sanitizeSettings($group->settings),
            'image_url' => $this->groupImageUrl($group, $attempt),
            'section_number' => (int) ($group->section?->section_number ?? $this->navigation->sectionForQuestionNumber((int) $group->start_question_number)),
        ];

        if ($group->question_type?->value === 'multiple_answer') {
            $sanitized['required_answers_count'] = max(
                1,
                (int) $group->end_question_number - (int) $group->start_question_number + 1,
            );
        }

        $sanitized['rendered_html'] = $this->groupRenderer->render($sanitized, $groupQuestions);

        return $sanitized;
    }

    /**
     * @return array<string, mixed>
     */
    public function sanitizeQuestion(ListeningQuestion $question, ?ListeningAttempt $attemptAnswerSource): array
    {
        $answerRow = $attemptAnswerSource?->answers
            ->firstWhere('listening_question_id', $question->id);

        $meta = is_array($answerRow?->meta) ? $answerRow->meta : [];

        return [
            'id' => $question->id,
            'question_number' => (int) $question->question_number,
            'question_type' => $question->question_type?->value,
            'question_text' => $question->question_text,
            'question_html' => $question->question_html,
            'instruction' => $question->instruction,
            'options' => $this->sanitizeOptions($question->options, $question->question_type?->value),
            'word_limit' => $question->word_limit,
            'group_id' => $question->listening_question_group_id,
            'section_number' => (int) ($question->section?->section_number ?? $this->navigation->sectionForQuestionNumber((int) $question->question_number)),
            'student_answer' => $answerRow?->student_answer,
            'answer_status' => $answerRow?->answer_status?->value ?? ListeningAnswerStatus::Unanswered->value,
            'is_flagged' => ($meta['is_flagged'] ?? false) === true,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function buildSectionsPayload(ListeningAttempt $attempt): array
    {
        $test = $attempt->test;
        $sections = [];

        foreach ($test?->sections()->where('is_active', true)->orderBy('section_number')->get() ?? [] as $section) {
            $sections[] = [
                'number' => (int) $section->section_number,
                'title' => $section->title,
                'instruction' => $section->instruction,
                'start_question_number' => (int) $section->start_question_number,
                'end_question_number' => (int) $section->end_question_number,
                'preparation_seconds' => $section->preparation_seconds,
                'audio_stream_url' => route('student.listening.attempts.audio.section', [
                    'attempt' => $attempt->id,
                    'section' => $section->section_number,
                ]),
                'has_playable_audio' => $this->audioAccess->sectionHasPlayableAudio($section),
            ];
        }

        return $sections;
    }

    /**
     * @return array<string, mixed>
     */
    public function playerConfig(): array
    {
        return array_merge(
            (array) config('listening.student_player', []),
            [
                'navigation' => (array) config('listening.navigation', []),
                'autosave' => (array) config('listening.autosave', []),
                'recovery' => (array) config('listening.recovery', []),
                'official_flow' => (array) config('listening.official_flow', []),
                'official_audio' => (array) config('listening.official_audio', []),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function playerRoutes(ListeningAttempt $attempt): array
    {
        return [
            'save_answer' => route('student.listening.attempts.answers.save', $attempt),
            'bulk_save' => route('student.listening.attempts.answers.bulk_save', $attempt),
            'autosave' => route('student.listening.attempts.autosave', $attempt),
            'autosave_bulk' => route('student.listening.attempts.autosave.bulk', $attempt),
            'navigation_update' => route('student.listening.attempts.navigation.update', $attempt),
            'state_sync' => route('student.listening.attempts.state.sync', $attempt),
            'review_summary' => route('student.listening.attempts.review', $attempt),
            'submit' => route('student.listening.attempts.submit', $attempt),
            'submitted' => route('student.listening.attempts.submitted', $attempt),
            'expired' => route('student.listening.attempts.expired', $attempt),
            'flag' => route('student.listening.attempts.questions.flag', ['attempt' => $attempt->id, 'question' => '__QUESTION__']),
            'timer_state' => route('student.listening.attempts.timer.state', $attempt),
            'timer_sync' => route('student.listening.attempts.timer.sync', $attempt),
            'auto_submit' => route('student.listening.attempts.auto_submit', $attempt),
            'audio_start' => route('student.listening.attempts.audio.start', $attempt),
            'audio_end' => route('student.listening.attempts.audio.end', $attempt),
            'phase_transfer' => route('student.listening.attempts.phase.transfer', $attempt),
        ];
    }

    /**
     * @param  array<string, mixed>|null  $options
     * @return array<string, mixed>|list<array<string, mixed>>|null
     */
    private function sanitizeOptions(?array $options, ?string $type): array|null
    {
        if ($options === null || $options === []) {
            return null;
        }

        if (array_is_list($options) && isset($options[0]) && is_array($options[0])) {
            return array_values(array_map(
                fn (array $option): array => Arr::only($option, ['key', 'text', 'label']),
                $options,
            ));
        }

        $sanitized = $options;

        if (isset($sanitized['choices']) && is_array($sanitized['choices'])) {
            $sanitized['choices'] = array_values(array_map(
                fn (array $choice): array => Arr::only($choice, ['key', 'text']),
                $sanitized['choices'],
            ));
        }

        if (isset($sanitized['items']) && is_array($sanitized['items'])) {
            $sanitized['items'] = array_values(array_map(function ($item): array {
                if (! is_array($item)) {
                    return ['key' => (string) $item, 'text' => (string) $item];
                }

                return Arr::only($item, ['key', 'text', 'label']);
            }, $sanitized['items']));
        }

        if (isset($sanitized['labels']) && is_array($sanitized['labels'])) {
            $sanitized['labels'] = array_values(array_map(
                fn (array $label): array => Arr::only($label, ['key', 'text']),
                $sanitized['labels'],
            ));
        }

        if (isset($sanitized['points']) && is_array($sanitized['points'])) {
            $sanitized['points'] = array_values(array_map(
                fn (array $point): array => Arr::only($point, ['number', 'x', 'y', 'label']),
                $sanitized['points'],
            ));
        }

        if (isset($sanitized['image']) && is_array($sanitized['image'])) {
            unset($sanitized['image']['path']);
        }

        unset($sanitized['is_correct']);

        return $sanitized;
    }

    /**
     * @param  array<string, mixed>|null  $settings
     * @return array<string, mixed>|null
     */
    private function sanitizeSettings(?array $settings): ?array
    {
        if ($settings === null) {
            return null;
        }

        return Arr::only($settings, [
            'required_answers',
            'required_answers_count',
            'display_instruction',
            'template_type',
            'word_limit',
            'partial_marking',
        ]);
    }

    private function groupImageUrl(ListeningQuestionGroup $group, ListeningAttempt $attempt): ?string
    {
        if (blank($group->image_path)) {
            return null;
        }

        if (config('listening.audio_access.use_signed_routes', true)) {
            return URL::temporarySignedRoute(
                'student.listening.attempts.groups.image',
                now()->addMinutes((int) config('listening.audio_access.signed_url_ttl_minutes', 60)),
                ['attempt' => $attempt->id, 'group' => $group->id],
            );
        }

        return route('student.listening.attempts.groups.image', [
            'attempt' => $attempt->id,
            'group' => $group->id,
        ]);
    }
}
