<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use App\Enums\Exam\TestType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ExamTest extends Model
{
    use SoftDeletes;

    protected $table = 'tests';

    protected $fillable = [
        'uuid',
        'slug',
        'title',
        'description',
        'type',
        'exam_type',
        'duration_seconds',
        'total_questions',
        'is_timed',
        'passing_band',
        'version',
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
            'type' => TestType::class,
            'exam_type' => ExamType::class,
            'status' => PublishStatus::class,
            'duration_seconds' => 'integer',
            'total_questions' => 'integer',
            'is_timed' => 'boolean',
            'passing_band' => 'decimal:1',
            'version' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ExamTest $test): void {
            if (empty($test->uuid)) {
                $test->uuid = (string) Str::uuid();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(TestModule::class, 'test_id');
    }

    public function readingModule(): HasMany
    {
        return $this->modules()->where('module', 'reading');
    }

    public function sections(): HasManyThrough
    {
        return $this->hasManyThrough(TestSection::class, TestModule::class, 'test_id', 'test_module_id');
    }

    public function testQuestions(): HasMany
    {
        return $this->hasMany(TestQuestion::class, 'test_id');
    }

    public function readingAnalytics(): HasMany
    {
        return $this->hasMany(ReadingAnalytics::class, 'test_id');
    }
}
