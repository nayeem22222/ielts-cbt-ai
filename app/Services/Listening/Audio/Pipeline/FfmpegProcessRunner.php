<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio\Pipeline;

use App\DTOs\Listening\Audio\Pipeline\FfmpegCommandResultData;
use RuntimeException;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class FfmpegProcessRunner
{
    /**
     * Run an FFmpeg/FFprobe command and return structured result.
     *
     * @param  list<string>  $command
     */
    public function run(array $command, ?int $timeout = null): FfmpegCommandResultData
    {
        $resolvedTimeout = $timeout ?? (int) config('listening.audio_pipeline.ffmpeg.timeout', 600);
        $commandHash = hash('sha256', implode(' ', array_map(
            fn (string $arg): string => preg_replace('/\/[^\/]+\//', '/...//', $arg) ?? $arg,
            $command,
        )));

        $startMs = (int) round(microtime(true) * 1000);

        try {
            $process = new Process($command);
            $process->setTimeout($resolvedTimeout);
            $process->run();

            $durationMs = (int) round(microtime(true) * 1000) - $startMs;

            return new FfmpegCommandResultData(
                exitCode: $process->getExitCode() ?? -1,
                successful: $process->isSuccessful(),
                output: $this->truncate($process->getOutput()),
                errorOutput: $this->truncate($process->getErrorOutput()),
                durationMs: $durationMs,
                commandHash: $commandHash,
            );
        } catch (ProcessFailedException $e) {
            $durationMs = (int) round(microtime(true) * 1000) - $startMs;
            $proc = $e->getProcess();

            return FfmpegCommandResultData::failure(
                exitCode: $proc->getExitCode() ?? -1,
                output: $this->truncate($proc->getOutput()),
                errorOutput: $this->truncate($proc->getErrorOutput()),
                durationMs: $durationMs,
                commandHash: $commandHash,
            );
        } catch (\Throwable $e) {
            $durationMs = (int) round(microtime(true) * 1000) - $startMs;

            return FfmpegCommandResultData::failure(
                exitCode: -1,
                output: '',
                errorOutput: $e->getMessage(),
                durationMs: $durationMs,
                commandHash: $commandHash,
            );
        }
    }

    /**
     * Run a command and parse its stdout as JSON.
     *
     * @param  list<string>  $command
     * @return array<string, mixed>
     *
     * @throws RuntimeException
     */
    public function runJson(array $command, ?int $timeout = null): array
    {
        $result = $this->run($command, $timeout);

        if (! $result->successful) {
            throw new RuntimeException(
                'FFmpeg process failed: '.($result->truncatedErrorOutput() ?: "exit code {$result->exitCode}")
            );
        }

        $decoded = json_decode($result->output, true);

        if (! is_array($decoded)) {
            throw new RuntimeException(
                'FFmpeg process returned invalid JSON. Output: '.$result->truncatedOutput(500)
            );
        }

        return $decoded;
    }

    /**
     * Run a command that must succeed or throw.
     *
     * @param  list<string>  $command
     *
     * @throws RuntimeException
     */
    public function mustRun(array $command, ?int $timeout = null): FfmpegCommandResultData
    {
        $result = $this->run($command, $timeout);

        if (! $result->successful) {
            throw new RuntimeException(
                'FFmpeg process failed (exit '.$result->exitCode.'): '.
                ($result->truncatedErrorOutput(1000) ?: $result->truncatedOutput(500))
            );
        }

        return $result;
    }

    private function truncate(string $text, int $max = 3000): string
    {
        if (strlen($text) <= $max) {
            return $text;
        }

        return substr($text, 0, $max).'...[truncated]';
    }
}
