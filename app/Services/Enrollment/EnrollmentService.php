<?php

declare(strict_types=1);

namespace App\Services\Enrollment;

use App\Enums\Enrollment\CourseEnrollmentStatus;
use App\Enums\Enrollment\StudentPackageStatus;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Package;
use App\Models\StudentPackage;
use App\Models\User;
use App\Services\Service;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class EnrollmentService extends Service
{
    public function __construct(
        private readonly CourseEnrollmentService $courseEnrollments,
    ) {
    }

    public function enrollInPackage(User $user, Package $package, ?int $orderId = null): StudentPackage
    {
        return DB::transaction(function () use ($user, $package, $orderId): StudentPackage {
            $studentPackage = StudentPackage::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'package_id' => $package->id,
                ],
                [
                    'order_id' => $orderId,
                    'status' => StudentPackageStatus::Pending->value,
                ]
            );

            return $this->activatePackage($studentPackage);
        });
    }

    public function activatePackage(StudentPackage $studentPackage): StudentPackage
    {
        $studentPackage->loadMissing('package');

        $package = $studentPackage->package;
        $startsAt = now();
        $expiresAt = $package->duration_days !== null
            ? $startsAt->copy()->addDays((int) $package->duration_days)
            : null;

        $studentPackage->update([
            'status' => StudentPackageStatus::Active->value,
            'starts_at' => $startsAt,
            'expires_at' => $expiresAt,
            'activated_at' => $startsAt,
            'cancelled_at' => null,
        ]);

        $this->provisionCourseEnrollments($studentPackage->fresh(['package.courses']));

        return $studentPackage->fresh(['package', 'courseEnrollments']);
    }

    public function cancelPackage(StudentPackage $studentPackage): StudentPackage
    {
        $studentPackage->update([
            'status' => StudentPackageStatus::Cancelled->value,
            'cancelled_at' => now(),
        ]);

        $this->expireLinkedCourseEnrollments($studentPackage, CourseEnrollmentStatus::Suspended);

        return $studentPackage->fresh();
    }

    public function expirePackage(StudentPackage $studentPackage): StudentPackage
    {
        $studentPackage->update([
            'status' => StudentPackageStatus::Expired->value,
        ]);

        $this->expireLinkedCourseEnrollments($studentPackage, CourseEnrollmentStatus::Expired);

        return $studentPackage->fresh();
    }

    public function refreshExpiredPackages(User $user): void
    {
        StudentPackage::query()
            ->where('user_id', $user->id)
            ->where('status', StudentPackageStatus::Active->value)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->each(fn (StudentPackage $studentPackage): StudentPackage => $this->expirePackage($studentPackage));
    }

    /**
     * @return Collection<int, StudentPackage>
     */
    public function activePackages(User $user): Collection
    {
        $this->refreshExpiredPackages($user);

        return StudentPackage::query()
            ->with('package')
            ->where('user_id', $user->id)
            ->active()
            ->orderByDesc('activated_at')
            ->get();
    }

    public function provisionCourseEnrollments(StudentPackage $studentPackage): void
    {
        $studentPackage->loadMissing('package.courses', 'user');

        foreach ($studentPackage->package->courses as $course) {
            $this->courseEnrollments->enrollInCourse(
                $studentPackage->user,
                $course,
                $studentPackage
            );
        }
    }

    private function expireLinkedCourseEnrollments(
        StudentPackage $studentPackage,
        CourseEnrollmentStatus $status,
    ): void {
        CourseEnrollment::query()
            ->where('student_package_id', $studentPackage->id)
            ->where('status', CourseEnrollmentStatus::Active->value)
            ->update([
                'status' => $status->value,
                'completed_at' => $status === CourseEnrollmentStatus::Expired ? now() : null,
            ]);
    }
}
