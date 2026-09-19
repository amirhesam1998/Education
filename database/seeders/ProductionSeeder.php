<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * The only seeder intended for normal production deployments.
 * It adds missing reference configuration and never resets existing data.
 */
class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionRoleSeeder::class,
            SettingsSeeder::class,
        ]);
    }
}
