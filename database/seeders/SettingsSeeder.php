<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Services\Admin\SettingsService;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        app(SettingsService::class)->seedDefaults();
    }
}
