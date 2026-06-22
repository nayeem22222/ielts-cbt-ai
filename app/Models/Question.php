<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Exam\ReadingQuestionType;
use App\Enums\Course\PublishStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Question extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'question_bank_id',
        'module',
        'type',
        'question_number',
        'prompt',
        'stimulus',
        'difficulty',
        'marks',
        'sort_order',
        'version',
        'status',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ReadingQuestionType::class,
            'stimulus' => 'array',
            'status' => PublishStatus::class,
            'question_number' => 'integer',
            'marks' => 'decimal:2',
            'sort_order' => 'integer',
            'version' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Question $question): void {
            if (empty($question->uuid)) {
                $question->uuid = (string) Str::uuid();
            }
        });
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_bank_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort_order');
    }

    public function correctAnswer(): HasOne
    {
        return $this->hasOne(QuestionCorrectAnswer::class);
    }

    public function explanation(): HasOne
    {
        return $this->hasOne(QuestionExplanation::class);
    }

    public function tags(): HasMany
    {
        return $this->hasMany(QuestionTag::class);
    }

    public function testQuestions(): HasMany
    {
        return $this->hasMany(TestQuestion::class);
    }
}
