<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Student\StudentLmsDashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StudentDashboardController extends Controller
{
    public function __construct(private readonly StudentLmsDashboardService $dashboard)
    {
    }

    public function index(Request $request): View
    {
        return view('pages.student.dashboard', $this->dashboard->build($request->user()));
    }
}
