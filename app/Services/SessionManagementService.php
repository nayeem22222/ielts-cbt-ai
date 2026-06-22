<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Support\UserAgentParser;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SessionManagementService extends Service
{
    public function __construct(private readonly DeviceTrackingService $devices)
    {
    }

    /**
     * @return Collection<int, array{
     *     id: string,
     *     ip_address: string|null,
     *     user_agent: string|null,
     *     browser: string,
     *     os: string,
     *     last_activity: Carbon,
     *     is_current: bool
     * }>
     */
    public function activeSessionsFor(User $user, string $currentSessionId): Collection
    {
        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function (object $session) use ($currentSessionId) {
                $parsed = UserAgentParser::parse($session->user_agent);

                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'browser' => $parsed['browser'],
                    'os' => $parsed['os'],
                    'last_activity' => Carbon::createFromTimestamp($session->last_activity),
                    'is_current' => $session->id === $currentSessionId,
                ];
            });
    }

    public function destroyOtherSessions(User $user, string $currentSessionId): int
    {
        $sessionIds = DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->pluck('id');

        foreach ($sessionIds as $sessionId) {
            $this->devices->clearSession($sessionId);
        }

        return DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $currentSessionId)
            ->delete();
    }

    public function destroySession(Request $request, User $user, string $sessionId): bool
    {
        $record = DB::table('sessions')
            ->where('id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if ($record === null) {
            return false;
        }

        if ($record->id === $request->session()->getId()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $this->devices->clearSession($sessionId);

        DB::table('sessions')->where('id', $sessionId)->delete();

        return true;
    }
}
