<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ReadingAttempt;
use App\Services\Exam\ReadingTimerService;
use Illuminate\Http\JsonResponse;

class ReadingTimerController extends Controller
{
    public function __construct(private readonly ReadingTimerService $timer)
    {
    }

    public function show(ReadingAttempt $attempt): JsonResponse
    {
        $this->timer->assertOwnedByUser($attempt);

        return response()->json([
            'data' => $this->timer->syncTimer($attempt),
        ]);
    }
}
