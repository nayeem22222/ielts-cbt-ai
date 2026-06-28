<?php

declare(strict_types=1);

namespace App\Services\Listening\Audio\Pipeline;

class FfmpegCommandBuilder
{
    public function __construct(
        private readonly ?FfmpegBinaryService $binaries = null,
    ) {}

    /**
     * Build an ffprobe metadata extraction command.
     *
     * @return list<string>
     */
    public function buildFfprobeMetadataCommand(string $input): array
    {
        return [
            $this->ffprobePath(),
            '-v', 'quiet',
            '-print_format', 'json',
            '-show_format',
            '-show_streams',
            $input,
        ];
    }

    /**
     * Build an ffmpeg audio conversion command (to MP3).
     *
     * @return list<string>
     */
    public function buildConvertCommand(string $input, string $output): array
    {
        $cmd = [$this->ffmpegPath()];

        if ($this->hideBanner()) {
            $cmd[] = '-hide_banner';
        }

        $cmd[] = '-y';
        $cmd[] = '-i';
        $cmd[] = $input;
        $cmd[] = '-codec:a';
        $cmd[] = (string) config('listening.audio_pipeline.conversion.codec', 'libmp3lame');
        $cmd[] = '-b:a';
        $cmd[] = (string) config('listening.audio_pipeline.conversion.bitrate', '128k');
        $cmd[] = '-ar';
        $cmd[] = (string) config('listening.audio_pipeline.conversion.sample_rate', 44100);
        $cmd[] = '-ac';
        $cmd[] = (string) config('listening.audio_pipeline.conversion.channels', 2);

        $threads = (int) config('listening.audio_pipeline.ffmpeg.threads', 2);

        if ($threads > 0) {
            $cmd[] = '-threads';
            $cmd[] = (string) $threads;
        }

        $cmd[] = $output;

        return $cmd;
    }

    /**
     * Build a loudnorm normalization command.
     *
     * @return list<string>
     */
    public function buildNormalizeCommand(string $input, string $output): array
    {
        $targetLufs = (float) config('listening.audio_pipeline.normalization.target_lufs', -16);
        $truePeak = (float) config('listening.audio_pipeline.normalization.true_peak', -1.5);
        $lra = (int) config('listening.audio_pipeline.normalization.lra', 11);

        $filter = sprintf('loudnorm=I=%s:TP=%s:LRA=%d', $targetLufs, $truePeak, $lra);

        $cmd = [$this->ffmpegPath()];

        if ($this->hideBanner()) {
            $cmd[] = '-hide_banner';
        }

        $cmd[] = '-y';
        $cmd[] = '-i';
        $cmd[] = $input;
        $cmd[] = '-af';
        $cmd[] = $filter;

        $threads = (int) config('listening.audio_pipeline.ffmpeg.threads', 2);

        if ($threads > 0) {
            $cmd[] = '-threads';
            $cmd[] = (string) $threads;
        }

        $cmd[] = $output;

        return $cmd;
    }

    /**
     * Build a silence detection command (output goes to stderr).
     *
     * @return list<string>
     */
    public function buildSilenceDetectCommand(string $input): array
    {
        $noiseDb = (int) config('listening.audio_pipeline.silence_detection.noise_threshold_db', -35);
        $minDuration = (int) config('listening.audio_pipeline.silence_detection.min_silence_duration', 2);

        $filter = sprintf('silencedetect=noise=%ddB:d=%d', $noiseDb, $minDuration);

        $cmd = [$this->ffmpegPath()];

        if ($this->hideBanner()) {
            $cmd[] = '-hide_banner';
        }

        $cmd[] = '-i';
        $cmd[] = $input;
        $cmd[] = '-af';
        $cmd[] = $filter;
        $cmd[] = '-f';
        $cmd[] = 'null';
        $cmd[] = '-';

        return $cmd;
    }

    /**
     * Build a raw PCM extraction command for waveform peak analysis.
     *
     * @return list<string>
     */
    public function buildWaveformPcmCommand(string $input, string $output): array
    {
        $cmd = [$this->ffmpegPath()];

        if ($this->hideBanner()) {
            $cmd[] = '-hide_banner';
        }

        $cmd[] = '-i';
        $cmd[] = $input;
        $cmd[] = '-ac';
        $cmd[] = '1';
        $cmd[] = '-filter:a';
        $cmd[] = 'aresample=8000';
        $cmd[] = '-map';
        $cmd[] = '0:a';
        $cmd[] = '-c:a';
        $cmd[] = 'pcm_s16le';
        $cmd[] = '-f';
        $cmd[] = 's16le';
        $cmd[] = '-y';
        $cmd[] = $output;

        return $cmd;
    }

    private function ffmpegPath(): string
    {
        return ($this->binaries ?? new FfmpegBinaryService)->ffmpegPath();
    }

    private function ffprobePath(): string
    {
        return ($this->binaries ?? new FfmpegBinaryService)->ffprobePath();
    }

    private function hideBanner(): bool
    {
        return (bool) config('listening.audio_pipeline.ffmpeg.hide_banner', true);
    }
}
