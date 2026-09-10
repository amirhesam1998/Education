<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\PaymentCard;
use App\Models\Reservation;
use App\Models\ReservationSlot;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\PermissionRoleSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReservationPrepaymentValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            PermissionRoleSeeder::class,
            SettingsSeeder::class,
        ]);
    }

    #[Test]
    #[DataProvider('disabledPrepaymentPayloads')]
    public function creating_a_reservation_with_prepayment_disabled_succeeds_and_drops_prepayment_values(array $overrides): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.reservations.store'), $this->payload(ReservationSlot::factory()->create(), $overrides))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $reservation = Reservation::query()->with('payment')->firstOrFail();

        $this->assertFalse($reservation->prepayment_required);
        $this->assertNull($reservation->prepayment_amount);
        $this->assertNull($reservation->payment_card_id);
        $this->assertNull($reservation->payment_deadline_at);
        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
        $this->assertSame(PaymentStatus::NotRequired, $reservation->payment->status);
        $this->assertNull($reservation->payment->amount);
    }

    public static function disabledPrepaymentPayloads(): array
    {
        return [
            'missing checkbox and no amount' => [[]],
            'missing checkbox and empty amount' => [['prepayment_amount' => '']],
            'missing checkbox and zero amount' => [['prepayment_amount' => 0]],
            'explicit zero checkbox' => [['prepayment_required' => '0', 'prepayment_amount' => 0]],
            'string false checkbox' => [['prepayment_required' => 'false', 'prepayment_amount' => 0, 'payment_card_id' => 999999, 'payment_deadline_at' => 'not-a-date']],
        ];
    }

    #[Test]
    public function creating_a_reservation_with_prepayment_enabled_requires_an_amount(): void
    {
        $card = $this->paymentCard();

        $this->actingAs($this->admin())
            ->post(route('admin.reservations.store'), $this->payload(ReservationSlot::factory()->create(), [
                'prepayment_required' => '1',
                'payment_card_id' => $card->id,
                'payment_deadline_at' => now()->addDay()->format('Y-m-d H:i'),
            ]))
            ->assertSessionHasErrors('prepayment_amount');

        $this->assertDatabaseCount('reservations', 0);
    }

    #[Test]
    public function creating_a_reservation_with_prepayment_enabled_rejects_amounts_below_the_minimum(): void
    {
        $card = $this->paymentCard();
        $minimum = app(SettingsService::class)->minimumPrepaymentAmount();

        $this->actingAs($this->admin())
            ->post(route('admin.reservations.store'), $this->payload(ReservationSlot::factory()->create(), [
                'prepayment_required' => '1',
                'prepayment_amount' => $minimum - 1,
                'payment_card_id' => $card->id,
                'payment_deadline_at' => now()->addDay()->format('Y-m-d H:i'),
            ]))
            ->assertSessionHasErrors('prepayment_amount');

        $this->assertDatabaseCount('reservations', 0);
    }

    #[Test]
    public function creating_a_reservation_with_prepayment_enabled_and_valid_fields_succeeds(): void
    {
        $card = $this->paymentCard();
        $minimum = app(SettingsService::class)->minimumPrepaymentAmount();

        $this->actingAs($this->admin())
            ->post(route('admin.reservations.store'), $this->payload(ReservationSlot::factory()->create(), [
                'prepayment_required' => 'true',
                'prepayment_amount' => $minimum,
                'payment_card_id' => $card->id,
                'payment_deadline_at' => now()->addDay()->format('Y-m-d H:i'),
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $reservation = Reservation::query()->with('payment')->firstOrFail();

        $this->assertTrue($reservation->prepayment_required);
        $this->assertSame($minimum, $reservation->prepayment_amount);
        $this->assertSame($card->id, $reservation->payment_card_id);
        $this->assertSame(ReservationStatus::PendingPrepayment, $reservation->status);
        $this->assertSame(PaymentStatus::PendingUpload, $reservation->payment->status);
    }

    #[Test]
    public function payment_card_validation_only_runs_when_prepayment_is_enabled(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.reservations.store'), $this->payload(ReservationSlot::factory()->create(), [
                'prepayment_required' => 'false',
                'prepayment_amount' => 0,
                'payment_card_id' => 999999,
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin())
            ->post(route('admin.reservations.store'), $this->payload(ReservationSlot::factory()->create(), [
                'prepayment_required' => '1',
                'prepayment_amount' => app(SettingsService::class)->minimumPrepaymentAmount(),
                'payment_card_id' => 999999,
                'payment_deadline_at' => now()->addDay()->format('Y-m-d H:i'),
            ]))
            ->assertSessionHasErrors('payment_card_id');
    }

    #[Test]
    public function prepayment_deadline_defaults_when_the_admin_does_not_set_one(): void
    {
        $card = $this->paymentCard();

        $reservation = $this->createReservation([
            'prepayment_required' => '1',
            'prepayment_amount' => app(SettingsService::class)->minimumPrepaymentAmount(),
            'payment_card_id' => $card->id,
            'payment_deadline_at' => '',
        ]);

        $this->assertTrue($reservation->prepayment_required);
        $this->assertNotNull($reservation->payment_deadline_at);
        $this->assertTrue($reservation->payment_deadline_at->between(now()->addMinutes(1439), now()->addMinutes(1441)));
        $this->assertSame(ReservationStatus::PendingPrepayment, $reservation->status);
    }

    #[Test]
    public function overdue_unpaid_prepayment_reservation_expires_and_releases_its_slot(): void
    {
        $slot = ReservationSlot::factory()->create(['capacity' => 1]);
        $student = \App\Models\Student::factory()->create();
        $reservation = Reservation::query()->create([
            'student_id' => $student->id,
            'slot_id' => $slot->id,
            'advisor_id' => $slot->advisor_id,
            'reserved_start_time' => '10:00',
            'reserved_end_time' => '10:15',
            'status' => ReservationStatus::PendingPrepayment,
            'prepayment_required' => true,
            'prepayment_amount' => app(SettingsService::class)->minimumPrepaymentAmount(),
            'payment_deadline_at' => now()->subMinute(),
            'public_token' => str()->random(80),
            'public_token_expires_at' => now()->addDay(),
        ]);

        $this->assertFalse(app(\App\Services\SlotAvailabilityService::class)->isIntervalAvailable($slot, '10:00', '10:15'));
        $this->assertSame(1, app(\App\Services\ReservationExpirationService::class)->expireOverdueReservations());

        $this->assertSame(ReservationStatus::Expired, $reservation->refresh()->status);
        $this->assertTrue(app(\App\Services\SlotAvailabilityService::class)->isIntervalAvailable($slot, '10:00', '10:15'));
    }

    #[Test]
    public function the_student_receipt_upload_section_only_appears_for_prepayment_reservations(): void
    {
        $disabled = $this->createReservation();
        $card = $this->paymentCard();
        $enabled = $this->createReservation([
            'prepayment_required' => '1',
            'prepayment_amount' => app(SettingsService::class)->minimumPrepaymentAmount(),
            'payment_card_id' => $card->id,
            'payment_deadline_at' => now()->addDay()->format('Y-m-d H:i'),
        ]);

        $this->get(route('public.reservations.show', $disabled->public_token))
            ->assertOk()
            ->assertDontSee('name="receipt_image"', false);

        $this->get(route('public.reservations.show', $enabled->public_token))
            ->assertOk()
            ->assertSee('name="receipt_image"', false);
    }

    #[Test]
    public function editing_a_reservation_from_prepayment_enabled_to_disabled_clears_obsolete_payment_data(): void
    {
        $card = $this->paymentCard();
        $reservation = $this->createReservation([
            'prepayment_required' => '1',
            'prepayment_amount' => app(SettingsService::class)->minimumPrepaymentAmount(),
            'payment_card_id' => $card->id,
            'payment_deadline_at' => now()->addDay()->format('Y-m-d H:i'),
        ]);

        $reservation->payment->forceFill([
            'receipt_image_path' => 'receipts/old.jpg',
            'uploaded_at' => now(),
            'status' => PaymentStatus::PendingApproval,
        ])->save();

        $this->actingAs($this->admin())
            ->put(route('admin.reservations.update', $reservation), $this->payload($reservation->slot, [
                'prepayment_required' => '0',
                'prepayment_amount' => 0,
                'payment_card_id' => 999999,
                'payment_deadline_at' => 'not-a-date',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $reservation->refresh()->load('payment');

        $this->assertFalse($reservation->prepayment_required);
        $this->assertNull($reservation->prepayment_amount);
        $this->assertNull($reservation->payment_card_id);
        $this->assertNull($reservation->payment_deadline_at);
        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
        $this->assertSame(PaymentStatus::NotRequired, $reservation->payment->status);
        $this->assertNull($reservation->payment->amount);
        $this->assertNull($reservation->payment->receipt_image_path);
    }

    #[Test]
    public function editing_a_reservation_from_disabled_to_enabled_requires_a_valid_amount(): void
    {
        $reservation = $this->createReservation();
        $card = $this->paymentCard();

        $this->actingAs($this->admin())
            ->put(route('admin.reservations.update', $reservation), $this->payload($reservation->slot, [
                'prepayment_required' => '1',
                'payment_card_id' => $card->id,
                'payment_deadline_at' => now()->addDay()->format('Y-m-d H:i'),
            ]))
            ->assertSessionHasErrors('prepayment_amount');
    }

    #[Test]
    public function existing_time_slot_conflict_validation_still_runs(): void
    {
        $slot = ReservationSlot::factory()->create(['capacity' => 1]);

        $this->createReservation([], $slot);

        $this->actingAs($this->admin())
            ->post(route('admin.reservations.store'), $this->payload($slot, [
                'phone_one' => '09121111111',
            ]))
            ->assertSessionHasErrors('reservation_interval');
    }

    #[Test]
    public function existing_disabled_reservations_remain_confirmed_when_updated_without_the_checkbox(): void
    {
        $reservation = $this->createReservation();

        $this->actingAs($this->admin())
            ->put(route('admin.reservations.update', $reservation), $this->payload($reservation->slot, [
                'admin_note' => 'updated',
            ]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $reservation->refresh();

        $this->assertFalse($reservation->prepayment_required);
        $this->assertNull($reservation->prepayment_amount);
        $this->assertSame(ReservationStatus::Confirmed, $reservation->status);
    }

    private function createReservation(array $overrides = [], ?ReservationSlot $slot = null): Reservation
    {
        $this->actingAs($this->admin())
            ->post(route('admin.reservations.store'), $this->payload($slot ?: ReservationSlot::factory()->create(), $overrides))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        return Reservation::query()->with(['slot', 'payment'])->latest('id')->firstOrFail();
    }

    private function payload(ReservationSlot $slot, array $overrides = []): array
    {
        $settings = app(SettingsService::class);

        return array_replace([
            'full_name' => 'Test Student',
            'major' => $settings->get('majors', [])[0] ?? 'General',
            'region' => \App\Models\Student::REGION_ONE,
            'score' => '12000',
            'exam_type' => [$settings->get('exam_types', [])[0] ?? 'General'],
            'phone_one' => '09120000000',
            'slot_id' => $slot->id,
            'reservation_interval' => '10:00|10:15',
        ], $overrides);
    }

    private function paymentCard(): PaymentCard
    {
        return PaymentCard::query()->create([
            'holder_name' => 'Test Holder',
            'card_number' => '1234567890123456',
            'bank_name' => 'Test Bank',
            'is_active' => true,
        ]);
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }
}
