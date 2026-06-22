<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Services\Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class BackupService extends Service
{
    public function __construct(
        private readonly SettingsService $settingsService,
    ) {
    }

    /**
     * @return array{path: string, size: int, tables: int}
     */
    public function run(): array
    {
        $disk = (string) $this->settingsService->get('backup', 'storage_disk', 'local');
        $timestamp = now()->format('Y-m-d_His');
        $directory = "backups/{$timestamp}";
        $manifestPath = "{$directory}/manifest.json";

        $tables = $this->exportTables($directory, $disk);
        $settings = $this->settingsService->allForDisplay();

        unset($settings['infrastructure']);

        foreach ($settings as $group => $values) {
            foreach ($values as $key => $value) {
                if (str_ends_with((string) $key, '_configured')) {
                    unset($settings[$group][$key]);
                    continue;
                }

                if (in_array($key, ['api_key', 'stripe_secret_key', 'webhook_secret'], true) && $value !== '') {
                    $settings[$group][$key] = '[redacted]';
                }
            }
        }

        Storage::disk($disk)->put($manifestPath, json_encode([
            'created_at' => now()->toIso8601String(),
            'app' => config('app.name'),
            'environment' => config('app.env'),
            'tables' => $tables,
            'settings' => $settings,
        ], JSON_PRETTY_PRINT));

        $fullPath = Storage::disk($disk)->path($manifestPath);
        $size = (int) (file_exists($fullPath) ? filesize($fullPath) : 0);

        return [
            'path' => $manifestPath,
            'size' => $size,
            'tables' => count($tables),
        ];
    }

    /**
     * @return list<string>
     */
    private function exportTables(string $directory, string $disk): array
    {
        $exported = [];

        if (! Schema::hasTable('settings')) {
            return $exported;
        }

        try {
            $rows = DB::table('settings')->get();

            Storage::disk($disk)->put(
                "{$directory}/settings.json",
                $rows->toJson(JSON_PRETTY_PRINT)
            );

            $exported[] = 'settings';
        } catch (Throwable) {
            // Best-effort backup for test/runtime environments.
        }

        return $exported;
    }
}
