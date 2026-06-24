<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Exam\ReadingQuestionTicketIssueType;
use App\Enums\Exam\ReadingQuestionTicketStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadingQuestionTicket extends Model
{
    protected $fillable = [
        'reading_test_id',
        'attempt_id',
        'question_id',
        'question_number',
        'user_id',
        'issue_type',
        'message',
        'status',
        'admin_reply',
    ];

    protected function casts(): array
    {
        return [
            'question_number' => 'integer',
            'issue_type' => ReadingQuestionTicketIssueType::class,
            'status' => ReadingQuestionTicketStatus::class,
        ];
    }

    public function test(): BelongsTo
    {
        return $this->belongsTo(ReadingTest::class, 'reading_test_id');
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ReadingAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ReadingQuestion::class, 'question_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
