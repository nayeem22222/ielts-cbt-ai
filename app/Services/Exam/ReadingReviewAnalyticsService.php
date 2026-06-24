<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Models\ReadingAttempt;
use App\Models\ReadingPassage;
use App\Models\ReadingTest;

class ReadingReviewAnalyticsService
{
    public function __construct(private readonly ReadingTestRendererService $renderer)
    {
    }

    /**
     * @param  list<array<string, mixed>>  $parts
     * @return list<array<string, mixed>>
     */
    public function buildPartAnalytics(array $parts): array
    {
        $analytics = [];

        foreach ($parts as $part) {
            $correct = 0;
            $incorrect = 0;
            $unanswered = 0;
            $total = 0;

            foreach ($part['questions'] ?? [] as $item) {
                $total++;
                match ($item['status'] ?? 'unanswered') {
                    'correct' => $correct++,
                    'incorrect' => $incorrect++,
                    default => $unanswered++,
                };
            }

            $attempted = $correct + $incorrect;
            $accuracy = $attempted > 0 ? round(($correct / $attempted) * 100, 1) : 0.0;

            $analytics[] = [
                'part_number' => $part['part_number'] ?? null,
                'title' => $part['title'] ?? null,
                'passage_id' => $part['passage_id'] ?? null,
                'correct' => $correct,
                'incorrect' => $incorrect,
                'unanswered' => $unanswered,
                'total' => $total,
                'accuracy_percent' => $accuracy,
            ];
        }

        return $analytics;
    }

    /**
     * @param  list<array<string, mixed>>  $parts
     * @return list<array<string, mixed>>
     */
    public function buildQuestionMap(array $parts): array
    {
        $map = [];

        foreach ($parts as $part) {
            foreach ($part['questions'] ?? [] as $item) {
                $number = (int) ($item['question_number'] ?? 0);
                if ($number <= 0) {
                    continue;
                }

                $map[] = [
                    'question_number' => $number,
                    'question_id' => $item['question_id'] ?? null,
                    'passage_id' => $item['passage_id'] ?? $part['passage_id'] ?? null,
                    'status' => $this->mapStatus($item),
                    'flagged' => (bool) ($item['flagged'] ?? false),
                ];
            }
        }

        usort($map, fn (array $a, array $b): int => $a['question_number'] <=> $b['question_number']);

        return $map;
    }

    /**
     * @param  list<array<string, mixed>>  $parts
     * @return list<array<string, mixed>>
     */
    public function buildWeakAreas(array $parts): array
    {
        $byType = [];

        foreach ($parts as $part) {
            foreach ($part['questions'] ?? [] as $item) {
                $type = (string) ($item['question_type'] ?? 'unknown');
                $label = (string) ($item['question_type_label'] ?? $type);

                if (! isset($byType[$type])) {
                    $byType[$type] = [
                        'question_type' => $type,
                        'label' => $label,
                        'correct' => 0,
                        'total' => 0,
                    ];
                }

                $byType[$type]['total']++;

                if (($item['status'] ?? '') === 'correct') {
                    $byType[$type]['correct']++;
                }
            }
        }

        $insights = array_values(array_map(function (array $row): array {
            $accuracy = $row['total'] > 0
                ? round(($row['correct'] / $row['total']) * 100, 1)
                : 0.0;

            return [
                'question_type' => $row['question_type'],
                'label' => $row['label'],
                'correct' => $row['correct'],
                'total' => $row['total'],
                'accuracy_percent' => $accuracy,
                'display' => "{$row['label']}: {$row['correct']}/{$row['total']} correct",
            ];
        }, $byType));

        usort($insights, fn (array $a, array $b): int => $a['accuracy_percent'] <=> $b['accuracy_percent']);

        return $insights;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function buildReviewPassages(ReadingTest $test): array
    {
        $test = $this->renderer->loadForRenderer($test);

        return $test->passages->map(function (ReadingPassage $passage): array {
            $useAutoLabels = (bool) $passage->auto_paragraph_labels;
            $html = $useAutoLabels ? $passage->renderedContentHtml() : ($passage->content_html ?? '');

            return [
                'id' => $passage->id,
                'part_number' => $passage->part_number,
                'title' => $passage->title,
                'content_html' => $html,
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function mapStatus(array $item): string
    {
        if ((bool) ($item['flagged'] ?? false)) {
            return 'flagged';
        }

        return (string) ($item['status'] ?? 'unanswered');
    }
}
