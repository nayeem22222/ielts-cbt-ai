<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio;

class FakeListeningFfmpegRunner implements ListeningFfmpegRunnerInterface
{
    public function isFfmpegAvailable(): bool
    {
        return true;
    }

    public function isFfprobeAvailable(): bool
    {
        return true;
    }

    public function probe(string $absolutePath): array
    {
        return [
            'format' => [
                'duration' => '120.50',
                'bit_rate' => '128000',
                'format_name' => 'mp3',
            ],
            'streams' => [
                [
                    'codec_type' => 'audio',
                    'sample_rate' => '44100',
                    'channels' => 2,
                ],
            ],
        ];
    }

    public function convert(string $inputPath, string $outputPath): void
    {
        if (! is_file($inputPath)) {
            throw new \RuntimeException('Input file not found.');
        }

        copy($inputPath, $outputPath);
    }

    public function normalize(string $inputPath, string $outputPath, float $targetLufs): void
    {
        $this->convert($inputPath, $outputPath);
    }

    public function extractPeaks(string $absolutePath, int $samples): array
    {
        $peaks = [];

        for ($i = 0; $i < $samples; $i++) {
            $peaks[] = round((($i % 10) + 1) / 10, 4);
        }

        return $peaks;
    }
}
