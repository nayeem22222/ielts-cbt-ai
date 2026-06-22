<?php

declare(strict_types=1);

namespace App\Models;

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

    public static function findBySlug(UserRole|string $slug): ?self
    {
        $value = $slug instanceof UserRole ? $slug->value : $slug;

        return static::query()->where('slug', $value)->first();
    }
}
