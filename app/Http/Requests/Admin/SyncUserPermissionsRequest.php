<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\Auth\Permission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncUserPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $permissionNames = array_map(
            fn (Permission $permission): string => $permission->value,
            Permission::cases()
        );

        return [
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($permissionNames)],
        ];
    }
}
