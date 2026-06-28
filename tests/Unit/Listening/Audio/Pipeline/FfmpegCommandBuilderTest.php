<?php

declare(strict_types=1);

namespace Tests\Unit\Listening\Audio\Pipeline;

use App\Services\Listening\Audio\Pipeline\FfmpegCommandBuilder;
use Tests\TestCase;

class FfmpegCommandBuilderTest extends TestCase
{
    private FfmpegCommandBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new FfmpegCommandBuilder;
    }

    public function test_builds_ffprobe_metadata_command_as_array(): void
    {
        $cmd = $this->builder->buildFfprobeMetadataCommand('/tmp/audio.mp3');

        $this->assertIsArray($cmd);
        $this->assertContains('-print_format', $cmd);
        $this->assertContains('json', $cmd);
        $this->assertContains('-show_format', $cmd);
        $this->assertContains('-show_streams', $cmd);
        $this->assertContains('/tmp/audio.mp3', $cmd);
        // No shell string gluing
        $this->assertStringNotContainsString('/tmp/audio.mp3', implode(' ', array_slice($cmd, 0, -1)));
    }

    public function test_builds_convert_command_with_config_values(): void
    {
        $cmd = $this->builder->buildConvertCommand('/input.wav', '/output.mp3');

        $this->assertIsArray($cmd);
        $this->assertContains('-y', $cmd);
        $this->assertContains('-i', $cmd);
        $this->assertContains('/input.wav', $cmd);
        $this->assertContains('/output.mp3', $cmd);
        $this->assertContains('-codec:a', $cmd);
        $this->assertContains('libmp3lame', $cmd);
        $this->assertContains('-b:a', $cmd);
        $this->assertContains('128k', $cmd);
        $this->assertContains('-ar', $cmd);
        $this->assertContains('-ac', $cmd);
    }

    public function test_builds_normalize_command_with_loudnorm_filter(): void
    {
        $cmd = $this->builder->buildNormalizeCommand('/input.mp3', '/output.mp3');

        $this->assertIsArray($cmd);
        $this->assertContains('-af', $cmd);
        // Find the filter argument after -af
        $afIdx = array_search('-af', $cmd);
        $this->assertNotFalse($afIdx);
        $filter = $cmd[$afIdx + 1] ?? '';
        $this->assertStringContainsString('loudnorm', $filter);
    }

    public function test_builds_silence_detect_command(): void
    {
        $cmd = $this->builder->buildSilenceDetectCommand('/input.mp3');

        $this->assertIsArray($cmd);
        $this->assertContains('-af', $cmd);
        $afIdx = array_search('-af', $cmd);
        $filter = $cmd[$afIdx + 1] ?? '';
        $this->assertStringContainsString('silencedetect', $filter);
        $this->assertContains('-f', $cmd);
        $this->assertContains('null', $cmd);
        $this->assertContains('-', $cmd);
    }

    public function test_builds_waveform_pcm_command(): void
    {
        $cmd = $this->builder->buildWaveformPcmCommand('/input.mp3', '/output.pcm');

        $this->assertIsArray($cmd);
        $this->assertContains('-i', $cmd);
        $this->assertContains('/input.mp3', $cmd);
        $this->assertContains('/output.pcm', $cmd);
        $this->assertContains('-f', $cmd);
        $this->assertContains('s16le', $cmd);
    }

    public function test_command_uses_array_not_shell_string(): void
    {
        $cmd = $this->builder->buildConvertCommand('/path with spaces/file.wav', '/output.mp3');

        // Paths with spaces must be kept as array elements, not shell-escaped strings
        $this->assertContains('/path with spaces/file.wav', $cmd);
    }
}
