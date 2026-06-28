<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio\Pipeline;

use Symfony\Component\Process\Process;

class FfmpegBinaryService
{
    public function ffmpegPath(): string
    {
        return $this->resolveBinary((string) config('listening.audio_pipeline.ffmpeg.binary', 'ffmpeg'), 'ffmpeg');
    }

    public function ffprobePath(): string
    {
        return $this->resolveBinary((string) config('listening.audio_pipeline.ffmpeg.ffprobe_binary', 'ffprobe'), 'ffprobe');
    }

    public function checkFfmpeg(): bool
    {
        return $this->binaryExists($this->ffmpegPath());
    }

    public function checkFfprobe(): bool
    {
        return $this->binaryExists($this->ffprobePath());
    }

    /**
     * @return array{ffmpeg: string|null, ffprobe: string|null}
     */
    public function version(): array
    {
        return [
            'ffmpeg' => $this->binaryVersion($this->ffmpegPath()),
            'ffprobe' => $this->binaryVersion($this->ffprobePath()),
        ];
    }

    public function assertAvailable(): void
    {
        if (! $this->checkFfmpeg()) {
            throw new \DomainException($this->unavailableMessage('FFmpeg'));
        }

        if (! $this->checkFfprobe()) {
            throw new \DomainException($this->unavailableMessage('FFprobe'));
        }
    }

    public function assertFfmpegAvailable(): void
    {
        if (! $this->checkFfmpeg()) {
            throw new \DomainException($this->unavailableMessage('FFmpeg'));
        }
    }

    public function assertFfprobeAvailable(): void
    {
        if (! $this->checkFfprobe()) {
            throw new \DomainException($this->unavailableMessage('FFprobe'));
        }
    }

    public function ffmpegStatusMessage(): string
    {
        return $this->checkFfmpeg()
            ? 'Available at '.$this->ffmpegPath()
            : $this->unavailableMessage('FFmpeg');
    }

    public function ffprobeStatusMessage(): string
    {
        return $this->checkFfprobe()
            ? 'Available at '.$this->ffprobePath()
            : $this->unavailableMessage('FFprobe');
    }

    private function binaryExists(string $binary): bool
    {
        if ($binary === '') {
            return false;
        }

        if (str_contains($binary, DIRECTORY_SEPARATOR) || str_contains($binary, '/')) {
            return is_file($binary) && (PHP_OS_FAMILY === 'Windows' || is_executable($binary));
        }

        $cmd = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        $process = new Process([$cmd, $binary]);

        try {
            $process->run();

            return $process->isSuccessful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function binaryVersion(string $binary): ?string
    {
        if (! $this->binaryExists($binary)) {
            return null;
        }

        $process = new Process([$binary, '-version']);

        try {
            $process->setTimeout(10);
            $process->run();

            if ($process->isSuccessful()) {
                $lines = explode("\n", $process->getOutput());

                return trim($lines[0] ?? '');
            }
        } catch (\Throwable) {
            // silent
        }

        return null;
    }

    private function resolveBinary(string $configured, string $name): string
    {
        $configured = trim($configured);

        if ($configured === '') {
            return $name;
        }

        if ($this->isExplicitPath($configured)) {
            return $configured;
        }

        $pathBinary = $this->findOnPath($configured);

        if ($pathBinary !== null) {
            return $pathBinary;
        }

        foreach ($this->commonPaths($name) as $path) {
            if (str_contains($path, '*')) {
                $matches = glob($path) ?: [];

                foreach ($matches as $match) {
                    if (is_file($match)) {
                        return $match;
                    }
                }

                continue;
            }

            if (is_file($path)) {
                return $path;
            }
        }

        return $configured;
    }

    private function findOnPath(string $binary): ?string
    {
        $cmd = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        $process = new Process([$cmd, $binary]);

        try {
            $process->setTimeout(5);
            $process->run();

            if (! $process->isSuccessful()) {
                return null;
            }

            $lines = preg_split('/\r\n|\r|\n/', trim($process->getOutput())) ?: [];
            $first = trim((string) ($lines[0] ?? ''));

            return $first !== '' ? $first : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return list<string>
     */
    private function commonPaths(string $name): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return [
                "/usr/local/bin/{$name}",
                "/usr/bin/{$name}",
                "/opt/homebrew/bin/{$name}",
            ];
        }

        $exe = "{$name}.exe";
        $userProfile = (string) ($_SERVER['USERPROFILE'] ?? getenv('USERPROFILE') ?: '');
        $localAppData = (string) ($_SERVER['LOCALAPPDATA'] ?? getenv('LOCALAPPDATA') ?: '');

        return array_values(array_filter([
            "C:\\ffmpeg\\bin\\{$exe}",
            "C:\\Program Files\\ffmpeg\\bin\\{$exe}",
            "C:\\Program Files (x86)\\ffmpeg\\bin\\{$exe}",
            "C:\\ProgramData\\chocolatey\\bin\\{$exe}",
            $userProfile !== '' ? "{$userProfile}\\scoop\\shims\\{$exe}" : null,
            $localAppData !== '' ? "{$localAppData}\\Microsoft\\WinGet\\Packages\\Gyan.FFmpeg_*\\ffmpeg-*\\bin\\{$exe}" : null,
        ]));
    }

    private function isExplicitPath(string $binary): bool
    {
        return str_contains($binary, DIRECTORY_SEPARATOR)
            || str_contains($binary, '/')
            || preg_match('/^[A-Za-z]:\\\\/', $binary) === 1;
    }

    private function unavailableMessage(string $binaryName): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return "{$binaryName} is not installed or not added to PATH.";
        }

        $envName = strtoupper($binaryName === 'FFprobe' ? 'FFPROBE' : 'FFMPEG').'_BINARY';

        return "{$binaryName} binary is not available. Please install FFmpeg or configure {$envName}.";
    }
}
