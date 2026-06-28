<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio\Pipeline;

use App\DTOs\Listening\Audio\Pipeline\AudioPipelineContextData;
use App\DTOs\Listening\Audio\Pipeline\AudioPipelineStageResultData;
use App\Models\Listening\ListeningAudio;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AudioConversionPipelineStep
{
    public function __construct(
        private readonly FfmpegCommandBuilder $commandBuilder,
        private readonly FfmpegProcessRunner $processRunner,
    ) {}

    public function execute(
        ListeningAudio $audio,
        AudioPipelineContextData $context,
    ): AudioPipelineStageResultData {
        $startMs = (int) round(microtime(true) * 1000);

        $disk = Storage::disk((string) config('listening.audio.disk', 'public'));

        // Resolve absolute input path
        $inputPath = $this->resolveAbsolutePath($audio->path);

        if (! is_file($inputPath)) {
            return AudioPipelineStageResultData::failure(
                stage: 'converted',
                message: "Source file not found at: {$audio->path}",
                durationMs: $this->elapsed($startMs),
            );
        }

        // Build versioned output path
        $outputDir = 'listening/audio/processed/'.$audio->id.'/'.$context->versionTag;
        $outputRelative = $outputDir.'/audio.mp3';
        $outputAbsolute = $disk->path($outputRelative);

        // Ensure directory exists
        if (! is_dir(dirname($outputAbsolute))) {
            mkdir(dirname($outputAbsolute), 0755, true);
        }

        $cmd = $this->commandBuilder->buildConvertCommand($inputPath, $outputAbsolute);
        $result = $this->processRunner->run($cmd);

        if (! $result->successful) {
            return AudioPipelineStageResultData::failure(
                stage: 'converted',
                message: 'FFmpeg conversion failed: '.$result->truncatedErrorOutput(500),
                durationMs: $this->elapsed($startMs),
                context: ['exit_code' => $result->exitCode, 'command_hash' => $result->commandHash],
            );
        }

        if (! is_file($outputAbsolute) || filesize($outputAbsolute) === 0) {
            return AudioPipelineStageResultData::failure(
                stage: 'converted',
                message: 'FFmpeg produced an empty or missing output file.',
                durationMs: $this->elapsed($startMs),
            );
        }

        $context->processedPath = $outputRelative;

        return AudioPipelineStageResultData::success(
            stage: 'converted',
            message: 'Audio converted to MP3 successfully.',
            durationMs: $this->elapsed($startMs),
            context: [
                'output_path' => $outputRelative,
                'output_size' => filesize($outputAbsolute),
            ],
        );
    }

    private function resolveAbsolutePath(string $relativePath): string
    {
        $disk = Storage::disk((string) config('listening.audio.disk', 'public'));

        return $disk->path($relativePath);
    }

    private function elapsed(int $startMs): int
    {
        return (int) round(microtime(true) * 1000) - $startMs;
    }
}
