<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Events\Auth\UserLoggedIn;
use App\Events\Auth\UserLoggedOut;
use App\Models\User;
use App\Services\DeviceTrackingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly DeviceTrackingService $devices)
    {
    }
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        /** @var User $user */
        $user = Auth::user();

        if (! $user->isActive()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'email' => 'Your account is inactive. Please contact support.',
            ]);
        }

        $request->session()->regenerate();
        $user->recordLogin($request->ip());
        $this->devices->track($user, $request);

        event(new UserLoggedIn(
            user: $user,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        ));

        return redirect()->intended($user->dashboardPath())
            ->with('status', 'Welcome back, '.$user->name.'.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof User) {
            event(new UserLoggedOut(
                user: $user,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            ));
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'You have been logged out.');
    }
}
