<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\Auth\UserRole;
use App\Enums\Auth\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
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
        $user->recordLogin($request->ip());

        return redirect()->route('student.dashboard')
            ->with('status', 'Your student account has been created.');
    }
}
