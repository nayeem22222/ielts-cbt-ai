<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutosaveLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'test_attempt_id',
        'student_answer_id',
        'payload',
        'saved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'saved_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(TestAttempt::class, 'test_attempt_id');
    }

    public function studentAnswer(): BelongsTo
    {
        return $this->belongsTo(StudentAnswer::class);
    }
}
