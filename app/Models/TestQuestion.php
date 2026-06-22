<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestQuestion extends Model
{
    use SoftDeletes;

    protected $table = 'test_question';

    protected $fillable = [
        'test_id',
        'test_module_id',
        'test_section_id',
        'question_id',
        'sort_order',
        'marks',
        'is_required',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'marks' => 'decimal:2',
            'is_required' => 'boolean',
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(ExamTest::class, 'test_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(TestModule::class, 'test_module_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(TestSection::class, 'test_section_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
