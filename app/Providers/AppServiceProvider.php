<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\Auth\Permission as PermissionEnum;
use App\Models\Course;
use App\Models\CourseCategory;
use App\Models\CourseSection;
use App\Models\Lesson;
use App\Models\LessonResource;
use App\Models\Package;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Role::class, \App\Policies\RolePolicy::class);
        Gate::policy(\App\Models\Permission::class, \App\Policies\PermissionPolicy::class);

        $coursePolicy = \App\Policies\CoursePolicy::class;
        Gate::policy(CourseCategory::class, $coursePolicy);
        Gate::policy(Course::class, $coursePolicy);
        Gate::policy(CourseSection::class, $coursePolicy);
        Gate::policy(Lesson::class, $coursePolicy);
        Gate::policy(LessonResource::class, $coursePolicy);
        Gate::policy(Package::class, \App\Policies\PackagePolicy::class);

        foreach (PermissionEnum::cases() as $permission) {
            Gate::define($permission->value, fn (User $user): bool => $user->hasPermission($permission));
        }
    }
}
