<?php

declare(strict_types=1);

namespace App\Services\Listening\Builders;

use App\Enums\Exam\ReadingCompletionAnswerRule;
use App\Enums\Listening\ListeningAnswerFormat;
use App\Models\Listening\ListeningQuestion;
use App\Models\Listening\ListeningQuestionGroup;
use App\Services\Listening\Builders\Concerns\ManagesListeningBuilderGroup;
use App\Services\Listening\ListeningQuestionGroupService;
use App\Services\Listening\ListeningQuestionService;
use App\Support\Listening\Builder\ListeningBuilderPresenter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListeningLabellingQuestionBuilderService
{
    use ManagesListeningBuilderGroup;

    private const DISK = 'uploads';

    public function __construct(
        private readonly ListeningQuestionService $questions,
        private readonly ListeningQuestionGroupService $groups,
        private readonly ListeningBuilderPresenter $presenter,
    ) {}

    public function assertLabellingGroup(ListeningQuestionGroup $group): void
    {
        if (! $group->question_type?->isLabellingBuilderType()) {
            throw ValidationException::withMessages([
                'question_type' => 'This question group does not use the labelling question builder.',
            ]);
        }
    }

    /**
     * @return array{diagram_image: ?string, answer_rule: string, custom_answer_rule: ?string, labels: list<array<string, mixed>>}
     */
    public function groupBuilderSettings(ListeningQuestionGroup $group): array
    {
        $settings = is_array($group->settings) ? $group->settings : [];
        $options = is_array($group->options) ? $group->options : [];

        return [
            'diagram_image' => $group->image_path ?? $settings['diagram_image'] ?? null,
            'answer_rule' => (string) ($settings['answer_rule'] ?? ReadingCompletionAnswerRule::OneWordOnly->value),
            'custom_answer_rule' => $settings['custom_answer_rule'] ?? null,
            'labels' => is_array($options['labels'] ?? null) ? $options['labels'] : [],
        ];
    }

    /**
     * @return Collection<int, \App\Support\Listening\Builder\ListeningBuilderQuestionView>
     */
    public function presentQuestions(ListeningQuestionGroup $group): Collection
    {
        return $this->presenter->presentQuestions($group, $this->questions->listForGroup($group));
    }

    public function uploadDiagramImage(ListeningQuestionGroup $group, UploadedFile $file): ListeningQuestionGroup
    {
        $this->assertLabellingGroup($group);
        $mime = (string) $file->getMimeType();

        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw ValidationException::withMessages([
                'diagram_image' => 'Only JPG, PNG, and WebP diagram images are allowed.',
            ]);
        }

        return DB::transaction(function () use ($group, $file): ListeningQuestionGroup {
            $test = $this->listeningTestForGroup($group);
            $section = $group->section ?? abort(404);
            $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
            $filename = Str::uuid()->toString().'.'.$extension;
            $path = 'listening/labelling/'.$group->id.'/'.$filename;

            Storage::disk(self::DISK)->makeDirectory('listening/labelling/'.$group->id);
            Storage::disk(self::DISK)->putFileAs('listening/labelling/'.$group->id, $file, $filename);

            if ($group->image_path !== null) {
                Storage::disk(self::DISK)->delete($group->image_path);
            }

            $options = is_array($group->options) ? $group->options : [];
            $options['image'] = [
                'path' => $path,
                'alt' => (string) ($group->image_alt ?? $options['image']['alt'] ?? ''),
            ];

            $this->groups->updateBuilderState($test, $section, $group, [
                'image_path' => $path,
                'options' => $options,
                'settings' => array_merge(is_array($group->settings) ? $group->settings : [], [
                    'diagram_image' => $path,
                ]),
            ]);

            return $group->refresh();
        });
    }

    public function streamDiagramImage(ListeningQuestionGroup $group): StreamedResponse
    {
        $path = $group->image_path ?? $this->groupBuilderSettings($group)['diagram_image'];

        if ($path === null || ! Storage::disk(self::DISK)->exists($path)) {
            abort(404);
        }

        return Storage::disk(self::DISK)->response($path);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveLabels(ListeningQuestionGroup $group, array $data): ListeningQuestionGroup
    {
        $this->assertLabellingGroup($group);
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);
        $labels = is_array($data['labels'] ?? null) ? $data['labels'] : [];
        $confirmRemove = (bool) ($data['confirm_remove'] ?? false);
        $existingNumbers = $this->questions->listForGroup($group)->pluck('question_number')->map(fn ($n) => (int) $n)->all();
        $labelNumbers = array_values(array_unique(array_map(
            fn (array $label): int => (int) ($label['question_number'] ?? 0),
            $labels,
        )));
        $removed = array_diff($existingNumbers, $labelNumbers);

        if ($removed !== [] && ! $confirmRemove) {
            throw ValidationException::withMessages([
                'confirm_remove' => 'Removing labels will delete linked questions: Q'
                    .implode(', Q', $removed).'. Confirm to continue.',
            ]);
        }

        return DB::transaction(function () use ($group, $test, $section, $labels, $data, $removed, $confirmRemove): ListeningQuestionGroup {
            $options = is_array($group->options) ? $group->options : [];
            $options['labels'] = $labels;

            $settings = is_array($group->settings) ? $group->settings : [];
            $settings['answer_rule'] = (string) ($data['answer_rule'] ?? ReadingCompletionAnswerRule::OneWordOnly->value);
            $settings['custom_answer_rule'] = $data['custom_answer_rule'] ?? null;

            $this->groups->updateBuilderState($test, $section, $group, [
                'options' => $options,
                'settings' => $settings,
            ]);

            foreach ($labels as $label) {
                $number = (int) ($label['question_number'] ?? 0);

                if ($number < 1) {
                    continue;
                }

                $payload = [
                    'question_type' => $group->question_type?->value,
                    'question_text' => (string) ($label['label'] ?? "Label {$number}"),
                    'correct_answer' => trim((string) ($label['correct_answer'] ?? '')) !== ''
                        ? [['value' => trim((string) $label['correct_answer']), 'type' => 'text']]
                        : [],
                    'accepted_answers' => $this->mapAlternatives($label['alternative_answers'] ?? []),
                    'answer_format' => ListeningAnswerFormat::Text->value,
                    'case_sensitive' => (bool) ($label['case_sensitive'] ?? false),
                    'explanation' => $label['explanation'] ?? null,
                    'marks' => 1,
                    'is_active' => true,
                    'is_required' => true,
                    'meta' => [
                        'x' => (float) ($label['x'] ?? 0),
                        'y' => (float) ($label['y'] ?? 0),
                        'difficulty' => $label['difficulty'] ?? 'medium',
                    ],
                ];

                $this->questions->syncQuestionSlot($test, $section, $group, $number, $payload);
            }

            if ($removed !== [] && $confirmRemove) {
                foreach ($group->questions()->whereIn('question_number', $removed)->get() as $question) {
                    $this->questions->delete($question);
                }
            }

            return $group->refresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateQuestion(ListeningQuestion $question, array $data): ListeningQuestion
    {
        $group = $question->group ?? abort(404);
        $test = $this->listeningTestForGroup($group);
        $section = $group->section ?? abort(404);

        return $this->questions->update($test, $section, $group, $question, [
            'question_number' => (int) ($data['question_number'] ?? $question->question_number),
            'correct_answer' => trim((string) ($data['correct_answer'] ?? '')) !== ''
                ? [['value' => trim((string) $data['correct_answer']), 'type' => 'text']]
                : [],
            'accepted_answers' => $this->mapAlternatives($data['alternative_answers'] ?? []),
            'case_sensitive' => (bool) ($data['case_sensitive'] ?? $question->case_sensitive),
            'explanation' => $data['explanation'] ?? $question->explanation,
        ]);
    }

    public function deleteQuestion(ListeningQuestion $question): void
    {
        $this->questions->delete($question);
    }

    /**
     * @param  list<string>|mixed  $answers
     * @return list<array{value: string, type: string}>
     */
    private function mapAlternatives(mixed $answers): array
    {
        if (! is_array($answers)) {
            return [];
        }

        return array_values(array_map(
            fn (string $answer): array => ['value' => trim($answer), 'type' => 'text'],
            array_filter(array_map('strval', $answers), fn (string $a): bool => trim($a) !== ''),
        ));
    }
}
