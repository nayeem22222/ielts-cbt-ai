<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\Enrollment\CourseEnrollmentService;
use App\Services\Enrollment\ProgressTrackingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CourseEnrollmentController extends Controller
{
    public function __construct(
        private readonly CourseEnrollmentService $courseEnrollments,
        private readonly ProgressTrackingService $progress,
    ) {
    }

    public function index(Request $request): View
    {
        return view('pages.student.courses.index', [
            'enrollments' => $this->courseEnrollments->accessibleEnrollments($request->user()),
        ]);
    }

    public function show(Request $request, Course $course): View
    {
        $enrollment = $this->courseEnrollments->accessibleEnrollments($request->user())
            ->firstWhere('course_id', $course->id);

        abort_if($enrollment === null, 403);

        $this->courseEnrollments->markAccessed($enrollment);

        $course->load(['sections.lessons', 'category']);

        return view('pages.student.courses.show', [
            'course' => $course,
            'enrollment' => $enrollment,
            'progressPercent' => $this->progress->courseProgressPercent($request->user(), $course),
        ]);
    }
}
