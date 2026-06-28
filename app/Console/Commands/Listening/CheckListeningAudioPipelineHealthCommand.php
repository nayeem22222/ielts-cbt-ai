<?php

declare(strict_types=1);

namespace App\Console\Commands\Listening;

use App\Enums\Listening\ListeningAudioProcessingStatus;
use App\Services\Listening\Audio\Pipeline\FfmpegBinaryService;
use App\Services\Listening\Audio\Pipeline\ListeningAudioPipelineDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CheckListeningAudioPipelineHealthCommand extends Command
{
    protected $signature = 'listening:audio:health';

    protected $description = 'Check the health of the listening audio pipeline.';

    public function handle(FfmpegBinaryService $ffmpeg): int
    {
        $this->info('Listening Audio Pipeline Health Check');
        $this->line(str_repeat('─', 50));

        $rows = [];
        $allHealthy = true;

        // 1. FFmpeg
        $ffmpegOk = $ffmpeg->checkFfmpeg();
        $rows[] = ['FFmpeg binary', $ffmpegOk ? $ffmpeg->ffmpegStatusMessage() : $ffmpeg->ffmpegStatusMessage(), $ffmpegOk ? 'OK' : 'FAIL'];

        if (! $ffmpegOk) {
            $allHealthy = false;
        }

        // 2. FFprobe
        $ffprobeOk = $ffmpeg->checkFfprobe();
        $rows[] = ['FFprobe binary', $ffprobeOk ? $ffmpeg->ffprobeStatusMessage() : $ffmpeg->ffprobeStatusMessage(), $ffprobeOk ? 'OK' : 'FAIL'];

        if (! $ffprobeOk) {
            $allHealthy = false;
        }

        // Versions
        if ($ffmpegOk || $ffprobeOk) {
            $versions = $ffmpeg->version();

            if ($versions['ffmpeg'] !== null) {
                $rows[] = ['FFmpeg version', substr($versions['ffmpeg'], 0, 60), 'INFO'];
            }

            if ($versions['ffprobe'] !== null) {
                $rows[] = ['FFprobe version', substr($versions['ffprobe'], 0, 60), 'INFO'];
            }
        }

        // 3. Storage directories
        $disk = (string) config('listening.audio.disk', 'public');
        $dirs = [
            'original' => 'listening/audio/original',
            'processed' => 'listening/audio/processed',
            'normalized' => 'listening/audio/normalized',
            'waveforms' => 'listening/audio/waveforms',
            'previews' => 'listening/audio/previews',
        ];

        foreach ($dirs as $name => $dir) {
            try {
                $storage = Storage::disk($disk);
                $absPath = $storage->path($dir);
                $exists = is_dir($absPath);
                $writable = $exists && is_writable($absPath);

                if (! $exists) {
                    @mkdir($absPath, 0755, true);
                    $writable = is_writable($absPath);
                }

                $status = $writable ? '✓ Writable' : '✗ Not writable';
                $rows[] = ["Storage: {$name}", $status, $writable ? 'OK' : 'WARN'];

                if (! $writable) {
                    $allHealthy = false;
                }
            } catch (\Throwable $e) {
                $rows[] = ["Storage: {$name}", '✗ Error: '.$e->getMessage(), 'FAIL'];
                $allHealthy = false;
            }
        }

        // 4. Queue connection
        $configuredConnection = (string) config('listening.audio_pipeline.connection', config('queue.default'));
        $effectiveConnection = ListeningAudioPipelineDispatcher::connection();
        $queueName = ListeningAudioPipelineDispatcher::queue();
        $rows[] = ['Queue connection (configured)', $configuredConnection, 'INFO'];
        $rows[] = ['Queue connection (effective)', $effectiveConnection, 'INFO'];
        $rows[] = ['Queue name', $queueName, 'INFO'];

        if (ListeningAudioPipelineDispatcher::usesSyncDriver()) {
            $rows[] = [
                'Sync queue override',
                'Configured as sync; pipeline dispatches to database to avoid HTTP timeouts.',
                'WARN',
            ];
            $this->line('');
            $this->warn('Listening audio pipeline cannot run on the sync queue (FFmpeg exceeds PHP max_execution_time).');
            $this->line('Run a worker in a separate terminal:');
            $this->line('  php artisan queue:work database --queue=listening-audio --timeout=900 --tries=3');
            $this->line('');
            $this->line('Or set LISTENING_AUDIO_QUEUE_CONNECTION=database in .env and keep the worker running.');
        } elseif ($effectiveConnection === 'database') {
            $rows[] = [
                'Queue worker',
                'Required: php artisan queue:work database --queue=listening-audio --timeout=900',
                'INFO',
            ];
        }

        // 5. Failed audio count
        try {
            $failedCount = DB::table('listening_audios')
                ->where('processing_status', ListeningAudioProcessingStatus::Failed->value)
                ->whereNull('deleted_at')
                ->count();

            $rows[] = ['Failed audio count', (string) $failedCount, $failedCount > 0 ? 'WARN' : 'OK'];
        } catch (\Throwable $e) {
            $rows[] = ['Failed audio count', 'DB error: '.$e->getMessage(), 'FAIL'];
        }

        // 6. Stuck (processing too long)
        try {
            $stuckMinutes = (int) config('listening.audio_pipeline.retry_stuck_after_minutes', 30);
            $stuckCount = DB::table('listening_audios')
                ->where('processing_status', ListeningAudioProcessingStatus::Processing->value)
                ->where('processing_started_at', '<=', now()->subMinutes($stuckMinutes))
                ->whereNull('deleted_at')
                ->count();

            $rows[] = ['Stuck processing count', (string) $stuckCount, $stuckCount > 0 ? 'WARN' : 'OK'];
        } catch (\Throwable $e) {
            $rows[] = ['Stuck processing count', 'DB error', 'FAIL'];
        }

        // 7. Recently completed
        try {
            $recentlyCompleted = DB::table('listening_audios')
                ->where('processing_status', ListeningAudioProcessingStatus::Completed->value)
                ->where('last_processed_at', '>=', now()->subHours(24))
                ->whereNull('deleted_at')
                ->count();

            $rows[] = ['Completed (last 24h)', (string) $recentlyCompleted, 'INFO'];
        } catch (\Throwable $e) {
            $rows[] = ['Completed (last 24h)', 'DB error', 'FAIL'];
        }

        $this->table(['Check', 'Value', 'Status'], $rows);

        $this->line('');

        if ($allHealthy) {
            $this->info('Pipeline health: ALL CHECKS PASSED');
        } else {
            $this->warn('Pipeline health: SOME CHECKS FAILED — review table above.');
            $this->line('');
            $this->line('FFmpeg setup:');
            $this->line('  1. Install FFmpeg for Windows from https://www.gyan.dev/ffmpeg/builds/ or via Chocolatey: choco install ffmpeg');
            $this->line('  2. Add the FFmpeg bin directory to PATH, or set FFMPEG_BINARY / FFPROBE_BINARY in .env.');
            $this->line('  3. Example .env: FFMPEG_BINARY=ffmpeg and FFPROBE_BINARY=ffprobe');
            $this->line('  4. Run php artisan config:clear, then php artisan listening:audio:health again.');
        }

        return self::SUCCESS;
    }
}
