<?php

declare(strict_types=1);

namespace App\Services\Exam;

use App\Models\ReadingAttempt;
use App\Models\User;

class ReadingStudentExperienceService
{
    public function __construct(
        private readonly ReadingHighlightService $highlights,
        private readonly ReadingNoteService $notes,
    ) {
    }

    /**
     * @param  array<string, mixed>  $endpoints
     * @return array<string, mixed>
     */
    public function augmentAttemptPayload(ReadingAttempt $attempt, array $endpoints, ?User $user = null): array
    {
        $user ??= auth()->user();

        if ($user === null) {
            return [
                'highlights' => [],
                'notes' => [],
            ];
        }

        return [
            'highlights' => $this->highlights->listForAttempt($attempt, $user),
            'notes' => $this->notes->listForAttempt($attempt, $user),
            'endpoints' => array_merge($endpoints, [
                'highlights' => route('reading-attempts.highlights.store', $attempt),
                'highlightsDestroy' => route('reading-attempts.highlights.destroy', ['attempt' => $attempt, 'highlight' => '__ID__']),
                'notes' => route('reading-attempts.notes.store', $attempt),
                'notesUpdate' => route('reading-attempts.notes.update', ['attempt' => $attempt, 'note' => '__ID__']),
                'notesDestroy' => route('reading-attempts.notes.destroy', ['attempt' => $attempt, 'note' => '__ID__']),
                'tickets' => route('reading-attempts.tickets.store', $attempt),
            ]),
        ];
    }
}
