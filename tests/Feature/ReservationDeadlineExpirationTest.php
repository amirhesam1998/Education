<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ReservationPayment;
use App\Models\ReservationSlot;
use App\Models\Student;
use App\Services\ReservationExpirationService;
use App\Services\SettingsService;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReservationDeadlineExpirationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingsSeeder::class);
    }

    #[Test]
    public function a_missing_reservation_deadline_uses_the_default_and_expires(): void
    {
        app(SettingsService::class)->set('default_payment_deadline_minutes', 1, 'integer');
        $reservation = $this->reservation();

        $this->assertSame(1, app(ReservationExpirationService::class)->expireOverdueReservations());
        $this->assertSame(ReservationStatus::Expired, $reservation->refresh()->status);
    }

    #[Test]
    public function an_approved_payment_is_never_expired(): void
    {
        app(SettingsService::class)->set('default_payment_deadline_minutes', 1, 'integer');
        $reservation = $this->reservation();
        ReservationPayment::query()->create([
            'reservation_id' => $reservation->id,
            'status' => PaymentStatus::Approved,
        ]);

        $this->assertSame(0, app(ReservationExpirationService::class)->expireOverdueReservations());
        $this->assertSame(ReservationStatus::PendingPrepayment, $reservation->refresh()->status);
    }

    private function reservation(): Reservation
    {
        $time = now()->subMinutes(2);

        $reservation = Reservation::query()->create([
            'student_id' => Student::factory()->create()->id,
            'slot_id' => ReservationSlot::factory()->create()->id,
            'status' => ReservationStatus::PendingPrepayment,
            'prepayment_required' => true,
            'prepayment_amount' => 1000,
            'public_token' => str()->random(80),
            'public_token_expires_at' => now()->addDay(),
        ]);

        $reservation->forceFill(['created_at' => $time, 'updated_at' => $time])->saveQuietly();

        return $reservation;
    }
}
