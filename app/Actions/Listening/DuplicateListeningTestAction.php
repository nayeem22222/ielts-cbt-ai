<?php

declare(strict_types=1);

namespace App\Actions\Listening;

use App\Enums\Listening\ListeningTestStatus;
use App\Models\Listening\ListeningTest;
use App\Models\Listening\ListeningTestSetting;
use App\Repositories\Listening\ListeningTestRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DuplicateListeningTestAction
{
    public function __construct(
        private readonly ListeningTestRepository $repository,
    ) {}

    public function execute(ListeningTest $test, int $userId): ListeningTest
    {
        return DB::transaction(function () use ($test, $userId): ListeningTest {
            $test->loadMissing('setting');

            $copyTitle = $this->uniqueCopyTitle($test->title);

            $copy = $this->repository->create([
                'title' => $copyTitle,
                'slug' => $this->generateUniqueSlug($copyTitle),
                'test_code' => $this->generateTestCode(),
                'description' => $test->description,
                'status' => ListeningTestStatus::Draft,
                'test_type' => $test->test_type,
                'total_sections' => $test->total_sections,
                'total_questions' => $test->total_questions,
                'total_marks' => $test->total_marks,
                'duration_minutes' => $test->duration_minutes,
                'transfer_time_minutes' => $test->transfer_time_minutes,
                'is_active' => false,
                'is_featured' => false,
                'difficulty_level' => $test->difficulty_level,
                'instructions' => $test->instructions,
                'created_by' => $userId,
                'updated_by' => $userId,
                'published_at' => null,
                'meta' => $test->meta,
            ]);

            $settings = $test->setting?->only([
                'allow_review_after_submit',
                'show_correct_answer',
                'show_transcript_after_submit',
                'show_audio_review',
                'allow_audio_replay',
                'allow_audio_seek',
                'auto_submit_on_timer_end',
                'enable_tab_switch_detection',
                'enable_copy_protection',
                'enable_question_flagging',
                'enable_auto_save',
                'auto_save_interval_seconds',
                'settings',
            ]) ?? ListeningTestSetting::officialDefaults();

            $copy->setting()->create($settings);

            return $copy->refresh();
        });
    }

    private function uniqueCopyTitle(string $title): string
    {
        $base = rtrim($title).' Copy';
        $candidate = $base;
        $suffix = 2;

        while (ListeningTest::query()->where('title', $candidate)->exists()) {
            $candidate = $base.' '.$suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $suffix = 1;

        while ($this->repository->slugExists($slug)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function generateTestCode(): string
    {
        do {
            $code = 'LST-'.strtoupper(Str::random(8));
        } while ($this->repository->testCodeExists($code));

        return $code;
    }
}
