<?php

declare(strict_types=1);

namespace App\Models\Listening;

use App\Enums\Listening\ListeningDifficultyLevel;
use App\Enums\Listening\ListeningTestStatus;
use App\Enums\Listening\ListeningTestType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class ListeningTest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'test_code',
        'description',
        'status',
        'test_type',
        'total_sections',
        'total_questions',
        'total_marks',
        'duration_minutes',
        'transfer_time_minutes',
        'is_active',
        'is_featured',
        'difficulty_level',
        'instructions',
        'created_by',
        'updated_by',
        'published_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => ListeningTestStatus::class,
            'test_type' => ListeningTestType::class,
            'difficulty_level' => ListeningDifficultyLevel::class,
            'total_sections' => 'integer',
            'total_questions' => 'integer',
            'total_marks' => 'integer',
            'duration_minutes' => 'integer',
            'transfer_time_minutes' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ListeningSection::class)->orderBy('display_order');
    }

    public function questionGroups(): HasMany
    {
        return $this->hasMany(ListeningQuestionGroup::class)->orderBy('display_order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ListeningQuestion::class)->orderBy('question_number');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ListeningAttempt::class);
    }

    public function markers(): HasMany
    {
        return $this->hasMany(ListeningQuestionMarker::class);
    }

    public function setting(): HasOne
    {
        return $this->hasOne(ListeningTestSetting::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ListeningTestStatus::Published);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeExamType(Builder $query, ?string $testType): Builder
    {
        if ($testType === null || $testType === '') {
            return $query;
        }

        return $query->where('test_type', $testType);
    }
}
