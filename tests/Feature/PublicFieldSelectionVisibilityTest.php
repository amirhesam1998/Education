<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ReservationSlot;
use App\Models\FieldSelectionPlan;
use App\Models\Student;
use App\Models\User;
use App\Services\FieldSelectionService;
use Database\Seeders\PermissionRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicFieldSelectionVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PermissionRoleSeeder::class);
    }

    #[Test]
    public function a_visible_published_plan_can_be_opened_and_printed_but_a_hidden_plan_cannot(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $reservation = Reservation::query()->create([
            'student_id' => Student::factory()->create()->id,
            'slot_id' => ReservationSlot::factory()->create()->id,
            'status' => ReservationStatus::Confirmed,
            'public_token' => str()->random(80),
            'public_token_expires_at' => now()->addDay(),
        ]);
        $service = app(FieldSelectionService::class);

        $visible = $service->createPlanForReservation($reservation, $user);
        $service->addItem($visible, ['field_code' => '10101', 'field_name' => 'Nursing', 'city' => 'Rasht'], $user);
        $service->publish($visible, $user);
        $service->showToStudent($visible->refresh(), $user);

        $this->get(route('public.reservations.show', $reservation->public_token))
            ->assertOk()
            ->assertSee('مشاهده انتخاب رشته');
        $this->get(route('public.reservations.field-selection.plan.show', [$reservation->public_token, $visible]))
            ->assertOk()
            ->assertSee('10101');
        $hidden = $service->createNewVersionFromExisting($visible->refresh(), $user);
        $service->addItem($hidden, ['field_code' => '20202', 'field_name' => 'Midwifery', 'city' => 'Tehran'], $user);
        $service->publish($hidden, $user);

        $this->get(route('public.reservations.field-selection.plan.show', [$reservation->public_token, $hidden]))
            ->assertNotFound();
        $this->get(route('public.reservations.field-selection.plan.print', [$reservation->public_token, $hidden]))
            ->assertNotFound();
        $this->get(route('public.reservations.show', $reservation->public_token))
            ->assertOk()
            ->assertSee('مشاهده انتخاب رشته');
    }

    #[Test]
    public function public_list_includes_every_published_visible_plan_for_its_reservation_only(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');
        $reservation = $this->reservation();
        $otherReservation = $this->reservation();
        $service = app(FieldSelectionService::class);

        $first = $this->publishedVisiblePlan($reservation, $user, '10101');
        $second = $this->publishedVisiblePlan($reservation, $user, '20202');
        $other = $this->publishedVisiblePlan($otherReservation, $user, '30303');

        $this->get(route('public.reservations.field-selection.show', $reservation->public_token))
            ->assertOk()
            ->assertSee('20202')
            ->assertSee('نسخه‌های قابل مشاهده')
            ->assertDontSee('30303');
        $this->get(route('public.reservations.field-selection.plan.show', [$reservation->public_token, $first]))
            ->assertOk()
            ->assertSee('10101');
        $this->get(route('public.reservations.field-selection.plan.print', [$reservation->public_token, $first]))
            ->assertOk()
            ->assertSee('10101')
            ->assertDontSee('20202');
        $this->get(route('public.reservations.field-selection.plan.show', [$reservation->public_token, $other]))
            ->assertNotFound();

        $this->assertSame(FieldSelectionPlan::STATUS_PUBLISHED, $second->status);
    }

    private function reservation(): Reservation
    {
        return Reservation::query()->create([
            'student_id' => Student::factory()->create()->id,
            'slot_id' => ReservationSlot::factory()->create()->id,
            'status' => ReservationStatus::Confirmed,
            'public_token' => str()->random(80),
            'public_token_expires_at' => now()->addDay(),
        ]);
    }

    private function publishedVisiblePlan(Reservation $reservation, User $user, string $code): FieldSelectionPlan
    {
        $plan = FieldSelectionPlan::query()->create([
            'reservation_id' => $reservation->id,
            'student_id' => $reservation->student_id,
            'version' => FieldSelectionPlan::query()->where('reservation_id', $reservation->id)->max('version') + 1,
            'status' => FieldSelectionPlan::STATUS_DRAFT,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        app(FieldSelectionService::class)->addItem($plan, [
            'field_code' => $code,
            'field_name' => 'Test field '.$code,
            'city' => 'Tehran',
        ], $user);

        $plan->forceFill([
            'status' => FieldSelectionPlan::STATUS_PUBLISHED,
            'is_public_visible' => true,
            'published_at' => now(),
        ])->save();

        return $plan->refresh();
    }
}
