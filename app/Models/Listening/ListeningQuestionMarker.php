<?php

declare(strict_types=1);

namespace App\Models\Listening;

use App\Enums\Listening\ListeningMarkerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListeningQuestionMarker extends Model
{
    protected $fillable = [
        'listening_test_id',
        'listening_section_id',
        'listening_question_id',
        'listening_question_group_id',
        'marker_type',
        'timestamp_start',
        'timestamp_end',
        'label',
        'note',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'marker_type' => ListeningMarkerType::class,
            'timestamp_start' => 'decimal:3',
            'timestamp_end' => 'decimal:3',
            'meta' => 'array',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(ListeningTest::class, 'listening_test_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(ListeningSection::class, 'listening_section_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ListeningQuestion::class, 'listening_question_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ListeningQuestionGroup::class, 'listening_question_group_id');
    }
}
