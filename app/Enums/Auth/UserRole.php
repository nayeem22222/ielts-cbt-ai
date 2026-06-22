<?php

declare(strict_types=1);

namespace App\Enums\Auth;

use App\Enums\Concerns\EnumHelpers;

enum UserRole: string
{
    use EnumHelpers;

    case Student = 'student';
    case Teacher = 'teacher';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    public function label(): string
    {
        return match ($this) {
            self::Student => 'Student',
            self::Teacher => 'Teacher',
            self::Admin => 'Admin',
            self::SuperAdmin => 'Super Admin',
        };
    }

    public function dashboardRouteName(): string
    {
        return match ($this) {
            self::Student => 'student.dashboard',
            self::Teacher => 'teacher.dashboard',
            self::Admin, self::SuperAdmin => 'admin.dashboard',
        };
    }

    /**
     * @return list<self>
     */
    public static function adminAssignable(): array
    {
        return [self::Student, self::Teacher, self::Admin, self::SuperAdmin];
    }

    /**
     * @return list<self>
     */
    public static function assignableByAdmin(): array
    {
        return [self::Student, self::Teacher, self::Admin];
    }
}
