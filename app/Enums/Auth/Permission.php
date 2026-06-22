<?php

declare(strict_types=1);

namespace App\Enums\Auth;

use App\Enums\Concerns\EnumHelpers;

enum Permission: string
{
    use EnumHelpers;

    case AccessStudentDashboard = 'dashboard.student';
    case AccessTeacherDashboard = 'dashboard.teacher';
    case AccessAdminDashboard = 'dashboard.admin';

    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersDelete = 'users.delete';

    case RolesView = 'roles.view';
    case RolesAssign = 'roles.assign';
    case RolesManagePermissions = 'roles.manage_permissions';

    case PermissionsView = 'permissions.view';
    case PermissionsAssign = 'permissions.assign';

    public function label(): string
    {
        return match ($this) {
            self::AccessStudentDashboard => 'Access student dashboard',
            self::AccessTeacherDashboard => 'Access teacher dashboard',
            self::AccessAdminDashboard => 'Access admin dashboard',
            self::UsersView => 'View users',
            self::UsersCreate => 'Create users',
            self::UsersUpdate => 'Update users',
            self::UsersDelete => 'Delete users',
            self::RolesView => 'View roles',
            self::RolesAssign => 'Assign roles',
            self::RolesManagePermissions => 'Manage role permissions',
            self::PermissionsView => 'View permissions',
            self::PermissionsAssign => 'Assign user permissions',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::AccessStudentDashboard,
            self::AccessTeacherDashboard,
            self::AccessAdminDashboard => 'dashboard',

            self::UsersView,
            self::UsersCreate,
            self::UsersUpdate,
            self::UsersDelete => 'users',

            self::RolesView,
            self::RolesAssign,
            self::RolesManagePermissions => 'roles',

            self::PermissionsView,
            self::PermissionsAssign => 'permissions',
        };
    }

    /**
     * @return list<self>
     */
    public static function forRole(UserRole $role): array
    {
        return match ($role) {
            UserRole::Student => [
                self::AccessStudentDashboard,
            ],
            UserRole::Teacher => [
                self::AccessTeacherDashboard,
            ],
            UserRole::Admin => [
                self::AccessAdminDashboard,
                self::UsersView,
                self::UsersCreate,
                self::UsersUpdate,
                self::UsersDelete,
                self::RolesView,
                self::PermissionsView,
            ],
            UserRole::SuperAdmin => self::cases(),
        };
    }
}
