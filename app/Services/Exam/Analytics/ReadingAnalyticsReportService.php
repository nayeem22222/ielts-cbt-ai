<?php

declare(strict_types=1);

namespace App\Services\Exam\Analytics;

use App\Enums\Exam\TestType;
use App\Models\ExamTest;
use App\Models\ReadingAnalytics;
use App\Services\Service;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReadingAnalyticsReportService extends Service
{
    /**
     * @return array<string, mixed>
     */
    public function testSummary(ExamTest $test): array
    {
        $analytics = ReadingAnalytics::query()
            ->where('test_id', $test->id)
            ->with(['user', 'attempt'])
            ->latest('computed_at')
            ->get();

        return [
            'test' => [
                'id' => $test->id,
                'title' => $test->title,
                'slug' => $test->slug,
            ],
            'attempt_count' => $analytics->count(),
            'average_accuracy' => round((float) $analytics->avg('accuracy_percent'), 2),
            'average_time_seconds' => (int) round((float) $analytics->avg('average_time_seconds')),
            'average_band' => round((float) $analytics->avg('band'), 1),
            'total_skipped' => (int) $analytics->sum('skipped_count'),
            'band_distribution' => $this->bandDistribution($analytics),
            'heat_map' => $this->aggregateHeatMap($analytics),
            'recent_attempts' => $analytics->take(10)->map(fn (ReadingAnalytics $item): array => [
                'uuid' => $item->uuid,
                'student' => $item->user?->name,
                'band' => (float) $item->band,
                'accuracy_percent' => (float) $item->accuracy_percent,
                'average_time_seconds' => $item->average_time_seconds,
                'skipped_count' => $item->skipped_count,
                'computed_at' => $item->computed_at?->toDateTimeString(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(): array
    {
        $readingTestIds = ExamTest::query()
            ->where('type', TestType::ReadingTest)
            ->pluck('id');

        $analytics = ReadingAnalytics::query()
            ->whereIn('test_id', $readingTestIds)
            ->get();

        return [
            'total_attempts' => $analytics->count(),
            'average_accuracy' => round((float) $analytics->avg('accuracy_percent'), 2),
            'average_time_seconds' => (int) round((float) $analytics->avg('average_time_seconds')),
            'band_distribution' => $this->bandDistribution($analytics),
            'tests' => ExamTest::query()
                ->where('type', TestType::ReadingTest)
                ->withCount(['testQuestions'])
                ->get()
                ->map(fn (ExamTest $test): array => [
                    'id' => $test->id,
                    'title' => $test->title,
                    'attempts' => ReadingAnalytics::query()->where('test_id', $test->id)->count(),
                ]),
        ];
    }

    public function exportTestReport(ExamTest $test): StreamedResponse
    {
        $summary = $this->testSummary($test);
        $filename = $test->slug.'-reading-analytics.csv';

        return response()->streamDownload(function () use ($summary, $test): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Reading Analytics Report', $test->title]);
            fputcsv($handle, []);
            fputcsv($handle, ['Metric', 'Value']);
            fputcsv($handle, ['Attempts', $summary['attempt_count']]);
            fputcsv($handle, ['Average Accuracy %', $summary['average_accuracy']]);
            fputcsv($handle, ['Average Time (seconds)', $summary['average_time_seconds']]);
            fputcsv($handle, ['Average Band', $summary['average_band']]);
            fputcsv($handle, ['Total Skipped Questions', $summary['total_skipped']]);
            fputcsv($handle, []);
            fputcsv($handle, ['Band Distribution']);
            fputcsv($handle, ['Band', 'Count']);

            foreach ($summary['band_distribution'] as $band => $count) {
                fputcsv($handle, [$band, $count]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['Question Heat Map']);
            fputcsv($handle, ['Question', 'Avg Accuracy %', 'Avg Time (s)', 'Attempts', 'Intensity']);

            foreach ($summary['heat_map']['cells'] ?? [] as $cell) {
                fputcsv($handle, [
                    $cell['question_number'],
                    $cell['accuracy_percent'],
                    $cell['average_time_seconds'],
                    $cell['attempt_count'],
                    $cell['intensity'],
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * @param  Collection<int, ReadingAnalytics>  $analytics
     * @return array<string, int>
     */
    private function bandDistribution(Collection $analytics): array
    {
        $distribution = [];

        foreach ($analytics as $item) {
            $band = number_format((float) $item->band, 1, '.', '');
            $distribution[$band] = ($distribution[$band] ?? 0) + 1;
        }

        ksort($distribution, SORT_NUMERIC);

        return $distribution;
    }

    /**
     * @param  Collection<int, ReadingAnalytics>  $analytics
     * @return array<string, mixed>
     */
    private function aggregateHeatMap(Collection $analytics): array
    {
        $buckets = [];

        foreach ($analytics as $item) {
            foreach ($item->time_per_question ?? [] as $entry) {
                $number = (int) ($entry['question_number'] ?? 0);

                if ($number <= 0) {
                    continue;
                }

                if (! isset($buckets[$number])) {
                    $buckets[$number] = [
                        'question_number' => $number,
                        'accuracy_total' => 0,
                        'time_total' => 0,
                        'attempt_count' => 0,
                    ];
                }

                $buckets[$number]['accuracy_total'] += (float) ($entry['accuracy_percent'] ?? 0);
                $buckets[$number]['time_total'] += (int) ($entry['time_spent_seconds'] ?? 0);
                $buckets[$number]['attempt_count']++;
            }
        }

        $cells = collect($buckets)
            ->sortKeys()
            ->map(function (array $bucket): array {
                $attempts = max(1, $bucket['attempt_count']);
                $accuracy = round($bucket['accuracy_total'] / $attempts, 2);
                $avgTime = (int) round($bucket['time_total'] / $attempts);

                return [
                    'question_number' => $bucket['question_number'],
                    'accuracy_percent' => $accuracy,
                    'average_time_seconds' => $avgTime,
                    'attempt_count' => $bucket['attempt_count'],
                    'intensity' => round(max((100 - $accuracy) / 100, min(1, $avgTime / 120)), 4),
                    'tone' => $accuracy >= 80 ? 'low' : ($accuracy >= 50 ? 'medium' : 'high'),
                ];
            })
            ->values()
            ->all();

        return [
            'cells' => $cells,
            'legend' => [
                'low' => 'High accuracy',
                'medium' => 'Moderate difficulty',
                'high' => 'Low accuracy / slow',
            ],
        ];
    }
}
