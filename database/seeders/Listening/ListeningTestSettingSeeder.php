<?php

declare(strict_types=1);

namespace Database\Seeders\Listening;

use App\Models\Listening\ListeningTest;
use App\Models\Listening\ListeningTestSetting;
use Illuminate\Database\Seeder;

class ListeningTestSettingSeeder extends Seeder
{
    public function run(): void
    {
        ListeningTest::query()->each(function (ListeningTest $test): void {
            if ($test->setting()->exists()) {
                return;
            }

            $test->setting()->create(ListeningTestSetting::officialDefaults());
        });
    }
}
