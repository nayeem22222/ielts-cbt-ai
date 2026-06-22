<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionTag extends Model
{
    protected $fillable = [
        'question_id',
        'tag',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
