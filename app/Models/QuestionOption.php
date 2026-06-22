<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionOption extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'question_id',
        'label',
        'option_text',
        'sort_order',
    ];

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
