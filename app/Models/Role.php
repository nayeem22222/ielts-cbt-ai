<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Auth\Permission as PermissionEnum;
use App\Enums\Auth\UserRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'label',
        'description',
        'guard_name',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    public static function findBySlug(UserRole|string $slug): ?self
    {
        $value = $slug instanceof UserRole ? $slug->value : $slug;

        return static::query()->where('slug', $value)->first();
    }

    public function hasPermission(PermissionEnum|string $permission): bool
    {
        $name = $permission instanceof PermissionEnum ? $permission->value : $permission;

        if ($this->relationLoaded('permissions')) {
            return $this->permissions->contains('name', $name);
        }

        return $this->permissions()->where('name', $name)->exists();
    }

    /**
     * @param  list<PermissionEnum|string>  $permissions
     */
    public function syncPermissions(array $permissions): void
    {
        $permissionIds = collect($permissions)
            ->map(fn (PermissionEnum|string $permission): ?int => Permission::findByName($permission)?->id)
            ->filter()
            ->values()
            ->all();

        $this->permissions()->sync($permissionIds);
    }

    /**
     * @param  list<PermissionEnum|string>  $permissions
     */
    public function givePermissionTo(array $permissions): void
    {
        $permissionIds = collect($permissions)
            ->map(fn (PermissionEnum|string $permission): ?int => Permission::findByName($permission)?->id)
            ->filter()
            ->values()
            ->all();

        $this->permissions()->syncWithoutDetaching($permissionIds);
    }
}
