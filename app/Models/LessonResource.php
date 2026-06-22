<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Course\ResourceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonResource extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'course_id',
        'lesson_id',
        'title',
        'file_path',
        'file_type',
        'external_url',
        'sort_order',
        'is_downloadable',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_type' => ResourceType::class,
            'sort_order' => 'integer',
            'is_downloadable' => 'boolean',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}
