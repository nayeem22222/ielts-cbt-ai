<?php

declare(strict_types=1);

namespace App\Actions\Listening;

use App\Models\Listening\ListeningTest;
use App\Repositories\Listening\ListeningSectionRepository;
use App\Support\Listening\ListeningSectionMap;
use Illuminate\Support\Facades\DB;

class CreateDefaultListeningSectionsAction
{
    public function __construct(
        private readonly ListeningSectionRepository $sections,
    ) {}

    /**
     * @return array{created: int, skipped: int}
     */
    public function execute(ListeningTest $test): array
    {
        return DB::transaction(function () use ($test): array {
            $created = 0;
            $skipped = 0;

            foreach (ListeningSectionMap::sectionRangeMap() as $sectionNumber => $config) {
                if ($this->sections->sectionNumberExists($test, $sectionNumber)) {
                    $skipped++;

                    continue;
                }

                $this->sections->create([
                    'listening_test_id' => $test->id,
                    'section_number' => $sectionNumber,
                    'title' => 'Section '.$sectionNumber,
                    'section_type' => $config['default_type'],
                    'start_question_number' => $config['start'],
                    'end_question_number' => $config['end'],
                    'total_questions' => $config['total'],
                    'display_order' => $sectionNumber,
                    'is_active' => true,
                ]);

                $created++;
            }

            return [
                'created' => $created,
                'skipped' => $skipped,
            ];
        });
    }
}
