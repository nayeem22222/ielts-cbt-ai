<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio\Pipeline;

use App\DTOs\Listening\Audio\Pipeline\AudioPipelineContextData;
use App\DTOs\Listening\Audio\Pipeline\AudioPipelineStageResultData;
use App\Models\Listening\ListeningAudio;
use Illuminate\Support\Facades\Storage;

class AudioSilenceDetectionStep
{
    public function __construct(
        private readonly FfmpegCommandBuilder $commandBuilder,
        private readonly FfmpegProcessRunner $processRunner,
    ) {}

    public function execute(
        ListeningAudio $audio,
        AudioPipelineContextData $context,
    ): AudioPipelineStageResultData {
        $enabled = (bool) config('listening.audio_pipeline.silence_detection.enabled', true);

        if (! $enabled) {
            return AudioPipelineStageResultData::skipped('silence_detected', 'Silence detection disabled in config.');
        }

        $inputPath = $this->resolveInputPath($context);

        if ($inputPath === null) {
            return AudioPipelineStageResultData::skipped('silence_detected', 'No audio file to analyse for silence.');
        }

        $startMs = (int) round(microtime(true) * 1000);

        $cmd = $this->commandBuilder->buildSilenceDetectCommand($inputPath);
        $result = $this->processRunner->run($cmd);

        // FFmpeg silence detect outputs to stderr; non-zero exit is normal here
        $report = $this->parseReport($result->errorOutput, $context->durationSeconds ?? 0.0);
        $context->silenceReport = $report;

        $warnThreshold = (int) config(
            'listening.audio_pipeline.silence_detection.warn_if_total_silence_percent_above',
            20
        );

        $silencePercent = $report['silence_percent'] ?? 0.0;

        if ($silencePercent > $warnThreshold) {
            $context->addWarning(
                sprintf(
                    'High silence detected: %.1f%% of audio is silent (threshold: %d%%).',
                    $silencePercent,
                    $warnThreshold,
                )
            );
        }

        return AudioPipelineStageResultData::success(
            stage: 'silence_detected',
            message: sprintf(
                'Silence detection complete. Total silence: %.1fs (%.1f%%).',
                $report['total_silence_seconds'] ?? 0.0,
                $silencePercent,
            ),
            durationMs: $this->elapsed($startMs),
            context: $report,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function parseReport(string $stderr, float $totalDuration): array
    {
        $segments = [];
        $totalSilenceSeconds = 0.0;

        // Parse silence_start markers
        preg_match_all('/silence_start:\s*([\d.]+)/', $stderr, $startMatches);
        preg_match_all('/silence_end:\s*([\d.]+)/', $stderr, $endMatches);

        $starts = array_map('floatval', $startMatches[1] ?? []);
        $ends = array_map('floatval', $endMatches[1] ?? []);

        $count = min(count($starts), count($ends));

        for ($i = 0; $i < $count; $i++) {
            $start = $starts[$i];
            $end = $ends[$i];
            $duration = max(0.0, $end - $start);

            $segments[] = [
                'start' => round($start, 2),
                'end' => round($end, 2),
                'duration' => round($duration, 2),
            ];

            $totalSilenceSeconds += $duration;
        }

        $silencePercent = $totalDuration > 0
            ? round(($totalSilenceSeconds / $totalDuration) * 100, 2)
            : 0.0;

        $warnThreshold = (int) config(
            'listening.audio_pipeline.silence_detection.warn_if_total_silence_percent_above',
            20
        );

        return [
            'total_silence_seconds' => round($totalSilenceSeconds, 2),
            'silence_percent' => $silencePercent,
            'segments' => $segments,
            'warning' => $silencePercent > $warnThreshold,
        ];
    }

    private function resolveInputPath(AudioPipelineContextData $context): ?string
    {
        $relPath = $context->normalizedPath ?? $context->processedPath;

        if ($relPath === null) {
            return null;
        }

        $disk = Storage::disk((string) config('listening.audio.disk', 'public'));
        $abs = $disk->path($relPath);

        return is_file($abs) ? $abs : null;
    }

    private function elapsed(int $startMs): int
    {
        return (int) round(microtime(true) * 1000) - $startMs;
    }
}
