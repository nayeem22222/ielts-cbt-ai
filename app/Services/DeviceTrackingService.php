<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use App\Support\UserAgentParser;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceTrackingService extends Service
{
    public function track(User $user, Request $request): UserDevice
    {
        $deviceUuid = $request->session()->get('device_uuid');

        if (! is_string($deviceUuid) || $deviceUuid === '') {
            $deviceUuid = (string) Str::uuid();
            $request->session()->put('device_uuid', $deviceUuid);
        }

        $parsed = UserAgentParser::parse($request->userAgent());
        $sessionId = $request->session()->getId();
        $ipAddress = $request->ip() ?? '0.0.0.0';

        return UserDevice::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'device_uuid' => $deviceUuid,
            ],
            [
                'device_name' => $parsed['browser'].' on '.$parsed['os'],
                'browser' => $parsed['browser'],
                'os' => $parsed['os'],
                'platform' => $parsed['platform'],
                'user_agent' => $request->userAgent(),
                'session_id' => $sessionId,
                'ip_address' => $ipAddress,
                'last_used_at' => now(),
            ]
        );
    }

    public function clearSession(string $sessionId): void
    {
        UserDevice::query()
            ->where('session_id', $sessionId)
            ->update(['session_id' => null]);
    }

    public function clearSessionsForUserExcept(int $userId, string $currentSessionId): void
    {
        UserDevice::query()
            ->where('user_id', $userId)
            ->where('session_id', '!=', $currentSessionId)
            ->update(['session_id' => null]);
    }
}
