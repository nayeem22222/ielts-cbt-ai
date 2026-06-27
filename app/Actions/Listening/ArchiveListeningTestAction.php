<?php

declare(strict_types=1);

namespace App\Actions\Listening;

use App\Enums\Listening\ListeningTestStatus;
use App\Models\Listening\ListeningTest;
use Illuminate\Support\Facades\DB;

class ArchiveListeningTestAction
{
    public function execute(ListeningTest $test): ListeningTest
    {
        return DB::transaction(function () use ($test): ListeningTest {
            $test->forceFill([
                'status' => ListeningTestStatus::Archived,
                'is_active' => false,
                'updated_by' => auth()->id(),
            ])->save();

            return $test->refresh();
        });
    }
}
