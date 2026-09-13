<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Enums\SlotStatus;
use App\Models\Advisor;
use App\Models\Reservation;
use App\Models\ReservationSlot;
use App\Models\Student;
use App\Models\User;
use App\Services\SlotAvailabilityService;
use Database\Seeders\PermissionRoleSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReservationSlotConsultantTest extends TestCase
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
    public function consultant_users_appear_and_regular_users_do_not(): void
    {
        $admin = $this->admin();
        [$consultant, $advisor] = $this->consultant(['name' => 'Consultant One']);
        $regular = User::factory()->create(['name' => 'Regular User']);
        Advisor::factory()->create(['user_id' => $regular->id, 'name' => $regular->name]);

        $this->actingAs($admin)
            ->get(route('admin.slots.create'))
            ->assertOk()
            ->assertSee($consultant->name)
            ->assertSee('value="'.$advisor->id.'"', false)
            ->assertDontSee($regular->name);
    }

    #[Test]
    public function selected_consultant_is_saved_and_selected_on_edit(): void
    {
        $admin = $this->admin();
        [, $advisor] = $this->consultant(['name' => 'Consultant One']);

        $this->actingAs($admin)
            ->post(route('admin.slots.store'), $this->slotPayload($advisor))
            ->assertRedirect(route('admin.slots.index'));

        $slot = ReservationSlot::query()->firstOrFail();
        $this->assertSame($advisor->id, $slot->advisor_id);

        $this->actingAs($admin)
            ->get(route('admin.slots.edit', $slot))
            ->assertOk()
            ->assertSee('value="'.$advisor->id.'" selected', false);
    }

    #[Test]
    public function non_consultant_advisor_id_fails_validation(): void
    {
        $admin = $this->admin();
        $regular = User::factory()->create(['name' => 'Regular User']);
        $advisor = Advisor::factory()->create(['user_id' => $regular->id, 'name' => $regular->name]);

        $this->actingAs($admin)
            ->post(route('admin.slots.store'), $this->slotPayload($advisor))
            ->assertSessionHasErrors('advisor_id');

        $this->assertDatabaseCount('reservation_slots', 0);
    }

    #[Test]
    public function changing_the_consultant_on_edit_is_persisted(): void
    {
        $admin = $this->admin();
        [, $firstAdvisor] = $this->consultant(['name' => 'First Consultant']);
        [, $secondAdvisor] = $this->consultant(['name' => 'Second Consultant']);
        $slot = ReservationSlot::factory()->create(['advisor_id' => $firstAdvisor->id]);

        $this->actingAs($admin)
            ->put(route('admin.slots.update', $slot), $this->slotPayload($secondAdvisor))
            ->assertRedirect(route('admin.slots.index'));

        $this->assertSame($secondAdvisor->id, $slot->refresh()->advisor_id);
    }

    #[Test]
    public function created_slot_appears_in_the_selected_consultants_schedule(): void
    {
        $admin = $this->admin();
        [$consultant, $advisor] = $this->consultant(['name' => 'Schedule Consultant']);

        $this->actingAs($admin)
            ->post(route('admin.slots.store'), $this->slotPayload($advisor))
            ->assertRedirect(route('admin.slots.index'));

        $this->actingAs($admin)
            ->get(route('admin.slots.index', ['advisor_id' => $advisor->id]))
            ->assertOk()
            ->assertSee($consultant->name);
    }

    #[Test]
    public function slot_index_exposes_edit_and_delete_actions(): void
    {
        $admin = $this->admin();
        [, $advisor] = $this->consultant(['name' => 'Editable Consultant']);
        $slot = ReservationSlot::factory()->create([
            'advisor_id' => $advisor->id,
            'date' => $this->slotDate(),
            'start_time' => '10:00',
            'end_time' => '10:15',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.slots.index', ['date' => $this->slotDate()]))
            ->assertOk()
            ->assertSee(route('admin.slots.show', $slot), false)
            ->assertSee(route('admin.slots.edit', $slot), false)
            ->assertSee(route('admin.slots.destroy', $slot), false)
            ->assertSee('ویرایش تایم')
            ->assertSee('حذف تایم');
    }

    #[Test]
    public function slot_without_active_reservations_can_be_deleted(): void
    {
        $admin = $this->admin();
        [, $advisor] = $this->consultant(['name' => 'Delete Consultant']);
        $slot = ReservationSlot::factory()->create(['advisor_id' => $advisor->id]);

        $this->actingAs($admin)
            ->delete(route('admin.slots.destroy', $slot))
            ->assertRedirect(route('admin.slots.index'))
            ->assertSessionHas('success', 'تایم حذف شد.');

        $this->assertModelMissing($slot);
    }

    #[Test]
    public function slot_with_active_reservation_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        [, $advisor] = $this->consultant(['name' => 'Locked Consultant']);
        $slot = ReservationSlot::factory()->create([
            'advisor_id' => $advisor->id,
            'start_time' => '10:00',
            'end_time' => '10:15',
        ]);

        Reservation::query()->create([
            'student_id' => Student::factory()->create()->id,
            'slot_id' => $slot->id,
            'advisor_id' => $advisor->id,
            'reserved_start_time' => '10:00',
            'reserved_end_time' => '10:15',
            'status' => ReservationStatus::PendingCompletion,
            'prepayment_required' => false,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.slots.destroy', $slot))
            ->assertSessionHasErrors('slot');

        $this->assertModelExists($slot);
    }

    #[Test]
    public function slots_can_end_at_midnight_and_generate_the_last_interval(): void
    {
        $admin = $this->admin();
        [, $advisor] = $this->consultant(['name' => 'Midnight Consultant']);

        $this->actingAs($admin)
            ->post(route('admin.slots.store'), $this->slotPayload($advisor, [
                'start_time' => '08:00',
                'end_time' => '00:00',
            ]))
            ->assertRedirect(route('admin.slots.index'));

        $slot = ReservationSlot::query()->firstOrFail();
        $this->assertSame('00:00', substr($slot->end_time, 0, 5));
        $this->assertSame('23:45|00:00', app(SlotAvailabilityService::class)->generateIntervalsForSlot($slot)->last()['value']);
    }

    #[Test]
    public function slot_creation_rejects_reverse_ranges_and_converts_jalali_dates(): void
    {
        $admin = $this->admin();
        [, $advisor] = $this->consultant(['name' => 'Jalali Slot Consultant']);

        $this->actingAs($admin)
            ->post(route('admin.slots.store'), $this->slotPayload($advisor, [
                'date' => '1404/01/01',
                'start_time' => '18:00',
                'end_time' => '17:00',
            ]))
            ->assertSessionHasErrors('end_time');

        $this->actingAs($admin)
            ->post(route('admin.slots.store'), $this->slotPayload($advisor, [
                'date' => '1404/01/01',
                'start_time' => '08:00',
                'end_time' => '00:00',
            ]))
            ->assertRedirect(route('admin.slots.index'));

        $this->assertDatabaseHas('reservation_slots', [
            'advisor_id' => $advisor->id,
            'date' => '2025-03-21',
            'end_time' => '00:00',
        ]);
    }

    #[Test]
    public function day_bulk_delete_only_removes_slots_without_history(): void
    {
        $admin = $this->admin();
        [, $advisor] = $this->consultant(['name' => 'Bulk Delete Consultant']);
        $free = ReservationSlot::factory()->create(['advisor_id' => $advisor->id, 'date' => $this->slotDate()]);
        $reserved = ReservationSlot::factory()->create(['advisor_id' => $advisor->id, 'date' => $this->slotDate(), 'start_time' => '11:00', 'end_time' => '11:15']);
        Reservation::query()->create([
            'student_id' => Student::factory()->create()->id,
            'slot_id' => $reserved->id,
            'advisor_id' => $advisor->id,
            'reserved_start_time' => '11:00',
            'reserved_end_time' => '11:15',
            'status' => ReservationStatus::Confirmed,
            'prepayment_required' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.slots.bulk-delete-day'), ['date' => $this->slotDate(), 'advisor_id' => $advisor->id, 'action' => 'delete'])
            ->assertRedirect(route('admin.slots.index'));

        $this->assertModelMissing($free);
        $this->assertModelExists($reserved);
    }

    #[Test]
    public function day_bulk_actions_convert_jalali_dates_before_querying_slots(): void
    {
        $admin = $this->admin();
        [, $advisor] = $this->consultant(['name' => 'Jalali Bulk Consultant']);
        $slot = ReservationSlot::factory()->create([
            'advisor_id' => $advisor->id,
            'date' => '2025-03-21',
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.slots.day-deletion-preview'), [
                'date' => '1404/01/01',
                'advisor_id' => $advisor->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('date', '2025-03-21')
            ->assertJsonPath('deletable', 1);

        $this->actingAs($admin)
            ->postJson(route('admin.slots.bulk-delete-day'), [
                'date' => '1404/01/01',
                'advisor_id' => $advisor->id,
                'action' => 'delete',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('deleted_count', 1)
            ->assertJsonPath('blocked_count', 0);

        $this->assertModelMissing($slot);
    }

    #[Test]
    public function day_bulk_preview_returns_a_persian_validation_message_for_an_invalid_date(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson(route('admin.slots.day-deletion-preview'), ['date' => 'not-a-date'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.date.0', 'تاریخ انتخاب‌شده معتبر نیست.');
    }

    #[Test]
    public function day_bulk_deactivation_keeps_slots_with_active_reservations_active(): void
    {
        $admin = $this->admin();
        [, $advisor] = $this->consultant(['name' => 'Protected Bulk Consultant']);
        $free = ReservationSlot::factory()->create(['advisor_id' => $advisor->id, 'date' => $this->slotDate()]);
        $reserved = ReservationSlot::factory()->create(['advisor_id' => $advisor->id, 'date' => $this->slotDate(), 'start_time' => '11:00', 'end_time' => '11:15']);
        Reservation::query()->create([
            'student_id' => Student::factory()->create()->id,
            'slot_id' => $reserved->id,
            'advisor_id' => $advisor->id,
            'reserved_start_time' => '11:00',
            'reserved_end_time' => '11:15',
            'status' => ReservationStatus::Confirmed,
            'prepayment_required' => false,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.slots.bulk-delete-day'), [
                'date' => $this->slotDate(),
                'advisor_id' => $advisor->id,
                'action' => 'deactivate',
            ])
            ->assertRedirect(route('admin.slots.index'));

        $this->assertSame(SlotStatus::Inactive, $free->refresh()->status);
        $this->assertSame(SlotStatus::Active, $reserved->refresh()->status);
    }

    #[Test]
    public function same_consultant_cannot_have_overlapping_slots_but_different_consultants_can(): void
    {
        $admin = $this->admin();
        [, $firstAdvisor] = $this->consultant(['name' => 'First Consultant']);
        [, $secondAdvisor] = $this->consultant(['name' => 'Second Consultant']);
        ReservationSlot::factory()->create([
            'advisor_id' => $firstAdvisor->id,
            'date' => $this->slotDate(),
            'start_time' => '10:00',
            'end_time' => '11:00',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.slots.store'), $this->slotPayload($firstAdvisor, [
                'start_time' => '10:30',
                'end_time' => '11:30',
            ]))
            ->assertSessionHasErrors('start_time');

        $this->actingAs($admin)
            ->post(route('admin.slots.store'), $this->slotPayload($secondAdvisor, [
                'start_time' => '10:30',
                'end_time' => '11:30',
            ]))
            ->assertRedirect(route('admin.slots.index'));

        $this->assertDatabaseHas('reservation_slots', [
            'advisor_id' => $secondAdvisor->id,
            'date' => $this->slotDate(),
            'start_time' => '10:30',
            'end_time' => '11:30',
        ]);
    }

    #[Test]
    public function reservations_still_use_the_selected_consultants_availability(): void
    {
        $admin = $this->admin();
        [, $advisor] = $this->consultant(['name' => 'Reservation Consultant']);
        $slot = ReservationSlot::factory()->create([
            'advisor_id' => $advisor->id,
            'date' => $this->slotDate(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'capacity' => 1,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.reservations.store'), [
                'phone_one' => '09120000000',
                'slot_id' => $slot->id,
                'reservation_interval' => '10:00|10:15',
                'prepayment_required' => false,
            ])
            ->assertRedirect();

        $reservation = Reservation::query()->firstOrFail();
        $this->assertSame($advisor->id, $reservation->advisor_id);

        $this->actingAs($admin)
            ->post(route('admin.reservations.store'), [
                'phone_one' => '09120000001',
                'slot_id' => $slot->id,
                'reservation_interval' => '10:00|10:15',
                'prepayment_required' => false,
            ])
            ->assertSessionHasErrors('reservation_interval');

        $this->assertFalse(app(SlotAvailabilityService::class)->isIntervalAvailable($slot, '10:00', '10:15'));
        $this->assertSame(ReservationStatus::PendingCompletion, $reservation->refresh()->status);
    }

    #[Test]
    public function unauthorized_users_cannot_configure_slots(): void
    {
        $user = User::factory()->create();
        [, $advisor] = $this->consultant(['name' => 'Consultant One']);

        $this->actingAs($user)
            ->post(route('admin.slots.store'), $this->slotPayload($advisor))
            ->assertForbidden();
    }

    /**
     * @return array{0: User, 1: Advisor}
     */
    private function consultant(array $attributes = []): array
    {
        $user = User::factory()->create($attributes);
        $user->assignRole(User::ROLE_CONSULTANT);

        return [$user, Advisor::syncForUser($user)];
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }

    private function slotPayload(Advisor $advisor, array $overrides = []): array
    {
        return $overrides + [
            'mode' => 'single',
            'advisor_id' => $advisor->id,
            'date' => $this->slotDate(),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'duration_minutes' => 15,
            'capacity' => 1,
            'status' => SlotStatus::Active->value,
        ];
    }

    private function slotDate(): string
    {
        return now()->addDays(3)->toDateString();
    }
}
