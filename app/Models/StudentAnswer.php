<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentAnswer extends Model
{
    protected $fillable = [
        'uuid',
        'test_attempt_id',
        'test_section_id',
        'question_id',
        'test_question_id',
        'module',
        'answer_text',
        'selected_options',
        'audio_path',
        'word_count',
        'is_flagged',
        'is_final',
        'submitted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'selected_options' => 'array',
            'is_flagged' => 'boolean',
            'is_final' => 'boolean',
            'submitted_at' => 'datetime',
            'word_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (StudentAnswer $answer): void {
            if (empty($answer->uuid)) {
                $answer->uuid = (string) Str::uuid();
            }
        });
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TestAttempt::class, 'test_attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(TestSection::class, 'test_section_id');
    }
}
