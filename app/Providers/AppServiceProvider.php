<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\Auth\Permission as PermissionEnum;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseSection;
use App\Models\ExamTest;
use App\Models\Lesson;
use App\Models\LessonResource;
use App\Models\Listening\ListeningTest;
use App\Models\Package;
use App\Models\Permission;
use App\Models\QuestionBank;
use App\Models\ReadingAttempt;
use App\Models\ReadingTest;
use App\Models\Role;
use App\Models\User;
use App\Policies\CoursePolicy;
use App\Policies\ExamPolicy;
use App\Policies\ListeningTestPolicy;
use App\Policies\PackagePolicy;
use App\Policies\PermissionPolicy;
use App\Policies\QuestionBankPolicy;
use App\Policies\ReadingAttemptPolicy;
use App\Policies\RolePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    public function boot(): void
    {
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);

        $coursePolicy = CoursePolicy::class;
        Gate::policy(CourseCategory::class, $coursePolicy);
        Gate::policy(Course::class, $coursePolicy);
        Gate::policy(CourseSection::class, $coursePolicy);
        Gate::policy(Lesson::class, $coursePolicy);
        Gate::policy(LessonResource::class, $coursePolicy);
        Gate::policy(Package::class, PackagePolicy::class);
        Gate::policy(ExamTest::class, ExamPolicy::class);
        Gate::policy(ReadingTest::class, ExamPolicy::class);
        Gate::policy(ListeningTest::class, ListeningTestPolicy::class);
        Gate::policy(ReadingAttempt::class, ReadingAttemptPolicy::class);
        Gate::policy(QuestionBank::class, QuestionBankPolicy::class);

        RateLimiter::for('reading-autosave', function (Request $request): Limit {
            $attempt = $request->route('attempt');
            $attemptKey = $attempt instanceof ReadingAttempt ? $attempt->id : 'guest';

            return Limit::perMinute(120)->by($request->user()?->id.'|'.$attemptKey);
        });

        RateLimiter::for('reading-timer', function (Request $request): Limit {
            $attempt = $request->route('attempt');
            $attemptKey = $attempt instanceof ReadingAttempt ? $attempt->id : 'guest';

            return Limit::perMinute(30)->by($request->user()?->id.'|'.$attemptKey);
        });

        RateLimiter::for('reading-submit', function (Request $request): Limit {
            $attempt = $request->route('attempt');
            $attemptKey = $attempt instanceof ReadingAttempt ? $attempt->id : 'guest';

            return Limit::perMinute(5)->by($request->user()?->id.'|'.$attemptKey);
        });

        foreach (PermissionEnum::cases() as $permission) {
            Gate::define($permission->value, fn (User $user): bool => $user->hasPermission($permission));
        }
    }
}
