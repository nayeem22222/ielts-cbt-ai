<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Enums\Exam\ReadingQuestionTicketIssueType;
use App\Enums\Exam\ReadingQuestionTicketStatus;
use App\Models\ReadingAttempt;
use App\Models\ReadingQuestion;
use App\Models\ReadingQuestionTicket;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ReadingQuestionTicketService
{
    public function __construct(private readonly ReadingAnswerService $answers)
    {
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function storeForStudent(ReadingAttempt $attempt, User $user, array $payload): array
    {
        if ($attempt->user_id !== $user->id) {
            throw new AuthorizationException('This attempt does not belong to you.');
        }

        $question = ReadingQuestion::query()->findOrFail((int) $payload['question_id']);
        $this->answers->assertQuestionBelongsToAttemptTest($attempt, $question);

        $ticket = ReadingQuestionTicket::query()->create([
            'reading_test_id' => $attempt->reading_test_id,
            'attempt_id' => $attempt->id,
            'question_id' => $question->id,
            'question_number' => $question->question_number,
            'user_id' => $user->id,
            'issue_type' => ReadingQuestionTicketIssueType::from((string) $payload['issue_type']),
            'message' => (string) $payload['message'],
            'status' => ReadingQuestionTicketStatus::Open,
        ]);

        return $this->serialize($ticket->load(['user:id,name,email', 'question.group.passage', 'test:id,title']));
    }

    /**
     * @return LengthAwarePaginator<int, ReadingQuestionTicket>
     */
    public function paginateForAdmin(?string $status = null, int $perPage = 20): LengthAwarePaginator
    {
        return ReadingQuestionTicket::query()
            ->with(['user:id,name,email', 'question.group.passage', 'test:id,title', 'attempt:id,uuid'])
            ->when($status, fn (Builder $query): Builder => $query->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function reply(ReadingQuestionTicket $ticket, string $reply): ReadingQuestionTicket
    {
        $ticket->admin_reply = $reply;
        $ticket->status = ReadingQuestionTicketStatus::Pending;
        $ticket->save();

        return $ticket->fresh(['user:id,name,email', 'question.group.passage', 'test:id,title', 'attempt:id,uuid']);
    }

    public function resolve(ReadingQuestionTicket $ticket): ReadingQuestionTicket
    {
        $ticket->status = ReadingQuestionTicketStatus::Resolved;
        $ticket->save();

        return $ticket->fresh(['user:id,name,email', 'question.group.passage', 'test:id,title', 'attempt:id,uuid']);
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(ReadingQuestionTicket $ticket): array
    {
        $question = $ticket->question;
        $passage = $question?->group?->passage;

        return [
            'id' => $ticket->id,
            'reading_test_id' => $ticket->reading_test_id,
            'test_title' => $ticket->test?->title,
            'attempt_uuid' => $ticket->attempt?->uuid,
            'question_id' => $ticket->question_id,
            'question_number' => $ticket->question_number,
            'question_prompt' => $question?->prompt,
            'passage_title' => $passage?->title,
            'part_number' => $passage?->part_number,
            'student_name' => $ticket->user?->name,
            'student_email' => $ticket->user?->email,
            'issue_type' => $ticket->issue_type?->value,
            'issue_type_label' => $ticket->issue_type?->label(),
            'message' => $ticket->message,
            'status' => $ticket->status?->value,
            'status_label' => $ticket->status?->label(),
            'admin_reply' => $ticket->admin_reply,
            'created_at' => $ticket->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function statusCounts(): array
    {
        $counts = ReadingQuestionTicket::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        return [
            'open' => (int) ($counts['open'] ?? 0),
            'pending' => (int) ($counts['pending'] ?? 0),
            'resolved' => (int) ($counts['resolved'] ?? 0),
        ];
    }
}
