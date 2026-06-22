<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Auth\UserRole;
use App\Enums\Auth\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var User|null $target */
        $target = $this->route('user');

        return $target !== null && ($this->user()?->can('update', $target) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');
        $assignableRoles = $this->assignableRoleValues($target);

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'string', Rule::in($assignableRoles)],
            'status' => ['required', 'string', Rule::in(UserStatus::values())],
            'email_verified' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return list<string>
     */
    private function assignableRoleValues(User $target): array
    {
        $roles = UserRole::assignableByAdmin();

        if ($this->user()?->hasRole(UserRole::SuperAdmin)) {
            $roles = UserRole::adminAssignable();
        }

        if ($target->hasRole(UserRole::SuperAdmin) && ! $this->user()?->hasRole(UserRole::SuperAdmin)) {
            return [UserRole::SuperAdmin->value];
        }

        return array_map(static fn (UserRole $role): string => $role->value, $roles);
    }
}
