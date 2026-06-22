<?php

declare(strict_types=1);

namespace App\Services\Enrollment;

use App\Enums\Enrollment\CourseEnrollmentStatus;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\StudentPackage;
use App\Models\User;
use App\Services\Service;
use Illuminate\Database\Eloquent\Collection;

class CourseEnrollmentService extends Service
{
    public function enrollInCourse(
        User $user,
        Course $course,
        ?StudentPackage $studentPackage = null,
    ): CourseEnrollment {
        $enrollment = CourseEnrollment::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $course->id,
                'student_package_id' => $studentPackage?->id,
            ],
            [
                'status' => CourseEnrollmentStatus::Active->value,
                'progress_percent' => 0,
                'enrolled_at' => now(),
                'completed_at' => null,
                'last_accessed_at' => now(),
            ]
        );

        return $enrollment->fresh(['course', 'studentPackage']);
    }

    public function canAccessCourse(User $user, Course $course): bool
    {
        return CourseEnrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->active()
            ->get()
            ->contains(fn (CourseEnrollment $enrollment): bool => $enrollment->isAccessible());
    }

    /**
     * @return Collection<int, CourseEnrollment>
     */
    public function accessibleEnrollments(User $user): Collection
    {
        return CourseEnrollment::query()
            ->with(['course.category', 'studentPackage.package'])
            ->where('user_id', $user->id)
            ->active()
            ->orderByDesc('last_accessed_at')
            ->get()
            ->filter(fn (CourseEnrollment $enrollment): bool => $enrollment->isAccessible())
            ->values();
    }

    public function markAccessed(CourseEnrollment $enrollment): CourseEnrollment
    {
        $enrollment->update(['last_accessed_at' => now()]);

        return $enrollment->fresh();
    }

    public function syncProgress(CourseEnrollment $enrollment, int $progressPercent): CourseEnrollment
    {
        $progressPercent = max(0, min(100, $progressPercent));

        $enrollment->update([
            'progress_percent' => $progressPercent,
            'status' => $progressPercent >= 100
                ? CourseEnrollmentStatus::Completed->value
                : CourseEnrollmentStatus::Active->value,
            'completed_at' => $progressPercent >= 100 ? now() : null,
        ]);

        return $enrollment->fresh();
    }
}
