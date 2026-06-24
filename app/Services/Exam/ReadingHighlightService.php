<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Enums\Exam\ReadingHighlightColor;
use App\Models\ReadingAttempt;
use App\Models\ReadingHighlight;
use App\Models\ReadingPassage;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ReadingHighlightService
{
    public function assertCanManage(ReadingAttempt $attempt, ?User $user = null): void
    {
        $user ??= auth()->user();

        if ($user === null || $attempt->user_id !== $user->id) {
            throw new AuthorizationException('This attempt does not belong to you.');
        }

        if ($attempt->status?->value !== 'in_progress') {
            throw new ConflictHttpException('Highlights can only be changed during an in-progress attempt.');
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForAttempt(ReadingAttempt $attempt, User $user): array
    {
        return ReadingHighlight::query()
            ->where('attempt_id', $attempt->id)
            ->where('user_id', $user->id)
            ->orderBy('passage_id')
            ->orderBy('start_offset')
            ->get()
            ->map(fn (ReadingHighlight $highlight): array => $this->serialize($highlight))
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function store(ReadingAttempt $attempt, User $user, array $payload): array
    {
        $this->assertCanManage($attempt, $user);
        $this->assertPassageBelongsToAttempt($attempt, (int) $payload['passage_id']);

        $color = ReadingHighlightColor::from((string) $payload['highlight_color']);

        $highlight = ReadingHighlight::query()->create([
            'attempt_id' => $attempt->id,
            'passage_id' => (int) $payload['passage_id'],
            'user_id' => $user->id,
            'selected_text' => (string) $payload['selected_text'],
            'start_offset' => (int) $payload['start_offset'],
            'end_offset' => (int) $payload['end_offset'],
            'highlight_color' => $color,
            'note_text' => $payload['note_text'] ?? null,
        ]);

        return $this->serialize($highlight);
    }

    public function destroy(ReadingAttempt $attempt, User $user, ReadingHighlight $highlight): void
    {
        $this->assertCanManage($attempt, $user);

        if ($highlight->attempt_id !== $attempt->id || $highlight->user_id !== $user->id) {
            throw new AuthorizationException('This highlight does not belong to you.');
        }

        $highlight->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function serialize(ReadingHighlight $highlight): array
    {
        return [
            'id' => $highlight->id,
            'passage_id' => $highlight->passage_id,
            'selected_text' => $highlight->selected_text,
            'start_offset' => $highlight->start_offset,
            'end_offset' => $highlight->end_offset,
            'highlight_color' => $highlight->highlight_color?->value ?? 'yellow',
            'note_text' => $highlight->note_text,
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
}
