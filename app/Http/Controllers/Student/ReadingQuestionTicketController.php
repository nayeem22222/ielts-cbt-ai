<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\StoreReadingQuestionTicketRequest;
use App\Models\ReadingAttempt;
use App\Services\Exam\ReadingQuestionTicketService;
use Illuminate\Http\JsonResponse;

class ReadingQuestionTicketController extends Controller
{
    public function __construct(private readonly ReadingQuestionTicketService $tickets)
    {
    }

    public function store(StoreReadingQuestionTicketRequest $request, ReadingAttempt $attempt): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $ticket = $this->tickets->storeForStudent($attempt, $user, $request->validated());

        return response()->json(['data' => $ticket], 201);
    }
}
