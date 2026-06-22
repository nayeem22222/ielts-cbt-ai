<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Course\PublishStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestSection extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'test_module_id',
        'title',
        'instructions',
        'sort_order',
        'duration_seconds',
        'question_count',
        'total_marks',
        'stimulus_text',
        'stimulus_audio_path',
        'stimulus_image_path',
        'metadata',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PublishStatus::class,
            'metadata' => 'array',
            'sort_order' => 'integer',
            'duration_seconds' => 'integer',
            'question_count' => 'integer',
            'total_marks' => 'decimal:2',
        ];
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(TestModule::class, 'test_module_id');
    }

    public function testQuestions(): HasMany
    {
        return $this->hasMany(TestQuestion::class)->orderBy('sort_order');
    }
}
