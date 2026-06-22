<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Course;
use App\Services\Enrollment\CourseEnrollmentService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCourseAccess
{
    public function __construct(private readonly CourseEnrollmentService $courseEnrollments)
    {
    }

    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        $course = $request->route('course');

        if (! $course instanceof Course) {
            abort(404);
        }

        if (! $this->courseEnrollments->canAccessCourse($user, $course)) {
            abort(403, 'You are not enrolled in this course.');
        }

        return $next($request);
    }
}
