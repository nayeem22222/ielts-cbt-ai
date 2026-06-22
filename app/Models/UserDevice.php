<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDevice extends Model
{
    protected $fillable = [
        'user_id',
        'device_uuid',
        'device_name',
        'browser',
        'os',
        'user_agent',
        'session_id',
        'platform',
        'push_token',
        'ip_address',
        'is_trusted',
        'last_used_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_trusted' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markTrusted(): void
    {
        $this->forceFill(['is_trusted' => true])->save();
    }

    public function markUntrusted(): void
    {
        $this->forceFill(['is_trusted' => false])->save();
    }

    public function isActive(): bool
    {
        return $this->session_id !== null;
    }
}
