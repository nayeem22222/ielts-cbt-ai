<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register repository interface bindings.
     *
     * Example:
     * $this->app->bind(ExamRepositoryInterface::class, EloquentExamRepository::class);
     */
    public function register(): void
    {
        //
    }
}
