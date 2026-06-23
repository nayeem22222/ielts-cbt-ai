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

class ProQyzStyleReadingSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->where('email', 'admin@example.com')->first();

        if ($admin === null) {
            return;
        }

        if (ExamTest::query()->where('slug', 'proqyz-style-reading-demo')->exists()) {
            return;
        }

        $passages = [
            [
                'title' => 'Urban Green Spaces',
                'sort_order' => 1,
                'instructions' => 'You should spend about 20 minutes on Questions 1-13, which are based on Reading Passage 1 below.',
                'stimulus_text' => "A\nCities worldwide are investing in parks and green corridors to improve air quality.\n\nB\nResearchers note that access to nature reduces stress among commuters.\n\nC\nSome councils plant native species to support local wildlife.\n\nD\nFunding remains a challenge for smaller municipalities.",
                'questions' => $this->passageOneQuestions(),
            ],
            [
                'title' => 'The History of Bicycle Design',
                'sort_order' => 2,
                'instructions' => 'You should spend about 20 minutes on Questions 14-26, which are based on Reading Passage 2 below.',
                'stimulus_text' => "A\nEarly bicycles were heavy and difficult to steer.\n\nB\nThe safety bicycle introduced chain drives and pneumatic tyres.\n\nC\nMass production made cycling affordable for workers.\n\nD\nRacing cyclists pushed engineers to experiment with lighter frames.",
                'questions' => $this->passageTwoQuestions(),
            ],
            [
                'title' => 'Learning in Mixed-Ability Classrooms',
                'sort_order' => 3,
                'instructions' => 'You should spend about 20 minutes on Questions 27-40, which are based on Reading Passage 3 below.',
                'stimulus_text' => "Teachers often debate whether students should be grouped by ability.\n\nSome studies suggest mixed groups encourage peer learning.\n\nOthers argue that tailored instruction is more efficient.\n\nCollaborative projects may benefit both confident and hesitant learners.",
                'questions' => $this->passageThreeQuestions(),
            ],
        ];

        $test = app(ReadingTestBuilderService::class)->importTest([
            'test' => [
                'title' => 'ProQyz Style Reading Demo',
                'slug' => 'proqyz-style-reading-demo',
                'description' => 'Cambridge-style layout with mixed IELTS reading question types.',
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

    /**
     * @return list<array<string, mixed>>
     */
    private function passageOneQuestions(): array
    {
        return [
            [
                'type' => ReadingQuestionType::MatchingInformation->value,
                'question_number' => 1,
                'prompt' => 'Which section mentions financial difficulty?',
                'correct_answer' => 'D',
                'marks' => 1,
                'sort_order' => 1,
                'options' => ['A', 'B', 'C', 'D'],
            ],
            [
                'type' => ReadingQuestionType::MatchingInformation->value,
                'question_number' => 2,
                'prompt' => 'Which section mentions health benefits?',
                'correct_answer' => 'B',
                'marks' => 1,
                'sort_order' => 2,
                'options' => ['A', 'B', 'C', 'D'],
            ],
            [
                'type' => ReadingQuestionType::ShortAnswer->value,
                'question_number' => 3,
                'prompt' => 'What do councils plant to support wildlife?',
                'correct_answer' => 'native species',
                'marks' => 1,
                'sort_order' => 3,
            ],
            [
                'type' => ReadingQuestionType::TrueFalseNg->value,
                'question_number' => 4,
                'prompt' => 'All cities have sufficient funding for green spaces.',
                'correct_answer' => 'False',
                'marks' => 1,
                'sort_order' => 4,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function passageTwoQuestions(): array
    {
        return [
            [
                'type' => ReadingQuestionType::MatchingHeadings->value,
                'question_number' => 14,
                'prompt' => 'Paragraph A',
                'correct_answer' => 'i',
                'marks' => 1,
                'sort_order' => 14,
                'options' => ['i', 'ii', 'iii', 'iv'],
            ],
            [
                'type' => ReadingQuestionType::MultipleChoiceSingle->value,
                'question_number' => 15,
                'prompt' => 'What made bicycles more affordable?',
                'correct_answer' => 'Mass production',
                'marks' => 1,
                'sort_order' => 15,
                'options' => ['Mass production', 'Racing events', 'Heavier frames', 'Longer wheels'],
            ],
            [
                'type' => ReadingQuestionType::SentenceCompletion->value,
                'question_number' => 16,
                'prompt' => 'The safety bicycle used pneumatic',
                'correct_answer' => 'tyres',
                'marks' => 1,
                'sort_order' => 16,
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function passageThreeQuestions(): array
    {
        return [
            [
                'type' => ReadingQuestionType::YesNoNg->value,
                'question_number' => 27,
                'prompt' => 'The writer believes mixed-ability classes always fail.',
                'correct_answer' => 'No',
                'marks' => 1,
                'sort_order' => 27,
            ],
            [
                'type' => ReadingQuestionType::MultipleChoiceMultiple->value,
                'question_number' => 28,
                'prompt' => 'Which TWO benefits are mentioned?',
                'correct_answer' => ['peer learning', 'collaborative projects'],
                'marks' => 2,
                'sort_order' => 28,
                'options' => ['peer learning', 'collaborative projects', 'shorter lessons', 'more exams'],
            ],
            [
                'type' => ReadingQuestionType::SummaryCompletion->value,
                'question_number' => 29,
                'prompt' => 'Some teachers prefer instruction that is more',
                'correct_answer' => 'efficient',
                'marks' => 1,
                'sort_order' => 29,
            ],
        ];
    }
}
