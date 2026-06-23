<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            SuperAdminSeeder::class,
            DemoCoursePackageSeeder::class,
            DemoReadingTestSeeder::class,
            ProQyzStyleReadingSeeder::class,
        ]);
    }
}
