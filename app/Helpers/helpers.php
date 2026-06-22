<?php

declare(strict_types=1);

/**
 * Global helper functions for cross-cutting, stateless utilities.
 *
 * Keep helpers pure and side-effect free. Domain-specific logic belongs in Services.
 */

if (! function_exists('setting')) {
    function setting(string $group, string $key, mixed $default = null): mixed
    {
        return app(\App\Services\Admin\SettingsService::class)->get($group, $key, $default);
    }
}
