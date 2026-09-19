<?php

namespace Tests\Feature;

use App\Models\Setting;
use Database\Seeders\ProductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductionSeederSafetyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_adds_missing_defaults_without_overwriting_production_configuration(): void
    {
        Setting::query()->create(['key' => 'contact_phone', 'value' => '09121234567', 'type' => 'string']);

        $this->seed(ProductionSeeder::class);
        $this->seed(ProductionSeeder::class);

        $this->assertSame('09121234567', Setting::query()->where('key', 'contact_phone')->value('value'));
        $this->assertSame(1, Setting::query()->where('key', 'contact_phone')->count());
        $this->assertSame(1, Role::query()->where('name', 'Super Admin')->count());
    }
}
