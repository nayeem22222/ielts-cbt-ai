<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Contracts\View\View;

class PermissionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Permission::class);

        $permissions = Permission::query()
            ->withCount('roles')
            ->orderBy('group')
            ->orderBy('name')
            ->get()
            ->groupBy('group');

        return view('pages.admin.permissions.index', [
            'permissions' => $permissions,
        ]);
    }
}
