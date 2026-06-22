<?php

declare(strict_types=1);

namespace App\Support\Settings;

use App\Enums\Settings\SettingsGroup;

final class SettingsSchema
{
    /**
     * @return array<string, array{encrypted?: bool, rules: list<string|object>}>
     */
    public static function fields(SettingsGroup $group): array
    {
        return match ($group) {
            SettingsGroup::General => [
                'site_name' => ['rules' => ['required', 'string', 'max:120']],
                'site_tagline' => ['rules' => ['nullable', 'string', 'max:255']],
                'support_email' => ['rules' => ['required', 'email', 'max:255']],
                'default_locale' => ['rules' => ['required', 'string', 'max:10']],
                'timezone' => ['rules' => ['required', 'string', 'max:64']],
                'maintenance_mode' => ['rules' => ['nullable', 'boolean']],
                'maintenance_message' => ['rules' => ['nullable', 'string', 'max:500']],
            ],
            SettingsGroup::Brand => [
                'app_name' => ['rules' => ['required', 'string', 'max:120']],
                'logo_url' => ['rules' => ['nullable', 'string', 'max:500']],
                'favicon_url' => ['rules' => ['nullable', 'string', 'max:500']],
                'primary_color' => ['rules' => ['required', 'string', 'max:20']],
                'secondary_color' => ['rules' => ['required', 'string', 'max:20']],
                'footer_text' => ['rules' => ['nullable', 'string', 'max:500']],
            ],
            SettingsGroup::Ai => [
                'default_provider' => ['rules' => ['required', 'string', 'in:openai,anthropic,azure']],
                'api_key' => ['encrypted' => true, 'rules' => ['nullable', 'string', 'max:500']],
                'default_model' => ['rules' => ['required', 'string', 'max:120']],
                'max_tokens' => ['rules' => ['required', 'integer', 'min:256', 'max:128000']],
                'temperature' => ['rules' => ['required', 'numeric', 'min:0', 'max:2']],
                'evaluation_enabled' => ['rules' => ['nullable', 'boolean']],
                'request_timeout' => ['rules' => ['required', 'integer', 'min:5', 'max:300']],
            ],
            SettingsGroup::Payment => [
                'default_gateway' => ['rules' => ['required', 'string', 'in:stripe,sslcommerz,manual']],
                'currency' => ['rules' => ['required', 'string', 'max:3']],
                'sandbox_mode' => ['rules' => ['nullable', 'boolean']],
                'stripe_public_key' => ['rules' => ['nullable', 'string', 'max:255']],
                'stripe_secret_key' => ['encrypted' => true, 'rules' => ['nullable', 'string', 'max:500']],
                'webhook_secret' => ['encrypted' => true, 'rules' => ['nullable', 'string', 'max:500']],
                'invoice_prefix' => ['rules' => ['required', 'string', 'max:20']],
            ],
            SettingsGroup::Storage => [
                'default_disk' => ['rules' => ['required', 'string', 'in:local,public,s3']],
                'max_upload_mb' => ['rules' => ['required', 'integer', 'min:1', 'max:512']],
                'allowed_file_types' => ['rules' => ['required', 'string', 'max:255']],
                'public_uploads' => ['rules' => ['nullable', 'boolean']],
            ],
            SettingsGroup::Redis => [
                'use_for_cache' => ['rules' => ['nullable', 'boolean']],
                'use_for_queue' => ['rules' => ['nullable', 'boolean']],
                'use_for_session' => ['rules' => ['nullable', 'boolean']],
                'key_prefix' => ['rules' => ['nullable', 'string', 'max:64']],
            ],
            SettingsGroup::Queue => [
                'preferred_driver' => ['rules' => ['required', 'string', 'in:sync,database,redis']],
                'retry_after' => ['rules' => ['required', 'integer', 'min:30', 'max:3600']],
                'max_tries' => ['rules' => ['required', 'integer', 'min:1', 'max:10']],
                'failed_job_alerts' => ['rules' => ['nullable', 'boolean']],
            ],
            SettingsGroup::Security => [
                'force_email_verification' => ['rules' => ['nullable', 'boolean']],
                'session_lifetime' => ['rules' => ['required', 'integer', 'min:5', 'max:1440']],
                'password_min_length' => ['rules' => ['required', 'integer', 'min:8', 'max:128']],
                'max_login_attempts' => ['rules' => ['required', 'integer', 'min:3', 'max:20']],
                'lockout_minutes' => ['rules' => ['required', 'integer', 'min:1', 'max:120']],
                'require_2fa_admins' => ['rules' => ['nullable', 'boolean']],
                'ip_allowlist' => ['rules' => ['nullable', 'string', 'max:2000']],
            ],
            SettingsGroup::Backup => [
                'enabled' => ['rules' => ['nullable', 'boolean']],
                'schedule' => ['rules' => ['required', 'string', 'in:daily,weekly,monthly']],
                'retention_days' => ['rules' => ['required', 'integer', 'min:1', 'max:365']],
                'storage_disk' => ['rules' => ['required', 'string', 'in:local,public,s3']],
                'notify_email' => ['rules' => ['nullable', 'email', 'max:255']],
            ],
        };
    }

    /**
     * @return array<string, string|int|float|bool|null>
     */
    public static function defaults(SettingsGroup $group): array
    {
        return match ($group) {
            SettingsGroup::General => [
                'site_name' => 'Arif Academy IELTS CBT',
                'site_tagline' => 'AI-powered IELTS preparation platform',
                'support_email' => 'support@arifacademy.com',
                'default_locale' => 'en',
                'timezone' => 'Asia/Dhaka',
                'maintenance_mode' => false,
                'maintenance_message' => 'We are performing scheduled maintenance. Please check back soon.',
            ],
            SettingsGroup::Brand => [
                'app_name' => 'Arif Academy',
                'logo_url' => '',
                'favicon_url' => '',
                'primary_color' => '#2563eb',
                'secondary_color' => '#0f172a',
                'footer_text' => '© Arif Academy. All rights reserved.',
            ],
            SettingsGroup::Ai => [
                'default_provider' => 'openai',
                'api_key' => '',
                'default_model' => 'gpt-4o-mini',
                'max_tokens' => 4096,
                'temperature' => 0.7,
                'evaluation_enabled' => true,
                'request_timeout' => 60,
            ],
            SettingsGroup::Payment => [
                'default_gateway' => 'sslcommerz',
                'currency' => 'BDT',
                'sandbox_mode' => true,
                'stripe_public_key' => '',
                'stripe_secret_key' => '',
                'webhook_secret' => '',
                'invoice_prefix' => 'INV',
            ],
            SettingsGroup::Storage => [
                'default_disk' => 'local',
                'max_upload_mb' => 25,
                'allowed_file_types' => 'jpg,jpeg,png,pdf,doc,docx,mp3,wav',
                'public_uploads' => false,
            ],
            SettingsGroup::Redis => [
                'use_for_cache' => false,
                'use_for_queue' => false,
                'use_for_session' => false,
                'key_prefix' => 'ielts_cbt',
            ],
            SettingsGroup::Queue => [
                'preferred_driver' => 'database',
                'retry_after' => 90,
                'max_tries' => 3,
                'failed_job_alerts' => true,
            ],
            SettingsGroup::Security => [
                'force_email_verification' => true,
                'session_lifetime' => 120,
                'password_min_length' => 8,
                'max_login_attempts' => 5,
                'lockout_minutes' => 15,
                'require_2fa_admins' => false,
                'ip_allowlist' => '',
            ],
            SettingsGroup::Backup => [
                'enabled' => true,
                'schedule' => 'daily',
                'retention_days' => 14,
                'storage_disk' => 'local',
                'notify_email' => '',
            ],
        };
    }

    /**
     * @return list<string>
     */
    public static function encryptedKeys(SettingsGroup $group): array
    {
        $keys = [];

        foreach (self::fields($group) as $key => $meta) {
            if (($meta['encrypted'] ?? false) === true) {
                $keys[] = $key;
            }
        }

        return $keys;
    }
}
