<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Auth\Permission as PermissionEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'name',
        'group',
        'description',
        'guard_name',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public static function findByName(PermissionEnum|string $name): ?self
    {
        $value = $name instanceof PermissionEnum ? $name->value : $name;

        return static::query()->where('name', $value)->first();
    }
}
