<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AuthDemoController extends Controller
{
    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->session()->put('demo_student', [
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        return redirect()->route('student.dashboard')->with('status', 'Student account created for UI preview.');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $request->session()->put('demo_student', [
            'name' => 'Student',
            'email' => $data['email'],
        ]);

        return redirect()->route('student.dashboard')->with('status', 'Logged in for UI preview.');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('demo_student');
        return redirect()->route('home')->with('status', 'Logged out.');
    }
}
