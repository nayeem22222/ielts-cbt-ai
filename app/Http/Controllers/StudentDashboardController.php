<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class StudentDashboardController extends Controller
{
    public function index(): View
    {
        return view('pages.student.dashboard');
    }
}
