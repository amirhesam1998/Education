<?php

namespace Database\Seeders;

use App\Models\Advisor;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionRoleSeeder::class,
            SettingsSeeder::class,
        ]);

        $admin = User::query()->updateOrCreate(
            ['phone' => '09120000000'],
            [
                'name' => 'مدیر کل',
                'email' => 'admin@example.com',
                'password' => 'password',
                'status' => 'active',
            ],
        );

        $admin->assignRole('Super Admin');

        Advisor::query()->firstOrCreate(
            ['name' => 'مشاور پیشفرض'],
            ['phone' => '02100000000', 'description' => 'مشاور انتخاب رشته', 'status' => 'active'],
        );
    }
}
