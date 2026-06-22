<?php

declare(strict_types=1);

namespace App\Services\Admin\Exam;

use App\Enums\Commerce\IeltsModule;
use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\AnswerType;
use App\Enums\Exam\ReadingQuestionType;
use App\Enums\Exam\TestType;
use App\Models\ExamTest;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\QuestionCorrectAnswer;
use App\Models\QuestionExplanation;
use App\Models\QuestionOption;
use App\Models\QuestionTag;
use App\Models\TestModule;
use App\Models\TestQuestion;
use App\Models\TestSection;
use App\Models\User;
use App\Services\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReadingTestBuilderService extends Service
{
    public function bootstrapReadingTest(ExamTest $test): void
    {
        TestModule::query()->firstOrCreate(
            [
                'test_id' => $test->id,
                'module' => IeltsModule::Reading->value,
            ],
            [
                'title' => 'Reading Module',
                'instructions' => 'Read the passages and answer the questions.',
                'sort_order' => 1,
                'duration_seconds' => $test->duration_seconds ?: 3600,
                'status' => PublishStatus::Draft->value,
            ]
        );

        QuestionBank::query()->firstOrCreate(
            ['slug' => $test->slug.'-reading-bank'],
            [
                'name' => $test->title.' Reading Bank',
                'description' => 'Auto-generated bank for '.$test->title,
                'module' => IeltsModule::Reading->value,
                'exam_type' => $test->exam_type?->value ?? ExamType::Academic->value,
                'status' => PublishStatus::Draft->value,
                'created_by' => $test->created_by,
            ]
        );
    }

    public function readingModule(ExamTest $test): TestModule
    {
        return TestModule::query()->firstOrCreate(
            [
                'test_id' => $test->id,
                'module' => IeltsModule::Reading->value,
            ],
            [
                'title' => 'Reading Module',
                'sort_order' => 1,
                'duration_seconds' => 3600,
                'status' => PublishStatus::Draft->value,
            ]
        );
    }

    public function questionBank(ExamTest $test): QuestionBank
    {
        return QuestionBank::query()->firstOrCreate(
            ['slug' => $test->slug.'-reading-bank'],
            [
                'name' => $test->title.' Reading Bank',
                'module' => IeltsModule::Reading->value,
                'exam_type' => $test->exam_type?->value ?? ExamType::Academic->value,
                'status' => PublishStatus::Draft->value,
                'created_by' => $test->created_by,
            ]
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function savePassage(TestModule $module, array $data, ?TestSection $section = null): TestSection
    {
        $attributes = [
            'title' => $data['title'],
            'instructions' => $data['instructions'] ?? null,
            'sort_order' => (int) ($data['sort_order'] ?? 1),
            'stimulus_text' => $data['stimulus_text'] ?? null,
            'status' => $data['status'] ?? PublishStatus::Draft->value,
        ];

        if ($section !== null) {
            $section->update($attributes);

            return $section->fresh();
        }

        return TestSection::query()->create(array_merge($attributes, [
            'test_module_id' => $module->id,
        ]));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveQuestion(
        ExamTest $test,
        TestModule $module,
        TestSection $section,
        QuestionBank $bank,
        array $data,
        ?Question $question = null,
    ): Question {
        return DB::transaction(function () use ($test, $module, $section, $bank, $data, $question): Question {
            $type = ReadingQuestionType::from($data['type']);

            $questionAttributes = [
                'question_bank_id' => $bank->id,
                'module' => IeltsModule::Reading->value,
                'type' => $type->value,
                'question_number' => (int) ($data['question_number'] ?? 1),
                'prompt' => $data['prompt'],
                'stimulus' => $this->normalizeStimulus($data['stimulus'] ?? null),
                'difficulty' => $data['difficulty'] ?? 'medium',
                'marks' => (float) ($data['marks'] ?? 1),
                'sort_order' => (int) ($data['sort_order'] ?? 1),
                'status' => $data['status'] ?? PublishStatus::Draft->value,
                'created_by' => auth()->id(),
            ];

            if ($question !== null) {
                $question->update($questionAttributes);
            } else {
                $question = Question::query()->create($questionAttributes);
            }

            $this->syncOptions($question, $type, $data['options'] ?? []);
            $this->syncCorrectAnswer($question, $type, $data);
            $this->syncExplanation($question, $data['explanation'] ?? null, $data['rationale'] ?? null);
            $this->syncTags($question, $type, $data['tags'] ?? []);

            TestQuestion::query()->updateOrCreate(
                [
                    'test_section_id' => $section->id,
                    'question_id' => $question->id,
                ],
                [
                    'test_id' => $test->id,
                    'test_module_id' => $module->id,
                    'sort_order' => (int) ($data['sort_order'] ?? 1),
                    'marks' => (float) ($data['marks'] ?? 1),
                    'is_required' => true,
                ]
            );

            $this->syncCounts($test, $section);

            return $question->fresh(['options', 'correctAnswer', 'explanation', 'tags']);
        });
    }

    public function deleteQuestion(TestSection $section, Question $question): void
    {
        TestQuestion::query()
            ->where('test_section_id', $section->id)
            ->where('question_id', $question->id)
            ->delete();

        $this->syncCounts($section->module->test, $section);
    }

    public function syncCounts(ExamTest $test, ?TestSection $section = null): void
    {
        $questionCount = TestQuestion::query()->where('test_id', $test->id)->count();
        $test->update(['total_questions' => $questionCount]);

        if ($section !== null) {
            $sectionCount = TestQuestion::query()->where('test_section_id', $section->id)->count();
            $section->update(['question_count' => $sectionCount]);
        }

        $module = $this->readingModule($test);
        $module->update([
            'question_count' => TestQuestion::query()->where('test_module_id', $module->id)->count(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function exportTest(ExamTest $test): array
    {
        $test->load(['modules.sections.testQuestions.question.options', 'modules.sections.testQuestions.question.correctAnswer', 'modules.sections.testQuestions.question.explanation']);

        $module = $this->readingModule($test);
        $sections = $module->sections()->with(['testQuestions.question.options', 'testQuestions.question.correctAnswer', 'testQuestions.question.explanation', 'testQuestions.question.tags'])->get();

        return [
            'version' => 1,
            'test' => [
                'title' => $test->title,
                'slug' => $test->slug,
                'description' => $test->description,
                'exam_type' => $test->exam_type?->value,
                'duration_seconds' => $test->duration_seconds,
                'status' => $test->status?->value,
            ],
            'passages' => $sections->map(fn (TestSection $section): array => [
                'title' => $section->title,
                'instructions' => $section->instructions,
                'sort_order' => $section->sort_order,
                'stimulus_text' => $section->stimulus_text,
                'questions' => $section->testQuestions->map(function (TestQuestion $pivot): array {
                    $question = $pivot->question;

                    return [
                        'type' => $question->type->value,
                        'question_number' => $question->question_number,
                        'prompt' => $question->prompt,
                        'stimulus' => $question->stimulus,
                        'marks' => $question->marks,
                        'sort_order' => $pivot->sort_order,
                        'options' => $question->options->map(fn (QuestionOption $option): array => [
                            'label' => $option->label,
                            'option_text' => $option->option_text,
                            'sort_order' => $option->sort_order,
                        ])->values()->all(),
                        'correct_answer' => $question->correctAnswer?->answer_value,
                        'answer_json' => $question->correctAnswer?->answer_json,
                        'explanation' => $question->explanation?->explanation,
                    ];
                })->values()->all(),
            ])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function importTest(array $payload, User $user): ExamTest
    {
        return DB::transaction(function () use ($payload, $user): ExamTest {
            $testData = $payload['test'] ?? [];
            $slug = $testData['slug'] ?? Str::slug($testData['title'] ?? 'reading-test-'.Str::random(6));

            $test = ExamTest::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $testData['title'] ?? 'Imported Reading Test',
                    'description' => $testData['description'] ?? null,
                    'type' => TestType::ReadingTest->value,
                    'exam_type' => $testData['exam_type'] ?? ExamType::Academic->value,
                    'duration_seconds' => (int) ($testData['duration_seconds'] ?? 3600),
                    'status' => $testData['status'] ?? PublishStatus::Draft->value,
                    'created_by' => $user->id,
                ]
            );

            $this->bootstrapReadingTest($test);
            $module = $this->readingModule($test);
            $bank = $this->questionBank($test);

            foreach ($payload['passages'] ?? [] as $passageData) {
                $section = $this->savePassage($module, $passageData);

                foreach ($passageData['questions'] ?? [] as $questionData) {
                    $this->saveQuestion($test, $module, $section, $bank, [
                        ...$questionData,
                        'options' => collect($questionData['options'] ?? [])->map(function (mixed $option): string {
                            if (is_array($option)) {
                                return (string) ($option['option_text'] ?? $option['label'] ?? '');
                            }

                            return (string) $option;
                        })->filter()->values()->all(),
                        'correct_answer' => $questionData['correct_answer'] ?? null,
                    ]);
                }
            }

            return $test->fresh(['modules.sections']);
        });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeStimulus(mixed $stimulus): ?array
    {
        if ($stimulus === null || $stimulus === '') {
            return null;
        }

        if (is_array($stimulus)) {
            return $stimulus;
        }

        return ['html' => (string) $stimulus];
    }

    /**
     * @param  list<string|array{label?: string, option_text?: string}>  $options
     */
    private function syncOptions(Question $question, ReadingQuestionType $type, array $options): void
    {
        $question->options()->delete();

        if ($options !== []) {
            foreach ($options as $index => $option) {
                $label = is_array($option)
                    ? ($option['label'] ?? chr(65 + $index))
                    : chr(65 + $index);
                $text = is_array($option) ? ($option['option_text'] ?? '') : (string) $option;

                QuestionOption::query()->create([
                    'question_id' => $question->id,
                    'label' => $label,
                    'option_text' => $text,
                    'sort_order' => $index + 1,
                ]);
            }

            return;
        }

        foreach ($type->defaultOptions() as $index => $text) {
            QuestionOption::query()->create([
                'question_id' => $question->id,
                'label' => match ($index) {
                    0 => 'T', 1 => 'F', 2 => 'NG', default => (string) ($index + 1),
                },
                'option_text' => $text,
                'sort_order' => $index + 1,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncCorrectAnswer(Question $question, ReadingQuestionType $type, array $data): void
    {
        $answerValue = $data['correct_answer'] ?? $data['answer_value'] ?? null;
        $answerJson = $data['answer_json'] ?? null;

        if ($type === ReadingQuestionType::MultipleChoiceMultiple && is_array($answerValue)) {
            $answerJson = $answerValue;
            $answerValue = null;
        }

        QuestionCorrectAnswer::query()->updateOrCreate(
            ['question_id' => $question->id, 'answer_key' => 'default'],
            [
                'answer_type' => $answerJson !== null ? AnswerType::Json->value : AnswerType::Text->value,
                'answer_value' => is_string($answerValue) ? $answerValue : null,
                'answer_json' => $answerJson,
            ]
        );
    }

    private function syncExplanation(Question $question, ?string $explanation, ?string $rationale): void
    {
        if ($explanation === null && $rationale === null) {
            return;
        }

        QuestionExplanation::query()->updateOrCreate(
            ['question_id' => $question->id],
            [
                'explanation' => $explanation,
                'rationale' => $rationale,
            ]
        );
    }

    /**
     * @param  list<string>  $tags
     */
    private function syncTags(Question $question, ReadingQuestionType $type, array $tags): void
    {
        $question->tags()->delete();

        $tags = array_unique(array_merge(['reading', $type->value], $tags));

        foreach ($tags as $tag) {
            QuestionTag::query()->create([
                'question_id' => $question->id,
                'tag' => $tag,
            ]);
        }
    }
}
