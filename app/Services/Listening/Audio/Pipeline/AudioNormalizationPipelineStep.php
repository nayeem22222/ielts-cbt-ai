<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio\Pipeline;

use App\DTOs\Listening\Audio\Pipeline\AudioPipelineContextData;
use App\DTOs\Listening\Audio\Pipeline\AudioPipelineStageResultData;
use App\Models\Listening\ListeningAudio;
use Illuminate\Support\Facades\Storage;

class AudioNormalizationPipelineStep
{
    public function __construct(
        private readonly FfmpegCommandBuilder $commandBuilder,
        private readonly FfmpegProcessRunner $processRunner,
    ) {}

    public function execute(
        ListeningAudio $audio,
        AudioPipelineContextData $context,
    ): AudioPipelineStageResultData {
        $enabled = (bool) config('listening.audio_pipeline.normalization.enabled', true);

        if (! $enabled) {
            return AudioPipelineStageResultData::skipped('normalized', 'Normalization is disabled in config.');
        }

        if ($context->processedPath === null) {
            return AudioPipelineStageResultData::skipped('normalized', 'No processed file available for normalization.');
        }

        $startMs = (int) round(microtime(true) * 1000);

        $disk = Storage::disk((string) config('listening.audio.disk', 'public'));
        $inputAbsolute = $disk->path($context->processedPath);

        if (! is_file($inputAbsolute)) {
            return AudioPipelineStageResultData::failure(
                stage: 'normalized',
                message: 'Processed file not found for normalization.',
                durationMs: $this->elapsed($startMs),
            );
        }

        $outputDir = 'listening/audio/normalized/'.$audio->id.'/'.$context->versionTag;
        $outputRelative = $outputDir.'/audio.mp3';
        $outputAbsolute = $disk->path($outputRelative);

        if (! is_dir(dirname($outputAbsolute))) {
            mkdir(dirname($outputAbsolute), 0755, true);
        }

        $cmd = $this->commandBuilder->buildNormalizeCommand($inputAbsolute, $outputAbsolute);
        $result = $this->processRunner->run($cmd);

        if (! $result->successful) {
            $strict = (bool) config('listening.audio_pipeline.normalization.strict', false);

            if ($strict) {
                return AudioPipelineStageResultData::failure(
                    stage: 'normalized',
                    message: 'Normalization failed: '.$result->truncatedErrorOutput(500),
                    durationMs: $this->elapsed($startMs),
                    context: ['exit_code' => $result->exitCode],
                );
            }

            // Non-strict: warn and skip normalization
            $context->addWarning('Normalization failed. Using processed (non-normalized) file instead.');

            return AudioPipelineStageResultData::warning(
                stage: 'normalized',
                message: 'Normalization failed but pipeline continues (strict=false). Using converted file.',
                context: ['error' => $result->truncatedErrorOutput(300)],
            );
        }

        if (! is_file($outputAbsolute) || filesize($outputAbsolute) === 0) {
            $context->addWarning('Normalization produced empty output. Using processed file instead.');

            return AudioPipelineStageResultData::warning(
                stage: 'normalized',
                message: 'Normalization produced empty output. Falling back to converted file.',
            );
        }

        $context->normalizedPath = $outputRelative;

        // Try to parse loudness from FFmpeg stderr
        $this->parseLoudness($result->errorOutput, $context);

        return AudioPipelineStageResultData::success(
            stage: 'normalized',
            message: 'Audio normalized successfully.',
            durationMs: $this->elapsed($startMs),
            context: [
                'output_path' => $outputRelative,
                'loudness_lufs' => $context->loudnessLufs,
            ],
        );
    }

    private function parseLoudness(string $stderr, AudioPipelineContextData $context): void
    {
        // Parse loudness from loudnorm JSON summary at end of stderr
        if (preg_match('/\{[^}]*"input_i"\s*:\s*"([^"]+)"[^}]*\}/s', $stderr, $matches)) {
            $jsonStr = $matches[0];
            $loudnessData = json_decode($jsonStr, true);

            if (is_array($loudnessData)) {
                if (isset($loudnessData['input_i']) && is_numeric($loudnessData['input_i'])) {
                    $context->loudnessLufs = (float) $loudnessData['input_i'];
                }

                if (isset($loudnessData['input_tp']) && is_numeric($loudnessData['input_tp'])) {
                    $context->peakDb = (float) $loudnessData['input_tp'];
                }
            }
        }
    }

    private function elapsed(int $startMs): int
    {
        return (int) round(microtime(true) * 1000) - $startMs;
    }
}
