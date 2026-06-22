<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Crud\CrudDefinition;
use App\Crud\CrudQuery;
use App\Enums\Auth\UserRole;
use App\Enums\Auth\UserStatus;
use App\Models\User;
use App\Services\Crud\AbstractCrudService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserCrudService extends AbstractCrudService
{
    protected function modelClass(): string
    {
        return User::class;
    }

    public function definition(): CrudDefinition
    {
        return new CrudDefinition(
            searchable: ['name', 'email', 'phone'],
            filters: [
                'status' => 'status',
                'role' => fn (Builder $query, mixed $value): mixed => $query->whereHas(
                    'roles',
                    fn (Builder $inner) => $inner->where('slug', (string) $value)
                ),
            ],
            sortable: ['id', 'name', 'email', 'status', 'created_at', 'last_login_at'],
            defaultSort: 'id',
            defaultDirection: 'desc',
            exportColumns: [
                'name' => 'Name',
                'email' => 'Email',
                'phone' => 'Phone',
                'status' => 'Status',
                'created_at' => 'Created At',
            ],
            importColumns: ['name', 'email', 'phone', 'status', 'role'],
            perPage: 15,
            softDeletes: true,
            relations: ['roles'],
        );
    }

    protected function customizeQuery(Builder $query, CrudQuery $crudQuery): void
    {
        if ($crudQuery->sort === 'role') {
            $query->orderByRaw('(select roles.slug from roles inner join role_user on roles.id = role_user.role_id where role_user.user_id = users.id limit 1) '.$crudQuery->direction);
        }
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function importRow(array $row): bool
    {
        $email = trim($row['email'] ?? '');

        if ($email === '') {
            return false;
        }

        if (User::query()->where('email', $email)->exists()) {
            return false;
        }

        $user = $this->create([
            'name' => trim($row['name'] ?? 'Imported User'),
            'email' => $email,
            'phone' => trim($row['phone'] ?? '') ?: null,
            'password' => 'password',
            'status' => in_array($row['status'] ?? '', array_column(UserStatus::cases(), 'value'), true)
                ? $row['status']
                : UserStatus::Active->value,
            'email_verified_at' => now(),
        ]);

        $role = UserRole::tryFrom((string) ($row['role'] ?? UserRole::Student->value)) ?? UserRole::Student;
        $user->assignRole($role);

        if ($user->hasRole(UserRole::Student)) {
            $user->studentProfile()->create([]);
        }

        return true;
    }

    protected function afterCreate(Model $model, array $attributes): void
    {
        if (! $model instanceof User) {
            return;
        }

        if (isset($attributes['role'])) {
            $model->assignRole((string) $attributes['role']);
        }

        if ($model->hasRole(UserRole::Student) && ! $model->studentProfile()->exists()) {
            $model->studentProfile()->create([]);
        }
    }
}
