<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateLessonProgressRequest;
use App\Models\Lesson;
use App\Services\Enrollment\CourseEnrollmentService;
use App\Services\Enrollment\ProgressTrackingService;
use Illuminate\Http\JsonResponse;

class LessonProgressController extends Controller
{
    public function __construct(
        private readonly ProgressTrackingService $progress,
        private readonly CourseEnrollmentService $courseEnrollments,
    ) {
    }

    public function update(UpdateLessonProgressRequest $request, Lesson $lesson): JsonResponse
    {
        $lesson->loadMissing('section.course');

        abort_unless(
            $this->courseEnrollments->canAccessCourse($request->user(), $lesson->section->course),
            403
        );

        $progress = $this->progress->updateProgress(
            $request->user(),
            $lesson,
            (int) $request->validated('progress_percent'),
            $request->validated('last_position_seconds') !== null
                ? (int) $request->validated('last_position_seconds')
                : null,
            (int) ($request->validated('time_spent_seconds') ?? 0),
        );

        return response()->json([
            'data' => [
                'lesson_id' => $progress->lesson_id,
                'status' => $progress->status->value,
                'progress_percent' => $progress->progress_percent,
                'course_progress_percent' => $this->progress->courseProgressPercent(
                    $request->user(),
                    $lesson->section->course
                ),
            ],
        ]);
    }
}
