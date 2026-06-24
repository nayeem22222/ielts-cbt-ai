<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ReadingTest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'slug',
        'title',
        'exam_type',
        'duration_minutes',
        'instructions',
        'meta_description',
        'notes',
        'status',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'exam_type' => ExamType::class,
            'duration_minutes' => 'integer',
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ReadingTest $test): void {
            if (empty($test->uuid)) {
                $test->uuid = (string) Str::uuid();
            }
        });
    }

    public function passages(): HasMany
    {
        return $this->hasMany(ReadingPassage::class)->orderBy('sort_order');
    }

    public function questionGroups(): HasManyThrough
    {
        return $this->hasManyThrough(
            ReadingQuestionGroup::class,
            ReadingPassage::class,
            'reading_test_id',
            'passage_id',
            'id',
            'id'
        )->orderBy('reading_question_groups.sort_order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ReadingAttempt::class);
    }

    public function questions(): Builder
    {
        return ReadingQuestion::query()
            ->whereHas('group.passage', fn (Builder $query) => $query->where('reading_test_id', $this->id));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Published->value);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Draft->value);
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', PublishStatus::Archived->value);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if ($search === null || trim($search) === '') {
            return $query;
        }

        $term = '%'.trim($search).'%';

        return $query->where(function (Builder $inner) use ($term): void {
            $inner->where('title', 'like', $term)
                ->orWhere('slug', 'like', $term);
        });
    }

    public function scopeExamType(Builder $query, ?string $examType): Builder
    {
        if ($examType === null || $examType === '') {
            return $query;
        }

        return $query->where('exam_type', $examType);
    }

    public function scopeOrdered(Builder $query, string $sort = 'id', string $direction = 'desc'): Builder
    {
        $allowed = ['id', 'title', 'created_at', 'duration_minutes'];
        $sort = in_array($sort, $allowed, true) ? $sort : 'id';
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $direction);
    }

    public function getPassagesCountAttribute(): int
    {
        return (int) ($this->attributes['passages_count'] ?? $this->passages()->count());
    }

    public function getQuestionGroupsCountAttribute(): int
    {
        return (int) ($this->attributes['question_groups_count'] ?? $this->questionGroups()->count());
    }

    public function getQuestionsCountAttribute(): int
    {
        if (array_key_exists('questions_count', $this->attributes)) {
            return (int) $this->attributes['questions_count'];
        }

        return $this->questions()->count();
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            PublishStatus::Published => 'success',
            PublishStatus::Archived => 'neutral',
            default => 'warning',
        };
    }

    public function getExamTypeLabelAttribute(): string
    {
        return $this->exam_type?->label() ?? '';
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status?->label() ?? '';
    }
}
