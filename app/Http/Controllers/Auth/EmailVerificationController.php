<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): View|RedirectResponse
    {
        $user = $request->user()?->fresh();

        if ($user?->hasVerifiedEmail()) {
            return redirect()->intended($user->dashboardPath());
        }

        return view('pages.auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended($request->user()->dashboardPath());
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->intended($request->user()->dashboardPath())
            ->with('status', 'Your email address has been verified.');
    }

    public function send(Request $request): RedirectResponse
    {
        $user = $request->user()->fresh();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($user->dashboardPath());
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'A new verification link has been sent to your email.');
    }
}
