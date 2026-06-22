<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Exam\TestAttemptStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class TestAttempt extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'test_id',
        'test_module_id',
        'current_section_id',
        'status',
        'started_at',
        'submitted_at',
        'completed_at',
        'time_remaining_seconds',
        'ip_address',
        'user_agent',
        'tab_switch_count',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TestAttemptStatus::class,
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
            'completed_at' => 'datetime',
            'metadata' => 'array',
            'time_remaining_seconds' => 'integer',
            'tab_switch_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (TestAttempt $attempt): void {
            if (empty($attempt->uuid)) {
                $attempt->uuid = (string) Str::uuid();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(ExamTest::class, 'test_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(TestModule::class, 'test_module_id');
    }

    public function currentSection(): BelongsTo
    {
        return $this->belongsTo(TestSection::class, 'current_section_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(StudentAnswer::class);
    }

    public function autosaveLogs(): HasMany
    {
        return $this->hasMany(AutosaveLog::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}
