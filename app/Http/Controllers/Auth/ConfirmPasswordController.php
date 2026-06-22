<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ConfirmPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ConfirmPasswordController extends Controller
{
    public function create(): View
    {
        return view('pages.auth.confirm-password');
    }

    public function store(ConfirmPasswordRequest $request): RedirectResponse
    {
        if (! Auth::guard('web')->validate([
            'email' => $request->user()->email,
            'password' => $request->string('password')->toString(),
        ])) {
            return back()->withErrors([
                'password' => 'The password is incorrect.',
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended($request->user()->dashboardPath());
    }
}
