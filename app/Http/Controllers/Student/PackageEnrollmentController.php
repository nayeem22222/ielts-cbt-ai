<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Services\Enrollment\EnrollmentService;
use App\Services\Enrollment\PackageAccessService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PackageEnrollmentController extends Controller
{
    public function __construct(
        private readonly EnrollmentService $enrollments,
        private readonly PackageAccessService $access,
    ) {
    }

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('pages.student.packages.index', [
            'activePackages' => $this->enrollments->activePackages($user),
            'availablePackages' => Package::query()
                ->where('is_public', true)
                ->where('is_active', true)
                ->where('status', 'active')
                ->orderBy('sort_order')
                ->get(),
            'accessibleModules' => $this->access->accessibleModules($user),
        ]);
    }

    public function store(Request $request, Package $package): RedirectResponse
    {
        abort_unless($package->is_public && $package->is_active, 404);

        $this->enrollments->enrollInPackage($request->user(), $package);

        return redirect()
            ->route('student.packages.index')
            ->with('status', 'Package activated successfully.');
    }
}
