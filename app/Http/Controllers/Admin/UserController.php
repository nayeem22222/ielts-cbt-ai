<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Auth\UserRole;
use App\Enums\Auth\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->query('search', ''));
        $role = (string) $request->query('role', '');
        $status = (string) $request->query('status', '');

        $users = User::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($role !== '', fn ($query) => $query->whereHas('roles', fn ($inner) => $inner->where('slug', $role)))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('pages.admin.users.index', [
            'users' => $users,
            'roles' => UserRole::cases(),
            'statuses' => UserStatus::cases(),
            'filters' => [
                'search' => $search,
                'role' => $role,
                'status' => $status,
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('pages.admin.users.create', [
            'roles' => $this->assignableRoles(),
            'statuses' => UserStatus::cases(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = User::query()->create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'phone' => $request->string('phone')->toString() ?: null,
            'password' => $request->string('password')->toString(),
            'status' => $request->string('status')->toString(),
        ]);

        $user->assignRole($request->string('role')->toString());

        if ($user->hasRole(UserRole::Student)) {
            $user->studentProfile()->create([]);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User created successfully.');
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $user->load('roles');

        return view('pages.admin.users.edit', [
            'user' => $user,
            'roles' => $this->assignableRoles($user),
            'statuses' => UserStatus::cases(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'phone' => $request->string('phone')->toString() ?: null,
            'status' => $request->string('status')->toString(),
        ]);

        if ($request->filled('password')) {
            $user->password = $request->string('password')->toString();
        }

        $user->save();
        $user->assignRole($request->string('role')->toString());

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User deleted successfully.');
    }

    /**
     * @return list<UserRole>
     */
    private function assignableRoles(?User $target = null): array
    {
        $roles = UserRole::assignableByAdmin();

        if (auth()->user()?->hasRole(UserRole::SuperAdmin)) {
            $roles = UserRole::adminAssignable();
        }

        if ($target?->hasRole(UserRole::SuperAdmin) && ! auth()->user()?->hasRole(UserRole::SuperAdmin)) {
            return [UserRole::SuperAdmin];
        }

        return $roles;
    }
}
