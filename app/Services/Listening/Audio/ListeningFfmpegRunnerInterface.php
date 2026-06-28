<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio;

interface ListeningFfmpegRunnerInterface
{
    public function isFfmpegAvailable(): bool;

    public function isFfprobeAvailable(): bool;

    /**
     * @return array<string, mixed>
     */
    public function probe(string $absolutePath): array;

    public function convert(string $inputPath, string $outputPath): void;

    public function normalize(string $inputPath, string $outputPath, float $targetLufs): void;

    /**
     * @return list<float>
     */
    public function extractPeaks(string $absolutePath, int $samples): array;
}
