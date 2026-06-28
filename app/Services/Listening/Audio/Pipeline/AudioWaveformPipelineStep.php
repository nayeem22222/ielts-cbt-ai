<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio\Pipeline;

use App\DTOs\Listening\Audio\Pipeline\AudioPipelineContextData;
use App\DTOs\Listening\Audio\Pipeline\AudioPipelineStageResultData;
use App\Models\Listening\ListeningAudio;
use Illuminate\Support\Facades\Storage;

class AudioWaveformPipelineStep
{
    public function __construct(
        private readonly FfmpegCommandBuilder $commandBuilder,
        private readonly FfmpegProcessRunner $processRunner,
    ) {}

    public function execute(
        ListeningAudio $audio,
        AudioPipelineContextData $context,
    ): AudioPipelineStageResultData {
        $enabled = (bool) config('listening.audio_pipeline.waveform.enabled', true);

        if (! $enabled) {
            return AudioPipelineStageResultData::skipped('waveform_generated', 'Waveform generation disabled in config.');
        }

        $inputPath = $this->resolveInputPath($context);

        if ($inputPath === null) {
            if ((bool) config('listening.audio_pipeline.waveform.fallback_on_failure', true)) {
                $this->createFallbackWaveform($audio, $context);

                return AudioPipelineStageResultData::warning(
                    stage: 'waveform_generated',
                    message: 'No audio file found for waveform; created fallback waveform.',
                );
            }

            return AudioPipelineStageResultData::failure(
                stage: 'waveform_generated',
                message: 'No audio file available for waveform generation.',
            );
        }

        $startMs = (int) round(microtime(true) * 1000);
        $samples = (int) config('listening.audio_pipeline.waveform.samples', 1000);
        $disk = Storage::disk((string) config('listening.audio.disk', 'public'));

        // Extract PCM
        $pcmDir = sys_get_temp_dir().'/listening_waveform_'.$audio->id.'_'.time();
        $pcmFile = $pcmDir.'/raw.pcm';

        @mkdir($pcmDir, 0755, true);

        $cmd = $this->commandBuilder->buildWaveformPcmCommand($inputPath, $pcmFile);
        $result = $this->processRunner->run($cmd);

        if (! $result->successful || ! is_file($pcmFile) || filesize($pcmFile) === 0) {
            @unlink($pcmFile);
            @rmdir($pcmDir);

            if ((bool) config('listening.audio_pipeline.waveform.fallback_on_failure', true)) {
                $this->createFallbackWaveform($audio, $context);

                return AudioPipelineStageResultData::warning(
                    stage: 'waveform_generated',
                    message: 'PCM extraction failed; created fallback waveform. quality=fallback',
                    context: ['error' => $result->truncatedErrorOutput(300)],
                );
            }

            return AudioPipelineStageResultData::failure(
                stage: 'waveform_generated',
                message: 'Waveform PCM extraction failed.',
                durationMs: $this->elapsed($startMs),
                context: ['exit_code' => $result->exitCode],
            );
        }

        // Read PCM and calculate peaks
        $peaks = $this->extractPeaks($pcmFile, $samples);

        @unlink($pcmFile);
        @rmdir($pcmDir);

        // Store waveform JSON
        $waveformDir = 'listening/audio/waveforms/'.$audio->id.'/'.$context->versionTag;
        $waveformRelative = $waveformDir.'/waveform.json';
        $waveformAbsolute = $disk->path($waveformRelative);

        @mkdir(dirname($waveformAbsolute), 0755, true);

        $waveformDoc = [
            'version' => 2,
            'samples' => $samples,
            'duration_seconds' => round($context->durationSeconds ?? 0.0, 2),
            'peaks' => $peaks,
            'normalized' => true,
            'generated_at' => now()->toIso8601String(),
            'quality' => 'full',
        ];

        file_put_contents($waveformAbsolute, json_encode($waveformDoc));

        $context->waveformJsonPath = $waveformRelative;
        $context->peaks = $peaks;

        // Optional preview image
        $previewPath = null;

        if ((bool) config('listening.audio_pipeline.waveform.preview_image', true)) {
            $previewPath = $this->generatePreviewImage($audio, $context, $peaks, $disk);
        }

        if ($previewPath !== null) {
            $context->waveformPreviewPath = $previewPath;
        }

        return AudioPipelineStageResultData::success(
            stage: 'waveform_generated',
            message: 'Waveform generated successfully.',
            durationMs: $this->elapsed($startMs),
            context: [
                'samples' => count($peaks),
                'waveform_path' => $waveformRelative,
                'preview_path' => $previewPath,
            ],
        );
    }

    /**
     * @return list<float>
     */
    private function extractPeaks(string $pcmFile, int $targetSamples): array
    {
        $handle = fopen($pcmFile, 'rb');

        if ($handle === false) {
            return array_fill(0, $targetSamples, 0.0);
        }

        $samples = [];

        while (! feof($handle)) {
            $bytes = fread($handle, 2);

            if ($bytes === false || strlen($bytes) < 2) {
                break;
            }

            $sample = unpack('s', $bytes);

            if (is_array($sample)) {
                $samples[] = abs((int) ($sample[1] ?? 0));
            }
        }

        fclose($handle);

        if (count($samples) === 0) {
            return array_fill(0, $targetSamples, 0.0);
        }

        // Downsample to target count
        $downsampled = $this->downsample($samples, $targetSamples);

        // Normalize 0–1
        $max = max($downsampled) ?: 1;

        return array_map(fn (int $v): float => round($v / $max, 4), $downsampled);
    }

    /**
     * @param  list<int>  $samples
     * @return list<int>
     */
    private function downsample(array $samples, int $targetCount): array
    {
        $count = count($samples);

        if ($count <= $targetCount) {
            return $samples;
        }

        $result = [];
        $bucketSize = $count / $targetCount;

        for ($i = 0; $i < $targetCount; $i++) {
            $start = (int) floor($i * $bucketSize);
            $end = (int) floor(($i + 1) * $bucketSize);
            $slice = array_slice($samples, $start, max(1, $end - $start));
            $result[] = $slice ? (int) max($slice) : 0;
        }

        return $result;
    }

    private function createFallbackWaveform(ListeningAudio $audio, AudioPipelineContextData $context): void
    {
        $samples = (int) config('listening.audio_pipeline.waveform.samples', 1000);
        $disk = Storage::disk((string) config('listening.audio.disk', 'public'));

        $waveformDir = 'listening/audio/waveforms/'.$audio->id.'/'.$context->versionTag;
        $waveformRelative = $waveformDir.'/waveform.json';
        $waveformAbsolute = $disk->path($waveformRelative);

        @mkdir(dirname($waveformAbsolute), 0755, true);

        $peaks = array_fill(0, $samples, 0.5);

        $doc = [
            'version' => 2,
            'samples' => $samples,
            'duration_seconds' => round($context->durationSeconds ?? 0.0, 2),
            'peaks' => $peaks,
            'normalized' => true,
            'generated_at' => now()->toIso8601String(),
            'quality' => 'fallback',
        ];

        file_put_contents($waveformAbsolute, json_encode($doc));

        $context->waveformJsonPath = $waveformRelative;
        $context->peaks = $peaks;
    }

    private function generatePreviewImage(
        ListeningAudio $audio,
        AudioPipelineContextData $context,
        array $peaks,
        \Illuminate\Contracts\Filesystem\Filesystem $disk,
    ): ?string {
        if (! function_exists('imagecreate')) {
            return null;
        }

        try {
            $width = 800;
            $height = 100;
            $image = imagecreatetruecolor($width, $height);

            if ($image === false) {
                return null;
            }

            $bgColor = imagecolorallocate($image, 30, 30, 46);
            $waveColor = imagecolorallocate($image, 99, 102, 241);

            imagefill($image, 0, 0, $bgColor);

            $midY = (int) ($height / 2);
            $samplesCount = count($peaks);

            for ($x = 0; $x < $width; $x++) {
                $idx = (int) floor($x / $width * $samplesCount);
                $peak = $peaks[$idx] ?? 0.0;
                $barHeight = (int) ($peak * $midY);

                imageline($image, $x, $midY - $barHeight, $x, $midY + $barHeight, $waveColor);
            }

            $previewDir = 'listening/audio/previews/'.$audio->id.'/'.$context->versionTag;
            $previewRelative = $previewDir.'/waveform.png';
            $previewAbsolute = $disk->path($previewRelative);

            @mkdir(dirname($previewAbsolute), 0755, true);

            imagepng($image, $previewAbsolute);
            imagedestroy($image);

            return $previewRelative;
        } catch (\Throwable) {
            return null;
        }
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
