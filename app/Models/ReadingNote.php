<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingNote extends Model
{
    protected $fillable = [
        'attempt_id',
        'question_id',
        'passage_id',
        'user_id',
        'title',
        'content',
        'selected_text',
        'start_offset',
        'end_offset',
    ];

    protected function casts(): array
    {
        return [
            'start_offset' => 'integer',
            'end_offset' => 'integer',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ReadingAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ReadingQuestion::class, 'question_id');
    }

    public function passage(): BelongsTo
    {
        return $this->belongsTo(ReadingPassage::class, 'passage_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
