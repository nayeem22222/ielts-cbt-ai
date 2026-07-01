<?php

declare(strict_types=1);

namespace App\Services\Listening\Result;

class ListeningResultAnalyticsService
{
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
                'section_id' => $part['section_id'] ?? null,
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
                    'section_id' => $item['section_id'] ?? $part['section_id'] ?? null,
                    'status' => $this->mapStatus($item),
                    'flagged' => (bool) ($item['flagged'] ?? false),
                ];
            }
        }

        usort($map, fn (array $a, array $b): int => $a['question_number'] <=> $b['question_number']);

        return $map;
    }

    /**
     * @param  list<array<string, mixed>>  $typeBreakdown
     * @return list<array<string, mixed>>
     */
    public function buildWeakAreas(array $typeBreakdown): array
    {
        $insights = [];

        foreach ($typeBreakdown as $row) {
            $total = (int) ($row['total'] ?? 0);
            $correct = (float) ($row['correct'] ?? 0);
            $accuracy = $total > 0
                ? round(($correct / $total) * 100, 1)
                : (float) ($row['percentage'] ?? 0);

            $insights[] = [
                'question_type' => $row['question_type'] ?? 'unknown',
                'label' => $row['label'] ?? ($row['question_type'] ?? 'Unknown'),
                'correct' => (int) round($correct),
                'total' => $total,
                'accuracy_percent' => $accuracy,
            ];
        }

        usort($insights, fn (array $a, array $b): int => $a['accuracy_percent'] <=> $b['accuracy_percent']);

        return $insights;
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
