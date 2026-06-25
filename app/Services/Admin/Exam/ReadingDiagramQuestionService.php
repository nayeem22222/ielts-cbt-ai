<?php

declare(strict_types=1);

namespace App\Services\Admin\Exam;

use App\Enums\Exam\OfficialReadingQuestionType;
use App\Enums\Exam\ReadingCompletionAnswerRule;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionGroup;
use App\Models\ReadingTest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Support\Reading\ReadingQuestionReferenceSupport;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReadingDiagramQuestionService
{
    private const DISK = 'uploads';

    public function __construct(private readonly ReadingCompletionTemplateService $template)
    {
    }

    public function loadGroupForBuilder(ReadingQuestionGroup $group): ReadingQuestionGroup
    {
        return $group->load([
            'passage.test',
            'questions' => fn ($query) => $query
                ->with(['correctAnswers'])
                ->orderBy('sort_order'),
        ]);
    }

    public function assertDiagramGroup(ReadingQuestionGroup $group): void
    {
        if (! $group->question_type?->isDiagramBuilderType()) {
            throw ValidationException::withMessages([
                'question_type' => 'This question group does not use the diagram label builder.',
            ]);
        }
    }

    public function readingTestForGroup(ReadingQuestionGroup $group): ReadingTest
    {
        /** @var ReadingPassage $passage */
        $passage = $group->passage()->firstOrFail();

        return $passage->test()->firstOrFail();
    }

    /**
     * @return array{
     *     diagram_image: ?string,
     *     answer_rule: string,
     *     custom_answer_rule: ?string,
     *     labels: list<array<string, mixed>>
     * }
     */
    public function groupBuilderSettings(ReadingQuestionGroup $group): array
    {
        $settings = $group->settings ?? [];

        return [
            'diagram_image' => $settings['diagram_image'] ?? null,
            'answer_rule' => (string) ($settings['answer_rule'] ?? ReadingCompletionAnswerRule::OneWordOnly->value),
            'custom_answer_rule' => $settings['custom_answer_rule'] ?? null,
            'labels' => is_array($settings['labels'] ?? null) ? $settings['labels'] : [],
        ];
    }

    public function uploadDiagramImage(ReadingQuestionGroup $group, UploadedFile $file): ReadingQuestionGroup
    {
        $this->assertDiagramGroup($group);

        return DB::transaction(function () use ($group, $file): ReadingQuestionGroup {
            $settings = $group->settings ?? [];
            $previousPath = $settings['diagram_image'] ?? null;

            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
            $filename = Str::uuid()->toString().'.'.$extension;
            $path = 'reading/diagrams/'.$group->id.'/'.$filename;

            Storage::disk(self::DISK)->makeDirectory('reading/diagrams/'.$group->id);

            Storage::disk(self::DISK)->putFileAs(
                'reading/diagrams/'.$group->id,
                $file,
                $filename,
            );

            $settings['diagram_image'] = $path;
            $group->forceFill(['settings' => $settings])->save();

            if (is_string($previousPath) && $previousPath !== '' && $previousPath !== $path) {
                Storage::disk(self::DISK)->delete($previousPath);
            }

            return $this->loadGroupForBuilder($group->refresh());
        });
    }

    public function streamDiagramImage(ReadingQuestionGroup $group): StreamedResponse
    {
        $this->assertDiagramGroup($group);

        $path = $this->groupBuilderSettings($group)['diagram_image'];

        if (! is_string($path) || $path === '' || ! Storage::disk(self::DISK)->exists($path)) {
            abort(404);
        }

        return Storage::disk(self::DISK)->response($path);
    }

    /**
     * @param  array{
     *     answer_rule: string,
     *     custom_answer_rule?: ?string,
     *     labels: list<array{
     *         question_number: int,
     *         x: float|int|string,
     *         y: float|int|string,
     *         label?: ?string,
     *         correct_answer?: ?string,
     *         alternative_answers?: list<string>|null,
     *         case_sensitive?: bool,
     *         explanation?: ?string,
     *         difficulty?: ?string
     *     }>,
     *     confirm_remove?: bool
     * }  $data
     */
    public function saveLabels(ReadingQuestionGroup $group, array $data): ReadingQuestionGroup
    {
        $this->assertDiagramGroup($group);

        return DB::transaction(function () use ($group, $data): ReadingQuestionGroup {
            $labels = $data['labels'] ?? [];
            $confirmRemove = (bool) ($data['confirm_remove'] ?? false);

            $this->validateLabels($group, $labels);

            $incomingNumbers = array_map(
                fn (array $label): int => (int) $label['question_number'],
                $labels,
            );

            $existingNumbers = $group->questions()
                ->where('question_number', '>', 0)
                ->pluck('question_number')
                ->map(fn ($value) => (int) $value)
                ->all();

            $toRemove = array_values(array_diff($existingNumbers, $incomingNumbers));

            if ($toRemove !== [] && ! $confirmRemove) {
                throw ValidationException::withMessages([
                    'confirm_remove' => 'Removing labels will delete linked questions: '
                        .implode(', ', $toRemove).'. Confirm to continue.',
                ]);
            }

            $settings = $group->settings ?? [];
            $settings['answer_rule'] = (string) $data['answer_rule'];
            $settings['custom_answer_rule'] = $data['custom_answer_rule'] ?? null;
            $settings['labels'] = array_map(fn (array $label): array => [
                'question_number' => (int) $label['question_number'],
                'x' => round((float) $label['x'], 2),
                'y' => round((float) $label['y'], 2),
                'label' => isset($label['label']) ? trim((string) $label['label']) : null,
            ], $labels);

            $group->forceFill(['settings' => $settings])->save();

            $wordLimit = (string) $data['answer_rule'];

            foreach ($labels as $index => $label) {
                $questionNumber = (int) $label['question_number'];
                $labelText = isset($label['label']) ? trim((string) $label['label']) : '';

                /** @var ReadingQuestion|null $question */
                $question = $group->questions()->where('question_number', $questionNumber)->first();

                $metadata = [
                    'diagram_label' => true,
                    'x' => round((float) $label['x'], 2),
                    'y' => round((float) $label['y'], 2),
                    'label' => $labelText !== '' ? $labelText : null,
                ];

                if ($question === null) {
                    /** @var ReadingQuestion $question */
                    $question = $group->questions()->create([
                        'question_number' => $questionNumber,
                        'prompt' => $labelText !== '' ? $labelText : "Label {$questionNumber}",
                        'sort_order' => $index + 1,
                        'marks' => 1,
                        'difficulty' => (string) ($label['difficulty'] ?? 'medium'),
                        'metadata' => $metadata,
                    ]);
                } else {
                    $question->forceFill([
                        'prompt' => $labelText !== '' ? $labelText : ($question->prompt ?: "Label {$questionNumber}"),
                        'sort_order' => $index + 1,
                        'metadata' => array_merge($question->metadata ?? [], $metadata),
                    ]);

                    if (isset($label['difficulty'])) {
                        $question->difficulty = (string) $label['difficulty'];
                    }

                    if (array_key_exists('explanation', $label)) {
                        $question->explanation = $label['explanation'];
                    }
                }

                ReadingQuestionReferenceSupport::applyAttributes($question, $label);
                $question->save();

                if (
                    array_key_exists('correct_answer', $label)
                    || array_key_exists('alternative_answers', $label)
                    || array_key_exists('case_sensitive', $label)
                ) {
                    $this->assertPublishedAnswerPresent($group, $label);
                    $this->syncCorrectAnswers($question, $label, $wordLimit);
                }
            }

            if ($toRemove !== [] && $confirmRemove) {
                $group->questions()->whereIn('question_number', $toRemove)->delete();
            }

            return $this->loadGroupForBuilder($group->refresh());
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateQuestion(ReadingQuestion $question, array $data): ReadingQuestion
    {
        return DB::transaction(function () use ($question, $data): ReadingQuestion {
            $group = $question->group()->firstOrFail();
            $this->assertDiagramGroup($group);

            if (isset($data['question_number'])) {
                $questionNumber = (int) $data['question_number'];
                $this->assertQuestionNumberIsValid($group, $questionNumber, $question);
                $question->question_number = $questionNumber;
            }

            if (array_key_exists('label', $data)) {
                $labelText = trim((string) ($data['label'] ?? ''));
                $metadata = $question->metadata ?? [];
                $metadata['label'] = $labelText !== '' ? $labelText : null;

                if (isset($data['x'])) {
                    $metadata['x'] = round((float) $data['x'], 2);
                }

                if (isset($data['y'])) {
                    $metadata['y'] = round((float) $data['y'], 2);
                }

                $question->metadata = $metadata;
                $question->prompt = $labelText !== '' ? $labelText : ($question->prompt ?: "Label {$question->question_number}");
            }

            if (array_key_exists('explanation', $data)) {
                $question->explanation = $data['explanation'];
            }

            if (isset($data['difficulty'])) {
                $question->difficulty = (string) $data['difficulty'];
            }

            ReadingQuestionReferenceSupport::applyAttributes($question, $data);

            $question->save();

            $this->syncGroupLabelSettings($group, $question);

            if (
                array_key_exists('correct_answer', $data)
                || array_key_exists('alternative_answers', $data)
                || array_key_exists('case_sensitive', $data)
            ) {
                $this->assertPublishedAnswerPresent($group, $data);
                $settings = $this->groupBuilderSettings($group);
                $this->syncCorrectAnswers($question, $data, $settings['answer_rule']);
            }

            return $question->load(['correctAnswers']);
        });
    }

    public function deleteLabel(ReadingQuestion $question): void
    {
        DB::transaction(function () use ($question): void {
            $group = $question->group()->firstOrFail();
            $this->assertDiagramGroup($group);

            $questionNumber = (int) $question->question_number;
            $question->delete();

            $settings = $group->settings ?? [];
            $labels = is_array($settings['labels'] ?? null) ? $settings['labels'] : [];
            $settings['labels'] = array_values(array_filter(
                $labels,
                fn (array $label): bool => (int) ($label['question_number'] ?? 0) !== $questionNumber,
            ));

            $group->forceFill(['settings' => $settings])->save();
        });
    }

    /**
     * @param  list<array<string, mixed>>  $labels
     */
    private function validateLabels(ReadingQuestionGroup $group, array $labels): void
    {
        if ($labels === []) {
            throw ValidationException::withMessages([
                'labels' => 'At least one diagram label is required.',
            ]);
        }

        $seen = [];

        foreach ($labels as $index => $label) {
            $number = (int) ($label['question_number'] ?? 0);
            $x = (float) ($label['x'] ?? -1);
            $y = (float) ($label['y'] ?? -1);

            if ($number < 1) {
                throw ValidationException::withMessages([
                    "labels.{$index}.question_number" => 'Question number is required.',
                ]);
            }

            if (isset($seen[$number])) {
                throw ValidationException::withMessages([
                    'labels' => "Duplicate question number {$number} in diagram labels.",
                ]);
            }

            $seen[$number] = true;

            if ($group->start_question !== null && $number < $group->start_question) {
                throw ValidationException::withMessages([
                    'labels' => "Question number {$number} is below group range ({$group->question_range_label}).",
                ]);
            }

            if ($group->end_question !== null && $number > $group->end_question) {
                throw ValidationException::withMessages([
                    'labels' => "Question number {$number} is above group range ({$group->question_range_label}).",
                ]);
            }

            if ($x < 0 || $x > 100 || $y < 0 || $y > 100) {
                throw ValidationException::withMessages([
                    "labels.{$index}.position" => 'Label coordinates must be between 0 and 100 percent.',
                ]);
            }
        }
    }

    private function syncGroupLabelSettings(ReadingQuestionGroup $group, ReadingQuestion $question): void
    {
        $metadata = $question->metadata ?? [];
        $settings = $group->settings ?? [];
        $labels = is_array($settings['labels'] ?? null) ? $settings['labels'] : [];
        $number = (int) $question->question_number;
        $updated = false;

        foreach ($labels as $index => $label) {
            if ((int) ($label['question_number'] ?? 0) === $number) {
                $labels[$index]['x'] = round((float) ($metadata['x'] ?? $label['x'] ?? 0), 2);
                $labels[$index]['y'] = round((float) ($metadata['y'] ?? $label['y'] ?? 0), 2);
                $labels[$index]['label'] = $metadata['label'] ?? null;
                $updated = true;

                break;
            }
        }

        if (! $updated) {
            $labels[] = [
                'question_number' => $number,
                'x' => round((float) ($metadata['x'] ?? 0), 2),
                'y' => round((float) ($metadata['y'] ?? 0), 2),
                'label' => $metadata['label'] ?? null,
            ];
        }

        $settings['labels'] = $labels;
        $group->forceFill(['settings' => $settings])->save();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertPublishedAnswerPresent(ReadingQuestionGroup $group, array $data): void
    {
        if ($group->status?->value !== 'published') {
            return;
        }

        if (trim((string) ($data['correct_answer'] ?? '')) === '') {
            throw ValidationException::withMessages([
                'correct_answer' => 'Correct answer is required for published diagram groups.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncCorrectAnswers(ReadingQuestion $question, array $data, string $wordLimit): void
    {
        $this->template->syncCorrectAnswers($question, [
            'correct_answer' => $data['correct_answer'] ?? null,
            'alternative_answers' => $data['alternative_answers'] ?? [],
            'case_sensitive' => (bool) ($data['case_sensitive'] ?? false),
            'word_limit' => $wordLimit,
            'regex' => $data['regex'] ?? null,
        ]);
    }

    private function assertQuestionNumberIsValid(
        ReadingQuestionGroup $group,
        int $questionNumber,
        ?ReadingQuestion $except = null,
    ): void {
        if ($questionNumber < 1) {
            throw ValidationException::withMessages([
                'question_number' => 'Question number is required.',
            ]);
        }

        if ($group->start_question !== null && $questionNumber < $group->start_question) {
            throw ValidationException::withMessages([
                'question_number' => "Question number must be at least {$group->start_question} for this group.",
            ]);
        }

        if ($group->end_question !== null && $questionNumber > $group->end_question) {
            throw ValidationException::withMessages([
                'question_number' => "Question number must not exceed {$group->end_question} for this group.",
            ]);
        }

        $groupQuery = $group->questions()->where('question_number', $questionNumber)->where('question_number', '>', 0);

        if ($except !== null) {
            $groupQuery->whereKeyNot($except->id);
        }

        if ($groupQuery->exists()) {
            throw ValidationException::withMessages([
                'question_number' => "Question number {$questionNumber} already exists in this group.",
            ]);
        }

        $test = $this->readingTestForGroup($group);
        $testQuery = $test->questions()->where('question_number', $questionNumber)->where('question_number', '>', 0);

        if ($except !== null) {
            $testQuery->whereKeyNot($except->id);
        }

        if ($testQuery->exists()) {
            throw ValidationException::withMessages([
                'question_number' => "Question number {$questionNumber} is already used in this reading test.",
            ]);
        }
    }
}
