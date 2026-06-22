<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SyncRolePermissionsRequest;
use App\Models\Role;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class RoleController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->orderBy('id')
            ->get();

        return view('pages.admin.roles.index', [
            'roles' => $roles,
        ]);
    }

    public function edit(Role $role): View
    {
        $this->authorize('updatePermissions', $role);

        $role->load('permissions');

        return view('pages.admin.roles.edit', [
            'role' => $role,
            'permissions' => \App\Models\Permission::query()->orderBy('group')->orderBy('name')->get()->groupBy('group'),
            'assigned' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function syncPermissions(SyncRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $this->authorize('updatePermissions', $role);

        $role->syncPermissions($request->input('permissions', []));

        return redirect()
            ->route('admin.roles.edit', $role)
            ->with('status', 'Role permissions updated successfully.');
    }
}
