<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Enums\Commerce\IeltsModule;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\TestAttemptStatus;
use App\Enums\Exam\TestType;
use App\Models\AutosaveLog;
use App\Models\ExamTest;
use App\Models\StudentAnswer;
use App\Models\TestAttempt;
use App\Models\TestModule;
use App\Models\TestQuestion;
use App\Models\TestSection;
use App\Models\User;
use App\Services\Admin\Exam\ReadingTestBuilderService;
use App\Services\Enrollment\PackageAccessService;
use App\Services\Exam\Analytics\ReadingQuestionTimingService;
use App\Services\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReadingPlayerService extends Service
{
    public function __construct(
        private readonly ReadingTestBuilderService $builder,
        private readonly PackageAccessService $access,
        private readonly ReadingQuestionTimingService $timings,
    ) {
    }

    public function resolvePublishedTest(): ?ExamTest
    {
        return ExamTest::query()
            ->where('type', TestType::ReadingTest)
            ->where('status', PublishStatus::Published)
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();
    }

    public function startOrResumeAttempt(User $user, ExamTest $test): TestAttempt
    {
        $existing = TestAttempt::query()
            ->where('user_id', $user->id)
            ->where('test_id', $test->id)
            ->where('status', TestAttemptStatus::InProgress)
            ->latest('id')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $module = $this->builder->readingModule($test);
        $firstSection = $module->sections()->orderBy('sort_order')->first();

        $this->access->recordModuleAttempt($user, IeltsModule::Reading);

        return TestAttempt::query()->create([
            'user_id' => $user->id,
            'test_id' => $test->id,
            'test_module_id' => $module->id,
            'current_section_id' => $firstSection?->id,
            'status' => TestAttemptStatus::InProgress,
            'started_at' => now(),
            'time_remaining_seconds' => $test->duration_seconds ?: 3600,
            'ip_address' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 500, ''),
            'metadata' => [
                'highlights' => [],
                'notes' => [],
                'active_question_id' => null,
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPlayerState(TestAttempt $attempt): array
    {
        $attempt->loadMissing(['test', 'module.sections.testQuestions.question.options']);

        $module = $attempt->module ?? $this->builder->readingModule($attempt->test);
        $sections = $module->sections()
            ->with(['testQuestions.question.options'])
            ->orderBy('sort_order')
            ->get();

        $answers = $attempt->answers()->get()->keyBy('question_id');
        $metadata = $attempt->metadata ?? [];

        $sectionPayload = $sections->map(function (TestSection $section) use ($answers, $metadata): array {
            $questions = $section->testQuestions->map(function (TestQuestion $pivot) use ($answers, $section): array {
                $question = $pivot->question;
                $saved = $answers->get($question->id);

                return [
                    'id' => $question->id,
                    'number' => $question->question_number,
                    'type' => $question->type->value,
                    'type_label' => $question->type->label(),
                    'ui_pattern' => $question->type->uiPattern(),
                    'prompt' => $question->prompt,
                    'section_id' => $section->id,
                    'options' => $question->options->map(fn ($option): array => [
                        'label' => $option->label,
                        'text' => $option->option_text,
                    ])->values()->all(),
                    'answer_text' => $saved?->answer_text ?? '',
                    'selected_options' => $saved?->selected_options ?? [],
                    'is_flagged' => (bool) ($saved?->is_flagged ?? false),
                    'is_answered' => $saved !== null && (
                        filled($saved->answer_text) || filled($saved->selected_options)
                    ),
                ];
            })->values()->all();

            $numbers = collect($questions)->pluck('number')->filter()->values();
            $questionFrom = $numbers->min();
            $questionTo = $numbers->max();

            return [
                'id' => $section->id,
                'part_label' => 'Part '.$section->sort_order,
                'title' => $section->title,
                'instructions' => $section->instructions,
                'stimulus_text' => $section->stimulus_text,
                'sort_order' => $section->sort_order,
                'question_from' => $questionFrom,
                'question_to' => $questionTo,
                'question_count' => count($questions),
                'highlights' => $metadata['highlights'][(string) $section->id] ?? [],
                'note' => $metadata['notes'][(string) $section->id] ?? '',
                'questions' => $questions,
            ];
        })->values()->all();

        $allQuestions = collect($sectionPayload)->flatMap(fn (array $section): array => $section['questions'])->values();

        return [
            'attempt' => [
                'uuid' => $attempt->uuid,
                'status' => $attempt->status->value,
                'time_remaining_seconds' => $attempt->time_remaining_seconds,
                'current_section_id' => $attempt->current_section_id,
                'active_question_id' => $metadata['active_question_id'] ?? ($allQuestions->first()['id'] ?? null),
            ],
            'test' => [
                'id' => $attempt->test->id,
                'title' => $attempt->test->title,
                'duration_seconds' => $attempt->test->duration_seconds ?: 3600,
            ],
            'sections' => $sectionPayload,
            'questions' => $allQuestions->all(),
            'autosave_url' => route('exam.reading.autosave', $attempt),
            'submit_url' => route('exam.reading.submit', $attempt),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function autosave(TestAttempt $attempt, array $payload): array
    {
        return DB::transaction(function () use ($attempt, $payload): array {
            $attempt->loadMissing('test');

            if ($attempt->status !== TestAttemptStatus::InProgress) {
                return ['saved_at' => now()->toIso8601String(), 'status' => $attempt->status->value];
            }

            $metadata = $attempt->metadata ?? [];
            $metadata['highlights'] = $payload['highlights'] ?? $metadata['highlights'] ?? [];
            $metadata['notes'] = $payload['notes'] ?? $metadata['notes'] ?? [];
            $metadata['active_question_id'] = $payload['active_question_id'] ?? $metadata['active_question_id'] ?? null;

            $attempt->update([
                'current_section_id' => $payload['current_section_id'] ?? $attempt->current_section_id,
                'time_remaining_seconds' => isset($payload['time_remaining_seconds'])
                    ? (int) $payload['time_remaining_seconds']
                    : $attempt->time_remaining_seconds,
                'metadata' => $metadata,
            ]);

            $savedAnswers = [];

            foreach ($payload['answers'] ?? [] as $answerData) {
                $questionId = (int) ($answerData['question_id'] ?? 0);

                if ($questionId <= 0) {
                    continue;
                }

                $pivot = TestQuestion::query()
                    ->where('test_id', $attempt->test_id)
                    ->where('question_id', $questionId)
                    ->first();

                $studentAnswer = StudentAnswer::query()->updateOrCreate(
                    [
                        'test_attempt_id' => $attempt->id,
                        'question_id' => $questionId,
                    ],
                    [
                        'test_section_id' => $pivot?->test_section_id,
                        'test_question_id' => $pivot?->id,
                        'module' => IeltsModule::Reading->value,
                        'answer_text' => $answerData['answer_text'] ?? null,
                        'selected_options' => $answerData['selected_options'] ?? null,
                        'is_flagged' => (bool) ($answerData['is_flagged'] ?? false),
                        'is_final' => false,
                    ]
                );

                $savedAnswers[] = $studentAnswer->id;
            }

            if (isset($payload['question_timings']) && is_array($payload['question_timings'])) {
                $this->timings->syncTimings($attempt, $payload['question_timings']);
            }

            AutosaveLog::query()->create([
                'test_attempt_id' => $attempt->id,
                'payload' => [
                    'answers_count' => count($payload['answers'] ?? []),
                    'current_section_id' => $attempt->current_section_id,
                    'active_question_id' => $metadata['active_question_id'],
                ],
                'saved_at' => now(),
            ]);

            return [
                'saved_at' => now()->toIso8601String(),
                'status' => $attempt->status->value,
                'answers_saved' => count($savedAnswers),
            ];
        });
    }
}
