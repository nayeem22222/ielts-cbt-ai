<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Course\ExamType;
use App\Enums\Course\PublishStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class QuestionBank extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'slug',
        'name',
        'description',
        'module',
        'exam_type',
        'status',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'exam_type' => ExamType::class,
            'status' => PublishStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (QuestionBank $bank): void {
            if (empty($bank->uuid)) {
                $bank->uuid = (string) Str::uuid();
            }
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->orderBy('sort_order');
    }
}
