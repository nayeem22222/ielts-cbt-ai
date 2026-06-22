<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\Auth\UserRole;
use App\Enums\Auth\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Events\Auth\UserRegistered;
use App\Models\User;
use App\Services\DeviceTrackingService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function __construct(private readonly DeviceTrackingService $devices)
    {
    }
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => $request->string('password')->toString(),
            'status' => UserStatus::Active->value,
        ]);

        $user->assignRole(UserRole::Student);
        $user->studentProfile()->create([]);

        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();
        $this->devices->track($user, $request);

        event(new UserRegistered(
            user: $user,
            ipAddress: $request->ip(),
            userAgent: $request->userAgent(),
        ));

        return redirect()->route('verification.notice')
            ->with('status', 'Your student account has been created. Please verify your email.');
    }
}
