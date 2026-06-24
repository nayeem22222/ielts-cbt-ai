<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Exam\ReadingHighlightColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingHighlight extends Model
{
    protected $fillable = [
        'attempt_id',
        'passage_id',
        'user_id',
        'selected_text',
        'start_offset',
        'end_offset',
        'highlight_color',
        'note_text',
    ];

    protected function casts(): array
    {
        return [
            'start_offset' => 'integer',
            'end_offset' => 'integer',
            'highlight_color' => ReadingHighlightColor::class,
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ReadingAttempt::class, 'attempt_id');
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
