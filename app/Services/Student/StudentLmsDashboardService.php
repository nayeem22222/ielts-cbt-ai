<?php

declare(strict_types=1);

namespace App\Services\Student;

use App\Enums\Course\LessonContentType;
use App\Enums\Course\PublishStatus;
use App\Enums\Enrollment\CourseEnrollmentStatus;
use App\Enums\Enrollment\LessonProgressStatus;
use App\Models\CourseEnrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\LessonResource;
use App\Models\User;
use App\Services\Enrollment\CourseEnrollmentService;
use App\Services\Enrollment\EnrollmentService;
use App\Services\Enrollment\PackageAccessService;
use App\Services\Enrollment\ProgressTrackingService;
use App\Services\Service;
use Illuminate\Support\Collection;

class StudentLmsDashboardService extends Service
{
    public function __construct(
        private readonly CourseEnrollmentService $courseEnrollments,
        private readonly ProgressTrackingService $progress,
        private readonly EnrollmentService $enrollments,
        private readonly PackageAccessService $access,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(User $user): array
    {
        $enrollments = $this->courseEnrollments->accessibleEnrollments($user);
        $courseIds = $enrollments->pluck('course_id');

        return [
            'stats' => $this->stats($user, $enrollments),
            'continueLearning' => $this->continueLearning($user, $enrollments),
            'courseProgress' => $this->courseProgressItems($user, $enrollments),
            'liveClasses' => $this->upcomingLiveClasses($courseIds),
            'downloads' => $this->downloads($courseIds),
            'assignments' => $this->assignments($user, $courseIds),
            'certificates' => $this->certificates($user),
            'accessibleModules' => $this->access->accessibleModules($user),
            'hasEnrollment' => $enrollments->isNotEmpty(),
        ];
    }

    /**
     * @param  Collection<int, CourseEnrollment>  $enrollments
     * @return array<string, int|string>
     */
    private function stats(User $user, Collection $enrollments): array
    {
        $courseIds = $enrollments->pluck('course_id');
        $overallProgress = $enrollments->isEmpty()
            ? 0
            : (int) round($enrollments->avg(fn (CourseEnrollment $enrollment): int => $enrollment->progress_percent));

        return [
            'overallProgress' => $overallProgress,
            'activeCourses' => $enrollments->count(),
            'liveClasses' => $this->upcomingLiveClasses($courseIds)->count(),
            'downloads' => $this->downloads($courseIds)->count(),
            'assignments' => $this->assignments($user, $courseIds)->count(),
            'certificates' => $this->certificates($user)->count(),
            'activePackages' => $this->enrollments->activePackages($user)->count(),
        ];
    }

    /**
     * @param  Collection<int, CourseEnrollment>  $enrollments
     * @return array<string, mixed>|null
     */
    private function continueLearning(User $user, Collection $enrollments): ?array
    {
        $inProgress = LessonProgress::query()
            ->with(['lesson.section.course', 'course'])
            ->where('user_id', $user->id)
            ->where('status', LessonProgressStatus::InProgress->value)
            ->whereIn('course_id', $enrollments->pluck('course_id'))
            ->latest('updated_at')
            ->first();

        if ($inProgress !== null) {
            return $this->formatContinueItem(
                $inProgress->course,
                $inProgress->lesson,
                $inProgress->progress_percent,
            );
        }

        $enrollment = $enrollments->sortByDesc('last_accessed_at')->first();

        if ($enrollment === null) {
            return null;
        }

        $course = $enrollment->course;
        $course->loadMissing('sections.lessons');

        foreach ($course->sections as $section) {
            foreach ($section->lessons as $lesson) {
                $completed = LessonProgress::query()
                    ->where('user_id', $user->id)
                    ->where('lesson_id', $lesson->id)
                    ->where('status', LessonProgressStatus::Completed->value)
                    ->exists();

                if (! $completed) {
                    return $this->formatContinueItem(
                        $course,
                        $lesson,
                        $this->progress->courseProgressPercent($user, $course),
                    );
                }
            }
        }

        return $this->formatContinueItem(
            $course,
            $course->sections->first()?->lessons->first(),
            $enrollment->progress_percent,
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private function formatContinueItem($course, ?Lesson $lesson, int $progressPercent): ?array
    {
        if ($course === null || $lesson === null) {
            return null;
        }

        return [
            'course' => $course,
            'lesson' => $lesson,
            'progressPercent' => $progressPercent,
            'lessonProgressPercent' => LessonProgress::query()
                ->where('lesson_id', $lesson->id)
                ->value('progress_percent') ?? 0,
            'resumeUrl' => route('student.courses.show', $course),
        ];
    }

    /**
     * @param  Collection<int, CourseEnrollment>  $enrollments
     * @return Collection<int, array<string, mixed>>
     */
    private function courseProgressItems(User $user, Collection $enrollments): Collection
    {
        return $enrollments
            ->take(6)
            ->map(function (CourseEnrollment $enrollment) use ($user): array {
                $course = $enrollment->course;
                $progress = $this->progress->courseProgressPercent($user, $course);

                return [
                    'enrollment' => $enrollment,
                    'course' => $course,
                    'progressPercent' => $progress,
                    'url' => route('student.courses.show', $course),
                    'category' => $course->category?->name,
                    'thumbnail' => $course->thumbnail_path,
                ];
            });
    }

    /**
     * @param  Collection<int, int>  $courseIds
     * @return Collection<int, array<string, mixed>>
     */
    private function upcomingLiveClasses(Collection $courseIds): Collection
    {
        if ($courseIds->isEmpty()) {
            return collect();
        }

        return Lesson::query()
            ->with(['section.course'])
            ->where('content_type', LessonContentType::Live->value)
            ->where('status', PublishStatus::Published->value)
            ->whereHas('section', fn ($query) => $query->whereIn('course_id', $courseIds))
            ->orderBy('published_at')
            ->orderBy('sort_order')
            ->limit(5)
            ->get()
            ->values()
            ->map(function (Lesson $lesson, int $index): array {
                $scheduledAt = $lesson->published_at ?? now()->addDays($index + 1)->setTime(19, 0);

                return [
                    'lesson' => $lesson,
                    'course' => $lesson->section->course,
                    'scheduledAt' => $scheduledAt,
                    'url' => route('student.courses.show', $lesson->section->course),
                ];
            });
    }

    /**
     * @param  Collection<int, int>  $courseIds
     * @return Collection<int, LessonResource>
     */
    private function downloads(Collection $courseIds): Collection
    {
        if ($courseIds->isEmpty()) {
            return collect();
        }

        return LessonResource::query()
            ->with(['course', 'lesson'])
            ->whereIn('course_id', $courseIds)
            ->where('is_downloadable', true)
            ->orderByDesc('updated_at')
            ->limit(8)
            ->get();
    }

    /**
     * @param  Collection<int, int>  $courseIds
     * @return Collection<int, array<string, mixed>>
     */
    private function assignments(User $user, Collection $courseIds): Collection
    {
        if ($courseIds->isEmpty()) {
            return collect();
        }

        return Lesson::query()
            ->with(['section.course'])
            ->where('status', PublishStatus::Published->value)
            ->where(function ($query): void {
                $query->where('content_type', LessonContentType::Quiz->value)
                    ->orWhere('title', 'like', '%Assignment%');
            })
            ->whereHas('section', fn ($query) => $query->whereIn('course_id', $courseIds))
            ->orderBy('sort_order')
            ->limit(6)
            ->get()
            ->map(function (Lesson $lesson) use ($user): array {
                $progress = LessonProgress::query()
                    ->where('user_id', $user->id)
                    ->where('lesson_id', $lesson->id)
                    ->first();

                return [
                    'lesson' => $lesson,
                    'course' => $lesson->section->course,
                    'status' => $progress?->status?->label() ?? 'Not started',
                    'progressPercent' => $progress?->progress_percent ?? 0,
                    'url' => route('student.courses.show', $lesson->section->course),
                ];
            });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function certificates(User $user): Collection
    {
        return CourseEnrollment::query()
            ->with('course')
            ->where('user_id', $user->id)
            ->where(function ($query): void {
                $query->where('status', CourseEnrollmentStatus::Completed->value)
                    ->orWhere('progress_percent', '>=', 100);
            })
            ->orderByDesc('completed_at')
            ->get()
            ->map(function (CourseEnrollment $enrollment): array {
                return [
                    'enrollment' => $enrollment,
                    'course' => $enrollment->course,
                    'issuedAt' => $enrollment->completed_at ?? $enrollment->updated_at,
                    'url' => route('student.courses.show', $enrollment->course),
                ];
            })
            ->values();
    }
}
