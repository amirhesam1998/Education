<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Enums\SlotStatus;
use App\Models\Advisor;
use App\Models\Reservation;
use App\Models\ReservationSlot;
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
