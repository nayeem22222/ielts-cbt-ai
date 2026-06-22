<?php

declare(strict_types=1);

namespace App\Services\Enrollment;

use App\Enums\Commerce\IeltsModule;
use App\Exceptions\EnrollmentAccessDeniedException;
use App\Models\ModuleAttemptUsage;
use App\Models\StudentPackage;
use App\Models\User;
use App\Services\Service;
use Illuminate\Database\Eloquent\Collection;

class PackageAccessService extends Service
{
    public function __construct(
        private readonly EnrollmentService $enrollments,
    ) {
    }

    /**
     * @return Collection<int, StudentPackage>
     */
    public function activeStudentPackages(User $user): Collection
    {
        return $this->enrollments->activePackages($user);
    }

    public function canAccessModule(User $user, IeltsModule|string $module): bool
    {
        $module = $this->normalizeModule($module);

        if (! $this->hasModuleEntitlement($user, $module)) {
            return false;
        }

        $remaining = $this->remainingAttempts($user, $module);

        return $remaining === null || $remaining > 0;
    }

    public function remainingAttempts(User $user, IeltsModule|string $module): ?int
    {
        $module = $this->normalizeModule($module);
        $packages = $this->activeStudentPackages($user);

        if ($packages->isEmpty()) {
            return 0;
        }

        $bestRemaining = null;

        foreach ($packages as $studentPackage) {
            if (! $studentPackage->package->allowsModule($module)) {
                continue;
            }

            $limit = $studentPackage->package->attemptLimitFor($module);

            if ($limit === null) {
                return null;
            }

            $used = $this->usedAttempts($studentPackage, $module);
            $remaining = max(0, $limit - $used);
            $bestRemaining = $bestRemaining === null
                ? $remaining
                : max($bestRemaining, $remaining);
        }

        return $bestRemaining ?? 0;
    }

    /**
     * @return list<string>
     */
    public function accessibleModules(User $user): array
    {
        $modules = [];

        foreach (IeltsModule::cases() as $module) {
            if ($this->canAccessModule($user, $module)) {
                $modules[] = $module->value;
            }
        }

        return $modules;
    }

    public function recordModuleAttempt(User $user, IeltsModule|string $module): void
    {
        $module = $this->normalizeModule($module);

        if (! $this->canAccessModule($user, $module)) {
            throw EnrollmentAccessDeniedException::attemptLimit($module->value);
        }

        $studentPackage = $this->resolvePackageForAttempt($user, $module);

        if ($studentPackage === null) {
            throw EnrollmentAccessDeniedException::package();
        }

        $usage = ModuleAttemptUsage::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'student_package_id' => $studentPackage->id,
                'module' => $module->value,
            ],
            [
                'attempt_count' => 0,
            ]
        );

        $usage->increment('attempt_count');
    }

    public function usedAttempts(StudentPackage $studentPackage, IeltsModule|string $module): int
    {
        $module = $this->normalizeModule($module);

        return (int) ModuleAttemptUsage::query()
            ->where('user_id', $studentPackage->user_id)
            ->where('student_package_id', $studentPackage->id)
            ->where('module', $module->value)
            ->value('attempt_count');
    }

    private function hasModuleEntitlement(User $user, IeltsModule $module): bool
    {
        return $this->activeStudentPackages($user)
            ->contains(fn (StudentPackage $studentPackage): bool => $studentPackage->package->allowsModule($module));
    }

    private function resolvePackageForAttempt(User $user, IeltsModule $module): ?StudentPackage
    {
        $packages = $this->activeStudentPackages($user)
            ->filter(fn (StudentPackage $studentPackage): bool => $studentPackage->package->allowsModule($module));

        foreach ($packages as $studentPackage) {
            $limit = $studentPackage->package->attemptLimitFor($module);

            if ($limit === null) {
                return $studentPackage;
            }

            $used = $this->usedAttempts($studentPackage, $module);

            if ($used < $limit) {
                return $studentPackage;
            }
        }

        return null;
    }

    private function normalizeModule(IeltsModule|string $module): IeltsModule
    {
        return $module instanceof IeltsModule
            ? $module
            : IeltsModule::from($module);
    }
}
