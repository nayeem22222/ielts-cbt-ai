<?php

declare(strict_types=1);

namespace App\Actions\Listening;

use App\Models\Listening\ListeningTest;
use App\Repositories\Listening\ListeningSectionRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReorderListeningSectionsAction
{
    public function __construct(
        private readonly ListeningSectionRepository $sections,
    ) {}

    /**
     * @param  list<int>  $orderedSectionIds
     */
    public function execute(ListeningTest $test, array $orderedSectionIds): void
    {
        DB::transaction(function () use ($test, $orderedSectionIds): void {
            $sections = $this->sections->query()
                ->where('listening_test_id', $test->id)
                ->whereIn('id', $orderedSectionIds)
                ->get()
                ->keyBy('id');

            if ($sections->count() !== count($orderedSectionIds)) {
                throw ValidationException::withMessages([
                    'sections' => 'One or more sections do not belong to this listening test.',
                ]);
            }

            foreach ($orderedSectionIds as $index => $sectionId) {
                $section = $sections->get($sectionId);

                if ($section === null) {
                    continue;
                }

                $this->sections->update($section, [
                    'display_order' => $index + 1,
                ]);
            }
        });
    }
}
