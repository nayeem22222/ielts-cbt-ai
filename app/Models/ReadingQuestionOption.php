<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\TouchesReadingTestCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingQuestionOption extends Model
{
    use TouchesReadingTestCache;
    protected $fillable = [
        'group_id',
        'question_id',
        'option_key',
        'option_label',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ReadingQuestion::class, 'question_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ReadingQuestionGroup::class, 'group_id');
    }

    protected function touchReadingTestForCache(): void
    {
        if ($this->group_id !== null) {
            $this->loadMissing('group.passage');
            $this->touchReadingTestById($this->group?->passage?->reading_test_id);

            return;
        }

        $this->loadMissing('question.group.passage');
        $this->touchReadingTestById($this->question?->group?->passage?->reading_test_id);
    }
}
