<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionExplanation extends Model
{
    protected $fillable = [
        'question_id',
        'explanation',
        'rationale',
    ];

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
