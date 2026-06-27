<?php

declare(strict_types=1);

namespace App\Actions\Listening;

use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use Illuminate\Validation\ValidationException;

class ValidateSectionBelongsToTestAction
{
    public function execute(ListeningTest $test, ListeningSection $section): void
    {
        if ((int) $section->listening_test_id !== (int) $test->id) {
            throw ValidationException::withMessages([
                'section' => 'Section does not belong to this test.',
            ]);
        }
    }
}
