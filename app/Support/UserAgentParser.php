<?php

declare(strict_types=1);

namespace App\Support;

final class UserAgentParser
{
    /**
     * @return array{browser: string, os: string, platform: string}
     */
    public static function parse(?string $userAgent): array
    {
        $browser = self::detectBrowser($userAgent);
        $os = self::detectOs($userAgent);

        return [
            'browser' => $browser,
            'os' => $os,
            'platform' => $os,
        ];
    }

    public static function detectBrowser(?string $userAgent): string
    {
        if ($userAgent === null || $userAgent === '') {
            return 'Unknown Browser';
        }

        if (str_contains($userAgent, 'Edg/')) {
            return 'Microsoft Edge';
        }

        if (str_contains($userAgent, 'Chrome/') || str_contains($userAgent, 'CriOS/')) {
            return 'Google Chrome';
        }

        if (str_contains($userAgent, 'Firefox/') || str_contains($userAgent, 'FxiOS/')) {
            return 'Mozilla Firefox';
        }

        if (str_contains($userAgent, 'Safari/') && ! str_contains($userAgent, 'Chrome')) {
            return 'Safari';
        }

        if (str_contains($userAgent, 'Opera') || str_contains($userAgent, 'OPR/')) {
            return 'Opera';
        }

        return 'Unknown Browser';
    }

    public static function detectOs(?string $userAgent): string
    {
        if ($userAgent === null || $userAgent === '') {
            return 'Unknown OS';
        }

        if (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            return 'iOS';
        }

        if (str_contains($userAgent, 'Android')) {
            return 'Android';
        }

        if (str_contains($userAgent, 'Windows')) {
            return 'Windows';
        }

        if (str_contains($userAgent, 'Mac OS X') || str_contains($userAgent, 'Macintosh')) {
            return 'macOS';
        }

        if (str_contains($userAgent, 'Linux')) {
            return 'Linux';
        }

        return 'Unknown OS';
    }
}
