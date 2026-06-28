<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio\Pipeline;

use App\DTOs\Listening\Audio\Pipeline\FfprobeMetadataResultData;
use RuntimeException;

class FfprobeMetadataReader
{
    public function __construct(
        private readonly FfmpegCommandBuilder $commandBuilder,
        private readonly FfmpegProcessRunner $processRunner,
    ) {}

    /**
     * Read and parse metadata for an audio file.
     *
     * @throws RuntimeException
     */
    public function read(string $path): FfprobeMetadataResultData
    {
        if (! is_file($path)) {
            throw new RuntimeException("Audio file does not exist: {$path}");
        }

        $command = $this->commandBuilder->buildFfprobeMetadataCommand($path);
        $json = $this->processRunner->runJson($command);

        return $this->parse($json);
    }

    /**
     * Parse a raw ffprobe JSON array.
     *
     * @param  array<string, mixed>  $json
     */
    public function parse(array $json): FfprobeMetadataResultData
    {
        return FfprobeMetadataResultData::fromFfprobe($json);
    }
}
