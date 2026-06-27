<?php

declare(strict_types=1);

namespace App\Actions\Listening;

use App\Models\Listening\ListeningSection;
use App\Models\Listening\ListeningTest;
use Illuminate\Support\Facades\DB;

class DetachTranscriptFromSectionAction
{
    public function __construct(
        private readonly ValidateSectionBelongsToTestAction $validateSectionBelongsToTest,
    ) {}

    public function execute(ListeningTest $test, ListeningSection $section): ListeningSection
    {
        $this->validateSectionBelongsToTest->execute($test, $section);

        return DB::transaction(function () use ($section): ListeningSection {
            $section->update(['transcript_id' => null]);

            return $section->refresh();
        });
    }
}
