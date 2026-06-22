<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\Settings\SettingsGroup;
use App\Models\Setting;
use App\Services\Service;
use App\Support\Settings\SettingsSchema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SettingsService extends Service
{
    private const CACHE_KEY = 'settings.all';

    /**
     * @return array<string, array<string, mixed>>
     */
    public function allForDisplay(): array
    {
        $data = [];

        foreach (SettingsGroup::cases() as $group) {
            $data[$group->value] = $this->groupForDisplay($group);
        }

        $data['infrastructure'] = $this->infrastructureStatus();

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function groupForDisplay(SettingsGroup $group): array
    {
        $values = $this->group($group);

        foreach (SettingsSchema::encryptedKeys($group) as $key) {
            if (! empty($values[$key])) {
                $values[$key] = '';
                $values[$key.'_configured'] = true;
            }
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    public function group(SettingsGroup $group): array
    {
        return Cache::remember(
            self::CACHE_KEY.'.'.$group->value,
            now()->addHour(),
            fn (): array => $this->loadGroup($group)
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateGroup(SettingsGroup $group, array $data): void
    {
        $fields = SettingsSchema::fields($group);

        foreach ($fields as $key => $meta) {
            if (! array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            $encrypted = ($meta['encrypted'] ?? false) === true;

            if ($encrypted && ($value === '' || $value === null)) {
                continue;
            }

            if (in_array($key, ['maintenance_mode', 'evaluation_enabled', 'sandbox_mode', 'public_uploads', 'use_for_cache', 'use_for_queue', 'use_for_session', 'failed_job_alerts', 'force_email_verification', 'require_2fa_admins', 'enabled'], true)) {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
            }

            $stored = is_bool($value) ? ($value ? '1' : '0') : (string) $value;

            if ($encrypted) {
                $stored = Crypt::encryptString($stored);
            }

            Setting::query()->updateOrCreate(
                ['group' => $group->value, 'key' => $key],
                ['value' => $stored, 'is_encrypted' => $encrypted]
            );
        }

        $this->flushCache($group);
    }

    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $groupEnum = SettingsGroup::tryFrom($group);

        if ($groupEnum === null) {
            return $default;
        }

        $values = $this->group($groupEnum);

        return $values[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function infrastructureStatus(): array
    {
        return [
            'storage' => $this->storageStatus(),
            'redis' => $this->redisStatus(),
            'queue' => $this->queueStatus(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function storageStatus(): array
    {
        $defaultDisk = (string) config('filesystems.default');
        $writable = false;
        $detail = 'Unknown';

        try {
            Storage::disk($defaultDisk)->put('_health_check.txt', 'ok');
            $writable = Storage::disk($defaultDisk)->get('_health_check.txt') === 'ok';
            Storage::disk($defaultDisk)->delete('_health_check.txt');
            $detail = $writable ? 'Read/write OK' : 'Read/write issue';
        } catch (Throwable) {
            $detail = 'Unavailable';
        }

        return [
            'default_disk' => $defaultDisk,
            'disks' => array_keys((array) config('filesystems.disks', [])),
            'status' => $writable ? 'healthy' : 'degraded',
            'detail' => $detail,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function redisStatus(): array
    {
        $connected = false;
        $detail = 'Not configured';

        try {
            if (class_exists(Redis::class)) {
                Redis::connection()->ping();
                $connected = true;
                $detail = 'Connected';
            }
        } catch (Throwable $exception) {
            $detail = $exception->getMessage();
        }

        return [
            'client' => (string) config('database.redis.client', 'phpredis'),
            'host' => (string) config('database.redis.default.host', '127.0.0.1'),
            'port' => (string) config('database.redis.default.port', '6379'),
            'cache_driver' => (string) config('cache.default'),
            'status' => $connected ? 'healthy' : 'degraded',
            'detail' => $detail,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function queueStatus(): array
    {
        $pending = 0;
        $failed = 0;

        if (Schema::hasTable('jobs')) {
            $pending = (int) DB::table('jobs')->count();
        }

        if (Schema::hasTable('failed_jobs')) {
            $failed = (int) DB::table('failed_jobs')->count();
        }

        $driver = (string) config('queue.default');

        return [
            'driver' => $driver,
            'connection' => (string) config("queue.connections.{$driver}.connection", $driver),
            'pending_jobs' => $pending,
            'failed_jobs' => $failed,
            'horizon_enabled' => class_exists(\Laravel\Horizon\Horizon::class),
            'status' => $driver === 'sync' ? 'degraded' : 'healthy',
            'detail' => strtoupper($driver).' driver',
        ];
    }

    public function seedDefaults(): void
    {
        foreach (SettingsGroup::cases() as $group) {
            $defaults = SettingsSchema::defaults($group);

            foreach ($defaults as $key => $value) {
                Setting::query()->firstOrCreate(
                    ['group' => $group->value, 'key' => $key],
                    [
                        'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                        'is_encrypted' => in_array($key, SettingsSchema::encryptedKeys($group), true),
                    ]
                );
            }
        }

        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadGroup(SettingsGroup $group): array
    {
        $defaults = SettingsSchema::defaults($group);
        $stored = Setting::query()
            ->where('group', $group->value)
            ->get()
            ->keyBy('key');

        $values = [];

        foreach ($defaults as $key => $default) {
            $record = $stored->get($key);

            if ($record === null) {
                $values[$key] = $default;

                continue;
            }

            $raw = $record->value ?? '';

            if ($record->is_encrypted && $raw !== '') {
                try {
                    $raw = Crypt::decryptString($raw);
                } catch (Throwable) {
                    $raw = '';
                }
            }

            $values[$key] = $this->castValue($key, $raw, $default);
        }

        return $values;
    }

    private function castValue(string $key, string $raw, mixed $default): mixed
    {
        if (is_bool($default)) {
            return in_array($raw, ['1', 'true', 'yes', 'on'], true);
        }

        if (is_int($default)) {
            return (int) $raw;
        }

        if (is_float($default)) {
            return (float) $raw;
        }

        return $raw;
    }

    private function flushCache(SettingsGroup $group): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget(self::CACHE_KEY.'.'.$group->value);
    }
}
