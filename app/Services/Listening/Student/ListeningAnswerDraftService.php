<?php

declare(strict_types=1);

namespace App\Services\Listening\Student;

use App\Models\Listening\ListeningAttempt;
use App\Models\Listening\ListeningAttemptAnswer;

class ListeningAnswerDraftService
{
    public function __construct(
        private readonly ListeningAutoSaveService $autoSave,
    ) {}

    public function buildDraftKey(ListeningAttempt $attempt): string
    {
        return 'listening_attempt_'.$attempt->id.'_draft';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getServerAnswerSnapshot(ListeningAttempt $attempt): array
    {
        $snapshot = [];

        foreach ($attempt->answers()->with('question')->orderBy('question_number')->get() as $row) {
            /** @var ListeningAttemptAnswer $row */
            $meta = is_array($row->meta) ? $row->meta : [];
            $autosave = is_array($meta['autosave'] ?? null) ? $meta['autosave'] : [];

            $snapshot[(int) $row->question_number] = [
                'question_id' => (int) $row->listening_question_id,
                'question_number' => (int) $row->question_number,
                'answer' => $row->student_answer,
                'hash' => (string) ($autosave['last_saved_hash'] ?? $this->autoSave->calculateAnswerHash($row->student_answer)),
                'updated_at' => (string) ($autosave['client_saved_at'] ?? $row->updated_at?->toIso8601String() ?? ''),
                'is_flagged' => ($meta['is_flagged'] ?? false) === true,
            ];
        }

        return $snapshot;
    }

    /**
     * @param  array<string, mixed>  $clientDraft
     * @param  array<int, array<string, mixed>>  $serverSnapshot
     * @return array<string, mixed>
     */
    public function compareClientDraftWithServer(array $clientDraft, array $serverSnapshot): array
    {
        $clientAnswers = is_array($clientDraft['answers'] ?? null) ? $clientDraft['answers'] : [];
        $conflicts = [];
        $recoverable = [];

        foreach ($clientAnswers as $key => $clientItem) {
            if (! is_array($clientItem)) {
                continue;
            }

            $questionNumber = (int) ($clientItem['question_number'] ?? $key);
            $serverItem = $serverSnapshot[$questionNumber] ?? null;

            if ($serverItem === null) {
                $recoverable[] = $clientItem;

                continue;
            }

            $clientHash = (string) ($clientItem['hash'] ?? $this->autoSave->calculateAnswerHash($clientItem['answer'] ?? null));
            $serverHash = (string) ($serverItem['hash'] ?? '');

            if ($clientHash === $serverHash) {
                continue;
            }

            $clientAt = strtotime((string) ($clientItem['updated_at'] ?? '')) ?: 0;
            $serverAt = strtotime((string) ($serverItem['updated_at'] ?? '')) ?: 0;

            if (config('listening.recovery.prefer_newer_answer', true) && $clientAt > $serverAt) {
                $recoverable[] = $clientItem;
            } else {
                $conflicts[] = [
                    'question_number' => $questionNumber,
                    'client' => [
                        'answer' => $clientItem['answer'] ?? null,
                        'hash' => $clientHash,
                        'updated_at' => $clientItem['updated_at'] ?? null,
                    ],
                    'server' => [
                        'answer' => $serverItem['answer'] ?? null,
                        'hash' => $serverHash,
                        'updated_at' => $serverItem['updated_at'] ?? null,
                    ],
                ];
            }
        }

        return [
            'recoverable' => $recoverable,
            'conflicts' => $conflicts,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $draftAnswers
     * @return list<array<string, mixed>>
     */
    public function recoverDraftAnswers(ListeningAttempt $attempt, array $draftAnswers): array
    {
        $applied = [];

        foreach ($draftAnswers as $item) {
            $questionId = (int) ($item['question_id'] ?? 0);

            if ($questionId <= 0) {
                continue;
            }

            $question = $attempt->test?->questions()->whereKey($questionId)->first();

            if ($question === null) {
                continue;
            }

            $result = $this->autoSave->saveAnswer($attempt, $question, $item['answer'] ?? null, [
                'client_answer_hash' => $item['hash'] ?? null,
                'client_saved_at' => $item['updated_at'] ?? null,
                'saved_from' => 'recovery',
            ]);

            $applied[] = $result->toArray();
        }

        return $applied;
    }
}
