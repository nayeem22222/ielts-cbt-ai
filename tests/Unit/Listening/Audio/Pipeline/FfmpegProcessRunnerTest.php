<?php

declare(strict_types=1);

namespace Tests\Unit\Listening\Audio\Pipeline;

use App\DTOs\Listening\Audio\Pipeline\FfmpegCommandResultData;
use App\Services\Listening\Audio\Pipeline\FfmpegProcessRunner;
use RuntimeException;
use Tests\TestCase;

class FfmpegProcessRunnerTest extends TestCase
{
    private FfmpegProcessRunner $runner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->runner = new FfmpegProcessRunner;
    }

    public function test_returns_result_dto_for_successful_command(): void
    {
        // Use 'echo' which exists on both Unix and Windows
        $cmd = PHP_OS_FAMILY === 'Windows'
            ? ['cmd', '/C', 'echo hello']
            : ['echo', 'hello'];

        $result = $this->runner->run($cmd);

        $this->assertInstanceOf(FfmpegCommandResultData::class, $result);
        $this->assertTrue($result->successful);
        $this->assertSame(0, $result->exitCode);
        $this->assertStringContainsString('hello', trim($result->output));
    }

    public function test_returns_failure_dto_for_non_zero_exit(): void
    {
        $cmd = PHP_OS_FAMILY === 'Windows'
            ? ['cmd', '/C', 'exit 1']
            : ['bash', '-c', 'exit 1'];

        $result = $this->runner->run($cmd);

        $this->assertInstanceOf(FfmpegCommandResultData::class, $result);
        $this->assertFalse($result->successful);
        $this->assertNotSame(0, $result->exitCode);
    }

    public function test_mustrun_throws_on_failure(): void
    {
        $cmd = PHP_OS_FAMILY === 'Windows'
            ? ['cmd', '/C', 'exit 2']
            : ['bash', '-c', 'exit 2'];

        $this->expectException(RuntimeException::class);
        $this->runner->mustRun($cmd);
    }

    public function test_run_json_throws_on_invalid_json(): void
    {
        $cmd = PHP_OS_FAMILY === 'Windows'
            ? ['cmd', '/C', 'echo not-json']
            : ['echo', 'not-json'];

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/invalid JSON/i');
        $this->runner->runJson($cmd);
    }

    public function test_result_has_command_hash(): void
    {
        $cmd = PHP_OS_FAMILY === 'Windows'
            ? ['cmd', '/C', 'echo test']
            : ['echo', 'test'];

        $result = $this->runner->run($cmd);

        $this->assertNotEmpty($result->commandHash);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result->commandHash);
    }

    public function test_result_has_duration_ms(): void
    {
        $cmd = PHP_OS_FAMILY === 'Windows'
            ? ['cmd', '/C', 'echo test']
            : ['echo', 'test'];

        $result = $this->runner->run($cmd);

        $this->assertGreaterThanOrEqual(0, $result->durationMs);
    }

    public function test_truncated_output_caps_length(): void
    {
        $result = FfmpegCommandResultData::success(
            output: str_repeat('a', 5000),
            errorOutput: '',
            durationMs: 100,
            commandHash: 'abc123',
        );

        $truncated = $result->truncatedOutput(100);
        $this->assertLessThanOrEqual(200, strlen($truncated)); // 100 + truncation string
        $this->assertStringEndsWith('[truncated]', $truncated);
    }
}
