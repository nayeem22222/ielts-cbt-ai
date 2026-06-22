<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Course\LessonContentType;
use App\Enums\Course\PublishStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Lesson extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'course_section_id',
        'slug',
        'title',
        'description',
        'content_type',
        'video_url',
        'duration_seconds',
        'is_preview',
        'sort_order',
        'status',
        'published_at',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'content_type' => LessonContentType::class,
            'status' => PublishStatus::class,
            'duration_seconds' => 'integer',
            'sort_order' => 'integer',
            'is_preview' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Lesson $lesson): void {
            if (empty($lesson->uuid)) {
                $lesson->uuid = (string) Str::uuid();
            }
        });
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(LessonResource::class);
    }
}
