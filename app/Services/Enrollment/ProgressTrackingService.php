<?php

declare(strict_types=1);

namespace App\Services\Enrollment;

use App\Enums\Enrollment\CourseEnrollmentStatus;
use App\Enums\Enrollment\LessonProgressStatus;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;
use App\Services\Service;

class ProgressTrackingService extends Service
{
    public function __construct(
        private readonly CourseEnrollmentService $courseEnrollments,
    ) {
    }

    public function startLesson(User $user, Lesson $lesson): LessonProgress
    {
        $lesson->loadMissing('section.course');
        $course = $lesson->section->course;

        $enrollment = $this->resolveEnrollment($user, $course);

        return LessonProgress::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'lesson_id' => $lesson->id,
            ],
            [
                'course_id' => $course->id,
                'course_enrollment_id' => $enrollment?->id,
                'status' => LessonProgressStatus::InProgress->value,
                'started_at' => now(),
            ]
        );
    }

    public function updateProgress(
        User $user,
        Lesson $lesson,
        int $progressPercent,
        ?int $lastPositionSeconds = null,
        int $additionalTimeSpent = 0,
    ): LessonProgress {
        $lesson->loadMissing('section.course');
        $progress = $this->startLesson($user, $lesson);

        $progressPercent = max(0, min(100, $progressPercent));
        $status = $progressPercent >= 100
            ? LessonProgressStatus::Completed
            : LessonProgressStatus::InProgress;

        $attributes = [
            'progress_percent' => $progressPercent,
            'status' => $status->value,
            'time_spent_seconds' => $progress->time_spent_seconds + max(0, $additionalTimeSpent),
            'completed_at' => $progressPercent >= 100 ? now() : null,
        ];

        if ($lastPositionSeconds !== null) {
            $attributes['last_position_seconds'] = $lastPositionSeconds;
        }

        $progress->update($attributes);

        $this->syncCourseEnrollmentProgress($user, $lesson->section->course);

        return $progress->fresh(['lesson', 'course']);
    }

    public function completeLesson(User $user, Lesson $lesson): LessonProgress
    {
        return $this->updateProgress($user, $lesson, 100);
    }

    public function courseProgressPercent(User $user, Course $course): int
    {
        $totalLessons = $course->lessons()->count();

        if ($totalLessons === 0) {
            return 0;
        }

        $completedLessons = LessonProgress::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', LessonProgressStatus::Completed->value)
            ->count();

        return (int) round(($completedLessons / $totalLessons) * 100);
    }

    public function syncCourseEnrollmentProgress(User $user, Course $course): ?CourseEnrollment
    {
        $enrollment = CourseEnrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->active()
            ->first();

        if ($enrollment === null) {
            return null;
        }

        return $this->courseEnrollments->syncProgress(
            $enrollment,
            $this->courseProgressPercent($user, $course)
        );
    }

    private function resolveEnrollment(User $user, Course $course): ?CourseEnrollment
    {
        return CourseEnrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->active()
            ->first();
    }
}
