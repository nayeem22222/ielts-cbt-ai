<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio;

use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ListeningFfmpegRunner implements ListeningFfmpegRunnerInterface
{
    public function isFfmpegAvailable(): bool
    {
        if (! config('listening.audio.ffmpeg.enabled', true)) {
            return false;
        }

        return $this->binaryExists((string) config('listening.audio.ffmpeg.binary', 'ffmpeg'));
    }

    public function isFfprobeAvailable(): bool
    {
        if (! config('listening.audio.ffmpeg.enabled', true)) {
            return false;
        }

        return $this->binaryExists((string) config('listening.audio.ffmpeg.ffprobe_binary', 'ffprobe'));
    }

    public function probe(string $absolutePath): array
    {
        if (! $this->isFfprobeAvailable()) {
            throw new RuntimeException('FFprobe is not available. Install FFmpeg or configure FFPROBE_BINARY.');
        }

        $process = new Process([
            (string) config('listening.audio.ffmpeg.ffprobe_binary', 'ffprobe'),
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_format',
            '-show_streams',
            $absolutePath,
        ]);

        $this->runProcess($process);

        $decoded = json_decode($process->getOutput(), true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Unable to decode FFprobe output.');
        }

        return $decoded;
    }

    public function convert(string $inputPath, string $outputPath): void
    {
        if (! $this->isFfmpegAvailable()) {
            throw new RuntimeException('FFmpeg is not available. Install FFmpeg or configure FFMPEG_BINARY.');
        }

        $process = new Process([
            (string) config('listening.audio.ffmpeg.binary', 'ffmpeg'),
            '-y',
            '-i', $inputPath,
            '-codec:a', 'libmp3lame',
            '-b:a', (string) config('listening.audio.target_bitrate', '128k'),
            '-ar', (string) config('listening.audio.target_sample_rate', 44100),
            '-ac', (string) config('listening.audio.target_channels', 2),
            $outputPath,
        ]);

        $this->runProcess($process);
    }

    public function normalize(string $inputPath, string $outputPath, float $targetLufs): void
    {
        if (! $this->isFfmpegAvailable()) {
            throw new RuntimeException('FFmpeg is not available. Install FFmpeg or configure FFMPEG_BINARY.');
        }

        $process = new Process([
            (string) config('listening.audio.ffmpeg.binary', 'ffmpeg'),
            '-y',
            '-i', $inputPath,
            '-af', sprintf('loudnorm=I=%s:TP=-1.5:LRA=11', $targetLufs),
            $outputPath,
        ]);

        $this->runProcess($process);
    }

    public function extractPeaks(string $absolutePath, int $samples): array
    {
        if (! $this->isFfmpegAvailable()) {
            return $this->basicPeaks($samples);
        }

        $process = new Process([
            (string) config('listening.audio.ffmpeg.binary', 'ffmpeg'),
            '-i', $absolutePath,
            '-ac', '1',
            '-filter:a', 'aresample=8000',
            '-f', 's16le',
            '-',
        ]);

        try {
            $this->runProcess($process);
        } catch (RuntimeException) {
            return $this->basicPeaks($samples);
        }

        $raw = $process->getOutput();

        if ($raw === '') {
            return $this->basicPeaks($samples);
        }

        $values = array_values(unpack('s*', $raw) ?: []);

        if ($values === []) {
            return $this->basicPeaks($samples);
        }

        $chunkSize = max(1, (int) ceil(count($values) / $samples));
        $peaks = [];

        for ($i = 0; $i < $samples; $i++) {
            $chunk = array_slice($values, $i * $chunkSize, $chunkSize);
            $max = 0;

            foreach ($chunk as $sample) {
                $max = max($max, abs((int) $sample));
            }

            $peaks[] = round(min(1, $max / 32768), 4);
        }

        return $peaks;
    }

    private function runProcess(Process $process): void
    {
        $process->setTimeout((int) config('listening.audio.ffmpeg.timeout', 300));

        try {
            $process->mustRun();
        } catch (ProcessFailedException $exception) {
            throw new RuntimeException(trim($exception->getProcess()->getErrorOutput()) ?: $exception->getMessage(), 0, $exception);
        }
    }

    private function binaryExists(string $binary): bool
    {
        if ($binary === '') {
            return false;
        }

        if (str_contains($binary, DIRECTORY_SEPARATOR) || str_contains($binary, '/')) {
            return is_executable($binary);
        }

        $process = new Process([PHP_OS_FAMILY === 'Windows' ? 'where' : 'which', $binary]);

        try {
            $process->run();

            return $process->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return list<float>
     */
    private function basicPeaks(int $samples): array
    {
        $peaks = [];

        for ($i = 0; $i < $samples; $i++) {
            $peaks[] = round(abs(sin($i / max(1, $samples / 20))) * 0.5 + 0.05, 4);
        }

        return $peaks;
    }
}
