<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Crud\CrudQuery;
use App\Enums\Auth\UserRole;
use App\Enums\Auth\UserStatus;
use App\Http\Controllers\Admin\Concerns\HandlesCrudOperations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\SyncUserPermissionsRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Requests\Crud\CrudIndexRequest;
use App\Events\Auth\PasswordChanged;
use App\Models\User;
use App\Services\Admin\UserCrudService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    use HandlesCrudOperations;

    public function __construct(private readonly UserCrudService $users)
    {
    }

    protected function crudService(): UserCrudService
    {
        return $this->users;
    }

    protected function crudModelClass(): string
    {
        return User::class;
    }

    protected function crudRoutePrefix(): string
    {
        return 'admin.users';
    }

    public function index(CrudIndexRequest $request): View
    {
        $this->authorize('viewAny', User::class);

        $crudQuery = CrudQuery::fromRequest($request, $this->users->definition());
        $users = $this->users->paginate($crudQuery);

        return view('pages.admin.users.index', array_merge(
            $this->crudIndexData($crudQuery, $users),
            [
                'users' => $users,
                'roles' => UserRole::cases(),
                'statuses' => UserStatus::cases(),
                'routePrefix' => $this->crudRoutePrefix(),
            ]
        ));
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
            'email_verified_at' => $request->boolean('email_verified', true) ? now() : null,
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

        $user->load(['roles', 'directPermissions']);

        return view('pages.admin.users.edit', [
            'user' => $user,
            'roles' => $this->assignableRoles($user),
            'statuses' => UserStatus::cases(),
            'permissions' => \App\Models\Permission::query()->orderBy('group')->orderBy('name')->get()->groupBy('group'),
            'directPermissions' => $user->directPermissions->pluck('name')->all(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'phone' => $request->string('phone')->toString() ?: null,
            'status' => $request->string('status')->toString(),
            'email_verified_at' => $request->boolean('email_verified')
                ? ($user->email_verified_at ?? now())
                : null,
        ]);

        if ($request->filled('password')) {
            $user->password = $request->string('password')->toString();

            event(new PasswordChanged(
                user: $user,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
                metadata: ['source' => 'admin'],
            ));
        }

        $user->save();
        $user->assignRole($request->string('role')->toString());

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User updated successfully.');
    }


    public function verifyEmail(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        if ($user->email_verified_at === null) {
            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();
        }

        return redirect()
            ->back()
            ->with('status', 'Email verified successfully.');
    }

    public function unverifyEmail(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        if ($user->email_verified_at !== null) {
            $user->forceFill([
                'email_verified_at' => null,
            ])->save();
        }

        return redirect()
            ->back()
            ->with('status', 'Email verification removed successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'User deleted successfully.');
    }

    public function syncPermissions(SyncUserPermissionsRequest $request, User $user): RedirectResponse
    {
        $this->authorize('assignPermissions', $user);

        $user->syncDirectPermissions($request->input('permissions', []));

        return redirect()
            ->route('admin.users.edit', $user)
            ->with('status', 'Direct permissions updated successfully.');
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
