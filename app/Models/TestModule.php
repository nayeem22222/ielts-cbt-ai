<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Course\PublishStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestModule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'test_id',
        'module',
        'title',
        'instructions',
        'sort_order',
        'duration_seconds',
        'question_count',
        'total_marks',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
            'sort_order' => 'integer',
            'duration_seconds' => 'integer',
            'question_count' => 'integer',
            'total_marks' => 'decimal:2',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(ExamTest::class, 'test_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(TestSection::class)->orderBy('sort_order');
    }
}
