<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Advisor;
use App\Models\Reservation;
use App\Models\ReservationSlot;
use App\Models\Student;
use App\Models\User;
use App\Services\DashboardService;
use Database\Seeders\PermissionRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DashboardConsultationStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionRoleSeeder::class);
    }

    #[Test]
    public function it_counts_only_completed_reservations_in_the_creator_scope(): void
    {
        $creator = User::factory()->create();
        $otherCreator = User::factory()->create();
        $advisor = Advisor::factory()->create();
        $slot = ReservationSlot::factory()->create(['advisor_id' => $advisor->id]);

        $this->reservation($creator, $advisor, $slot, ReservationStatus::Completed);
        $this->reservation($creator, $advisor, $slot, ReservationStatus::Confirmed);
        $this->reservation($otherCreator, $advisor, $slot, ReservationStatus::Completed);

        $stats = app(DashboardService::class)->getCompletedConsultationStatsForCreator($creator);

        $this->assertSame([[
            'advisor_id' => $advisor->id,
            'advisor_name' => $advisor->name,
            'completed_count' => 1,
        ]], $stats);
    }

    private function reservation(User $creator, Advisor $advisor, ReservationSlot $slot, ReservationStatus $status): void
    {
        Reservation::query()->create([
            'student_id' => Student::factory()->create()->id,
            'advisor_id' => $advisor->id,
            'slot_id' => $slot->id,
            'created_by' => $creator->id,
            'status' => $status,
            'public_token' => str()->random(80),
            'public_token_expires_at' => now()->addDay(),
        ]);
    }
}
