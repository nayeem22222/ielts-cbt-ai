<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Models\ReadingAttempt;
use App\Models\ReadingNote;
use App\Models\ReadingPassage;
use App\Models\ReadingQuestion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ReadingNoteService
{
    public function assertCanManage(ReadingAttempt $attempt, ?User $user = null): void
    {
        $user ??= auth()->user();

        if ($user === null || $attempt->user_id !== $user->id) {
            throw new AuthorizationException('This attempt does not belong to you.');
        }

        if ($attempt->status?->value !== 'in_progress') {
            throw new ConflictHttpException('Notes can only be changed during an in-progress attempt.');
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForAttempt(ReadingAttempt $attempt, User $user): array
    {
        return ReadingNote::query()
            ->where('attempt_id', $attempt->id)
            ->where('user_id', $user->id)
            ->with(['question:id,question_number', 'passage:id,part_number,title'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (ReadingNote $note): array => $this->serialize($note))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function store(ReadingAttempt $attempt, User $user, array $payload): array
    {
        $this->assertCanManage($attempt, $user);

        $questionId = isset($payload['question_id']) ? (int) $payload['question_id'] : null;
        $passageId = isset($payload['passage_id']) ? (int) $payload['passage_id'] : null;

        if ($questionId !== null) {
            $this->assertQuestionBelongsToAttempt($attempt, $questionId);
        }

        if ($passageId !== null) {
            $this->assertPassageBelongsToAttempt($attempt, $passageId);
        }

        $note = ReadingNote::query()->create([
            'attempt_id' => $attempt->id,
            'question_id' => $questionId,
            'passage_id' => $passageId,
            'user_id' => $user->id,
            'title' => $payload['title'] ?? null,
            'content' => (string) ($payload['content'] ?? ''),
            'selected_text' => $payload['selected_text'] ?? null,
            'start_offset' => isset($payload['start_offset']) ? (int) $payload['start_offset'] : null,
            'end_offset' => isset($payload['end_offset']) ? (int) $payload['end_offset'] : null,
        ]);

        return $this->serialize($note->load(['question:id,question_number', 'passage:id,part_number,title']));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function update(ReadingAttempt $attempt, User $user, ReadingNote $note, array $payload): array
    {
        $this->assertCanManage($attempt, $user);

        if ($note->attempt_id !== $attempt->id || $note->user_id !== $user->id) {
            throw new AuthorizationException('This note does not belong to you.');
        }

        if (array_key_exists('title', $payload)) {
            $note->title = $payload['title'];
        }

        if (array_key_exists('content', $payload)) {
            $note->content = (string) $payload['content'];
        }

        $note->save();

        return $this->serialize($note->load(['question:id,question_number', 'passage:id,part_number,title']));
    }

    public function destroy(ReadingAttempt $attempt, User $user, ReadingNote $note): void
    {
        $this->assertCanManage($attempt, $user);

        if ($note->attempt_id !== $attempt->id || $note->user_id !== $user->id) {
            throw new AuthorizationException('This note does not belong to you.');
        }

        $note->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(ReadingNote $note): array
    {
        return [
            'id' => $note->id,
            'question_id' => $note->question_id,
            'question_number' => $note->question?->question_number,
            'passage_id' => $note->passage_id,
            'passage_label' => $note->passage ? 'Part '.($note->passage->part_number ?: 1) : null,
            'title' => $note->title,
            'content' => $note->content,
            'selected_text' => $note->selected_text,
            'start_offset' => $note->start_offset,
            'end_offset' => $note->end_offset,
            'updated_at' => $note->updated_at?->toIso8601String(),
        ];
    }

    private function assertPassageBelongsToAttempt(ReadingAttempt $attempt, int $passageId): void
    {
        $exists = ReadingPassage::query()
            ->where('id', $passageId)
            ->where('reading_test_id', $attempt->reading_test_id)
            ->exists();

        if (! $exists) {
            abort(422, 'Passage does not belong to this reading test.');
        }
    }

    private function assertQuestionBelongsToAttempt(ReadingAttempt $attempt, int $questionId): void
    {
        $exists = ReadingQuestion::query()
            ->where('id', $questionId)
            ->whereHas('group.passage', fn ($query) => $query->where('reading_test_id', $attempt->reading_test_id))
            ->exists();

        if (! $exists) {
            abort(422, 'Question does not belong to this reading test.');
        }
    }
}
