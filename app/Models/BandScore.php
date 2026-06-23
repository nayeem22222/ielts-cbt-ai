<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Exam\ScoringMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BandScore extends Model
{
    protected $fillable = [
        'result_id',
        'module',
        'band',
        'raw_score',
        'max_score',
        'correct_count',
        'total_count',
        'scoring_method',
    ];

    protected function casts(): array
    {
        return [
            'band' => 'decimal:1',
            'raw_score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'correct_count' => 'integer',
            'total_count' => 'integer',
            'scoring_method' => ScoringMethod::class,
        ];
    }

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }
}
