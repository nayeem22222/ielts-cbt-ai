<?php

declare(strict_types=1);

namespace App\Http\Requests\Student;

use App\Enums\Exam\TestAttemptStatus;
use App\Models\ReadingAttempt;
use Illuminate\Foundation\Http\FormRequest;

abstract class ReadingAttemptScopedRequest extends FormRequest
{
    protected function attemptFromRoute(): ?ReadingAttempt
    {
        $attempt = $this->route('attempt');

        return $attempt instanceof ReadingAttempt ? $attempt : null;
    }

    protected function ownsWritableAttempt(): bool
    {
        $attempt = $this->attemptFromRoute();

        return $attempt !== null
            && $this->user()?->id === $attempt->user_id
            && $attempt->status === TestAttemptStatus::InProgress;
    }
}
