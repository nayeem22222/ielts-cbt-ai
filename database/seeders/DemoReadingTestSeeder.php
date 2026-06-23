<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\ReadingQuestionType;
use App\Models\ExamTest;
use App\Models\User;
use App\Services\Admin\Exam\ReadingTestBuilderService;
use Illuminate\Database\Seeder;

class DemoReadingTestSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->first();

        if ($admin === null) {
            return;
        }

        if (ExamTest::query()->where('slug', 'demo-reading-test-40')->exists()) {
            return;
        }

        $passageSizes = [14, 13, 13];
        $passages = [];
        $questionNumber = 1;

        foreach ($passageSizes as $index => $size) {
            $questions = [];

            for ($i = 0; $i < $size; $i++) {
                $questions[] = [
                    'type' => ReadingQuestionType::TrueFalseNg->value,
                    'question_number' => $questionNumber,
                    'prompt' => "According to the passage, statement {$questionNumber} is supported by the text.",
                    'correct_answer' => 'True',
                    'marks' => 1,
                    'sort_order' => $questionNumber,
                ];
                $questionNumber++;
            }

            $passages[] = [
                'title' => 'Passage '.($index + 1),
                'sort_order' => $index + 1,
                'instructions' => 'Questions '.($questionNumber - $size).'–'.($questionNumber - 1),
                'stimulus_text' => 'This is demo passage '.($index + 1).' for the Arif Academy IELTS reading practice test. '
                    .'It contains academic-style content for skimming, scanning, and detail questions.',
                'questions' => $questions,
            ];
        }

        $test = app(ReadingTestBuilderService::class)->importTest([
            'test' => [
                'title' => 'Demo Reading Test (40 Questions)',
                'slug' => 'demo-reading-test-40',
                'description' => 'Three passages and forty True/False/Not Given questions for practice.',
                'exam_type' => ExamType::Academic->value,
                'duration_seconds' => 3600,
                'status' => PublishStatus::Published->value,
            ],
            'passages' => $passages,
        ], $admin);

        $test->update([
            'status' => PublishStatus::Published,
            'published_at' => now(),
        ]);
    }
}
