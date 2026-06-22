<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class EnrollmentAccessDeniedException extends Exception
{
    public static function module(string $module): self
    {
        return new self("You do not have access to the {$module} module.");
    }

    public static function course(int $courseId): self
    {
        return new self("You are not enrolled in course #{$courseId}.");
    }

    public static function package(): self
    {
        return new self('You do not have an active package subscription.');
    }

    public static function attemptLimit(string $module): self
    {
        return new self("You have reached your {$module} attempt limit.");
    }
}
