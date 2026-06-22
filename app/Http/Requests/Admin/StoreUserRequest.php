<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Auth\UserRole;
use App\Enums\Auth\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\User::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $assignableRoles = $this->assignableRoleValues();

        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', 'string', Rule::in($assignableRoles)],
            'status' => ['required', 'string', Rule::in(UserStatus::values())],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * @return list<string>
     */
    private function assignableRoleValues(): array
    {
        $roles = UserRole::assignableByAdmin();

        if ($this->user()?->hasRole(UserRole::SuperAdmin)) {
            $roles = UserRole::adminAssignable();
        }

        return array_map(static fn (UserRole $role): string => $role->value, $roles);
    }
}
