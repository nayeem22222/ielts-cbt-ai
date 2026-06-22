<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudentEnrollmentRequest;
use App\Models\Package;
use App\Models\StudentPackage;
use App\Models\User;
use App\Services\Enrollment\EnrollmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StudentEnrollmentController extends Controller
{
    public function __construct(private readonly EnrollmentService $enrollments)
    {
    }

    public function index(Request $request): View
    {
        $enrollments = StudentPackage::query()
            ->with(['user', 'package'])
            ->latest()
            ->paginate(20);

        return view('pages.admin.enrollments.index', [
            'enrollments' => $enrollments,
            'students' => User::query()
                ->whereHas('roles', fn ($query) => $query->where('slug', 'student'))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'packages' => Package::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'name', 'slug']),
        ]);
    }

    public function store(StoreStudentEnrollmentRequest $request): RedirectResponse
    {
        $student = User::query()->findOrFail((int) $request->validated('user_id'));
        $package = Package::query()->findOrFail((int) $request->validated('package_id'));

        $this->enrollments->enrollInPackage($student, $package);

        return redirect()
            ->route('admin.enrollments.index')
            ->with('status', 'Student enrolled and package activated.');
    }

    public function activate(StudentPackage $enrollment): RedirectResponse
    {
        $this->enrollments->activatePackage($enrollment);

        return back()->with('status', 'Package activated.');
    }

    public function cancel(StudentPackage $enrollment): RedirectResponse
    {
        $this->enrollments->cancelPackage($enrollment);

        return back()->with('status', 'Package cancelled.');
    }
}
