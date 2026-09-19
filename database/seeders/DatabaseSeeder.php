<?php

namespace Database\Seeders;

use App\Models\Advisor;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(ProductionSeeder::class);

        if (app()->environment('production')) {
            return;
        }

        $admin = User::query()->firstOrCreate(
            ['phone' => '09120000000'],
            [
                'name' => 'مدیر کل',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
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
