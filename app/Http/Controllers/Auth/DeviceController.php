<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use App\Services\DeviceTrackingService;
use App\Services\SessionManagementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function __construct(
        private readonly DeviceTrackingService $devices,
        private readonly SessionManagementService $sessions,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();
        $currentSessionId = $request->session()->getId();

        $devices = $user->devices()
            ->orderByDesc('last_used_at')
            ->get()
            ->map(function (UserDevice $device) use ($currentSessionId) {
                return [
                    'model' => $device,
                    'is_current' => $device->session_id === $currentSessionId,
                    'is_active' => $device->session_id !== null,
                ];
            });

        return view('pages.auth.devices', [
            'devices' => $devices,
            'trustedDevices' => $devices->filter(fn (array $device): bool => $device['model']->is_trusted),
            'sessions' => $this->sessions->activeSessionsFor($user, $currentSessionId),
        ]);
    }

    public function trust(Request $request, UserDevice $device): RedirectResponse
    {
        $this->authorizeDevice($request, $device);

        $device->markTrusted();

        return back()->with('status', 'Device marked as trusted.');
    }

    public function untrust(Request $request, UserDevice $device): RedirectResponse
    {
        $this->authorizeDevice($request, $device);

        $device->markUntrusted();

        return back()->with('status', 'Device removed from trusted devices.');
    }

    public function destroyOthers(Request $request): RedirectResponse
    {
        $this->sessions->destroyOtherSessions(
            $request->user(),
            $request->session()->getId()
        );

        return back()->with('status', 'All other sessions have been logged out.');
    }

    public function destroySession(Request $request, string $session): RedirectResponse
    {
        $endedCurrent = $session === $request->session()->getId();

        $deleted = $this->sessions->destroySession($request, $request->user(), $session);

        if (! $deleted) {
            abort(404);
        }

        if ($endedCurrent) {
            return redirect()->route('login')->with('status', 'Your session has been ended.');
        }

        return back()->with('status', 'The selected session has been logged out.');
    }

    private function authorizeDevice(Request $request, UserDevice $device): void
    {
        if ((int) $device->user_id !== (int) $request->user()->id) {
            abort(403);
        }
    }
}
