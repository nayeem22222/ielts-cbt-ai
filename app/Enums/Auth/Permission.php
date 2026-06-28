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

    case SettingsView = 'settings.view';
    case SettingsUpdate = 'settings.update';

    case CoursesView = 'courses.view';
    case CoursesCreate = 'courses.create';
    case CoursesUpdate = 'courses.update';
    case CoursesDelete = 'courses.delete';

    case PackagesView = 'packages.view';
    case PackagesCreate = 'packages.create';
    case PackagesUpdate = 'packages.update';
    case PackagesDelete = 'packages.delete';

    case EnrollmentsView = 'enrollments.view';
    case EnrollmentsCreate = 'enrollments.create';
    case EnrollmentsUpdate = 'enrollments.update';
    case EnrollmentsDelete = 'enrollments.delete';

    case TestsView = 'tests.view';
    case TestsCreate = 'tests.create';
    case TestsUpdate = 'tests.update';
    case TestsDelete = 'tests.delete';

    case ListeningTestsView = 'listening.tests.view';
    case ListeningTestsCreate = 'listening.tests.create';
    case ListeningTestsUpdate = 'listening.tests.update';
    case ListeningTestsDelete = 'listening.tests.delete';
    case ListeningTestsPublish = 'listening.tests.publish';
    case ListeningTestsArchive = 'listening.tests.archive';
    case ListeningTestsDuplicate = 'listening.tests.duplicate';

    case ListeningSectionsView = 'listening.sections.view';
    case ListeningSectionsCreate = 'listening.sections.create';
    case ListeningSectionsUpdate = 'listening.sections.update';
    case ListeningSectionsDelete = 'listening.sections.delete';
    case ListeningSectionsRestore = 'listening.sections.restore';
    case ListeningSectionsReorder = 'listening.sections.reorder';

    case ListeningTranscriptsView = 'listening.transcripts.view';
    case ListeningTranscriptsCreate = 'listening.transcripts.create';
    case ListeningTranscriptsUpdate = 'listening.transcripts.update';
    case ListeningTranscriptsDelete = 'listening.transcripts.delete';
    case ListeningTranscriptsAttach = 'listening.transcripts.attach';
    case ListeningTranscriptsTimestampsUpdate = 'listening.transcripts.timestamps.update';

    case ListeningQuestionGroupsView = 'listening.question_groups.view';
    case ListeningQuestionGroupsCreate = 'listening.question_groups.create';
    case ListeningQuestionGroupsUpdate = 'listening.question_groups.update';
    case ListeningQuestionGroupsDelete = 'listening.question_groups.delete';

    case ListeningQuestionsView = 'listening.questions.view';
    case ListeningQuestionsCreate = 'listening.questions.create';
    case ListeningQuestionsUpdate = 'listening.questions.update';
    case ListeningQuestionsDelete = 'listening.questions.delete';
    case ListeningQuestionsBulkCreate = 'listening.questions.bulk_create';
    case ListeningQuestionsReorder = 'listening.questions.reorder';

    case QuestionBanksView = 'question_banks.view';
    case QuestionBanksCreate = 'question_banks.create';
    case QuestionBanksUpdate = 'question_banks.update';
    case QuestionBanksDelete = 'question_banks.delete';

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
            self::SettingsView => 'View settings',
            self::SettingsUpdate => 'Update settings',
            self::CoursesView => 'View courses',
            self::CoursesCreate => 'Create courses',
            self::CoursesUpdate => 'Update courses',
            self::CoursesDelete => 'Delete courses',
            self::PackagesView => 'View packages',
            self::PackagesCreate => 'Create packages',
            self::PackagesUpdate => 'Update packages',
            self::PackagesDelete => 'Delete packages',
            self::EnrollmentsView => 'View enrollments',
            self::EnrollmentsCreate => 'Create enrollments',
            self::EnrollmentsUpdate => 'Update enrollments',
            self::EnrollmentsDelete => 'Delete enrollments',
            self::TestsView => 'View reading tests',
            self::TestsCreate => 'Create reading tests',
            self::TestsUpdate => 'Update reading tests',
            self::TestsDelete => 'Delete reading tests',
            self::ListeningTestsView => 'View listening tests',
            self::ListeningTestsCreate => 'Create listening tests',
            self::ListeningTestsUpdate => 'Update listening tests',
            self::ListeningTestsDelete => 'Delete listening tests',
            self::ListeningTestsPublish => 'Publish listening tests',
            self::ListeningTestsArchive => 'Archive listening tests',
            self::ListeningTestsDuplicate => 'Duplicate listening tests',
            self::ListeningSectionsView => 'View listening sections',
            self::ListeningSectionsCreate => 'Create listening sections',
            self::ListeningSectionsUpdate => 'Update listening sections',
            self::ListeningSectionsDelete => 'Delete listening sections',
            self::ListeningSectionsRestore => 'Restore listening sections',
            self::ListeningSectionsReorder => 'Reorder listening sections',

            self::ListeningTranscriptsView => 'View listening transcripts',
            self::ListeningTranscriptsCreate => 'Create listening transcripts',
            self::ListeningTranscriptsUpdate => 'Update listening transcripts',
            self::ListeningTranscriptsDelete => 'Delete listening transcripts',
            self::ListeningTranscriptsAttach => 'Attach listening transcripts',
            self::ListeningTranscriptsTimestampsUpdate => 'Update listening transcript timestamps',

            self::ListeningQuestionGroupsView => 'View listening question groups',
            self::ListeningQuestionGroupsCreate => 'Create listening question groups',
            self::ListeningQuestionGroupsUpdate => 'Update listening question groups',
            self::ListeningQuestionGroupsDelete => 'Delete listening question groups',

            self::ListeningQuestionsView => 'View listening questions',
            self::ListeningQuestionsCreate => 'Create listening questions',
            self::ListeningQuestionsUpdate => 'Update listening questions',
            self::ListeningQuestionsDelete => 'Delete listening questions',
            self::ListeningQuestionsBulkCreate => 'Bulk create listening questions',
            self::ListeningQuestionsReorder => 'Reorder listening questions',

            self::QuestionBanksView => 'View question banks',
            self::QuestionBanksCreate => 'Create question banks',
            self::QuestionBanksUpdate => 'Update question banks',
            self::QuestionBanksDelete => 'Delete question banks',
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

            self::SettingsView,
            self::SettingsUpdate => 'settings',

            self::CoursesView,
            self::CoursesCreate,
            self::CoursesUpdate,
            self::CoursesDelete => 'courses',

            self::PackagesView,
            self::PackagesCreate,
            self::PackagesUpdate,
            self::PackagesDelete => 'packages',

            self::EnrollmentsView,
            self::EnrollmentsCreate,
            self::EnrollmentsUpdate,
            self::EnrollmentsDelete => 'enrollments',

            self::TestsView,
            self::TestsCreate,
            self::TestsUpdate,
            self::TestsDelete => 'tests',

            self::ListeningTestsView,
            self::ListeningTestsCreate,
            self::ListeningTestsUpdate,
            self::ListeningTestsDelete,
            self::ListeningTestsPublish,
            self::ListeningTestsArchive,
            self::ListeningTestsDuplicate => 'listening_tests',

            self::ListeningSectionsView,
            self::ListeningSectionsCreate,
            self::ListeningSectionsUpdate,
            self::ListeningSectionsDelete,
            self::ListeningSectionsRestore,
            self::ListeningSectionsReorder => 'listening_sections',

            self::ListeningTranscriptsView,
            self::ListeningTranscriptsCreate,
            self::ListeningTranscriptsUpdate,
            self::ListeningTranscriptsDelete,
            self::ListeningTranscriptsAttach,
            self::ListeningTranscriptsTimestampsUpdate => 'listening_transcripts',

            self::ListeningQuestionGroupsView,
            self::ListeningQuestionGroupsCreate,
            self::ListeningQuestionGroupsUpdate,
            self::ListeningQuestionGroupsDelete => 'listening_question_groups',

            self::ListeningQuestionsView,
            self::ListeningQuestionsCreate,
            self::ListeningQuestionsUpdate,
            self::ListeningQuestionsDelete,
            self::ListeningQuestionsBulkCreate,
            self::ListeningQuestionsReorder => 'listening_questions',

            self::QuestionBanksView,
            self::QuestionBanksCreate,
            self::QuestionBanksUpdate,
            self::QuestionBanksDelete => 'question_banks',
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
                self::SettingsView,
                self::SettingsUpdate,
                self::CoursesView,
                self::CoursesCreate,
                self::CoursesUpdate,
                self::CoursesDelete,
                self::PackagesView,
                self::PackagesCreate,
                self::PackagesUpdate,
                self::PackagesDelete,
                self::EnrollmentsView,
                self::EnrollmentsCreate,
                self::EnrollmentsUpdate,
                self::EnrollmentsDelete,
                self::TestsView,
                self::TestsCreate,
                self::TestsUpdate,
                self::TestsDelete,
                self::ListeningTestsView,
                self::ListeningTestsCreate,
                self::ListeningTestsUpdate,
                self::ListeningTestsDelete,
                self::ListeningTestsPublish,
                self::ListeningTestsArchive,
                self::ListeningTestsDuplicate,
                self::ListeningSectionsView,
                self::ListeningSectionsCreate,
                self::ListeningSectionsUpdate,
                self::ListeningSectionsDelete,
                self::ListeningSectionsRestore,
                self::ListeningSectionsReorder,
                self::ListeningTranscriptsView,
                self::ListeningTranscriptsCreate,
                self::ListeningTranscriptsUpdate,
                self::ListeningTranscriptsDelete,
                self::ListeningTranscriptsAttach,
                self::ListeningTranscriptsTimestampsUpdate,
                self::ListeningQuestionGroupsView,
                self::ListeningQuestionGroupsCreate,
                self::ListeningQuestionGroupsUpdate,
                self::ListeningQuestionGroupsDelete,
                self::ListeningQuestionsView,
                self::ListeningQuestionsCreate,
                self::ListeningQuestionsUpdate,
                self::ListeningQuestionsDelete,
                self::ListeningQuestionsBulkCreate,
                self::ListeningQuestionsReorder,
                self::QuestionBanksView,
                self::QuestionBanksCreate,
                self::QuestionBanksUpdate,
                self::QuestionBanksDelete,
            ],
            UserRole::SuperAdmin => self::cases(),
        };
    }
}
