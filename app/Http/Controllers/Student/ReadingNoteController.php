<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreReadingNoteRequest;
use App\Http\Requests\Student\UpdateReadingNoteRequest;
use App\Models\ReadingAttempt;
use App\Models\ReadingNote;
use App\Services\Exam\ReadingNoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReadingNoteController extends Controller
{
    public function __construct(private readonly ReadingNoteService $notes)
    {
    }

    public function index(Request $request, ReadingAttempt $attempt): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        if ($attempt->user_id !== $user->id) {
            abort(403);
        }

        return response()->json([
            'data' => $this->notes->listForAttempt($attempt, $user),
        ]);
    }

    public function store(StoreReadingNoteRequest $request, ReadingAttempt $attempt): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $note = $this->notes->store($attempt, $user, $request->validated());

        return response()->json(['data' => $note], 201);
    }

    public function update(UpdateReadingNoteRequest $request, ReadingAttempt $attempt, ReadingNote $note): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $payload = $this->notes->update($attempt, $user, $note, $request->validated());

        return response()->json(['data' => $payload]);
    }

    public function destroy(Request $request, ReadingAttempt $attempt, ReadingNote $note): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $this->notes->destroy($attempt, $user, $note);

        return response()->json(['success' => true]);
    }
}
