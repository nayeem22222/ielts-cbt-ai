<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Auth\UserRole;
use App\Enums\Auth\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'password',
        'status',
        'locale',
        'timezone',
        'last_login_at',
        'last_login_ip',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (empty($user->uuid)) {
                $user->uuid = (string) Str::uuid();
            }

            if (empty($user->status)) {
                $user->status = UserStatus::Active->value;
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }

    public function studentProfile(): HasOne
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function roleSlug(): ?string
    {
        return $this->roles()->first()?->slug;
    }

    public function primaryRole(): ?UserRole
    {
        $slug = $this->roleSlug();

        return $slug !== null ? UserRole::tryFrom($slug) : null;
    }

    public function hasRole(UserRole|string $role): bool
    {
        $slug = $role instanceof UserRole ? $role->value : $role;

        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('slug', $slug);
        }

        return $this->roles()->where('slug', $slug)->exists();
    }

    /**
     * @param  list<UserRole|string>  $roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active->value;
    }

    public function isAdminPanelUser(): bool
    {
        return $this->hasAnyRole([UserRole::Admin->value, UserRole::SuperAdmin->value]);
    }

    public function assignRole(UserRole|string $role): void
    {
        $roleModel = Role::findBySlug($role);

        if ($roleModel === null) {
            return;
        }

        $this->roles()->sync([$roleModel->id]);
    }

    public function dashboardPath(): string
    {
        $role = $this->primaryRole();

        if ($role === null) {
            return route('home');
        }

        return route($role->dashboardRouteName());
    }

    public function recordLogin(?string $ipAddress = null): void
    {
        $this->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress,
        ])->save();
    }
}
