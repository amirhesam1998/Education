<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ReservationPayment;
use App\Models\ReservationSlot;
use App\Models\Student;
use App\Models\StudentPhone;
use App\Models\User;
use App\Support\PersianDate;
use Database\Seeders\PermissionRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReservationSensitiveAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionRoleSeeder::class);
    }

    #[Test]
    public function consultant_without_sensitive_permissions_cannot_see_or_access_sensitive_reservation_data(): void
    {
        [$reservation, $payment] = $this->reservationWithPayment();
        $consultant = User::factory()->create();
        $consultant->assignRole(User::ROLE_CONSULTANT);

        $this->actingAs($consultant)
            ->get(route('admin.reservations.show', $reservation))
            ->assertOk()
            ->assertDontSee('دانش‌آموز محرمانه')
            ->assertDontSee('09120000000')
            ->assertDontSee($reservation->public_token)
            ->assertDontSee(PersianDate::money(450000))
            ->assertSee('شما دسترسی مشاهده اطلاعات شخصی دانش‌آموز را ندارید.')
            ->assertSee('شما دسترسی مشاهده اطلاعات پرداخت را ندارید.')
            ->assertSee('شما دسترسی مشاهده لینک دانش‌آموز را ندارید.');

        $this->get(route('admin.reservations.receipt', $reservation))->assertForbidden();
        $this->get(route('admin.payments.receipt', $payment))->assertForbidden();
        $this->post(route('admin.reservations.regenerate-link', $reservation))->assertForbidden();
    }

    #[Test]
    public function granular_permissions_enable_only_the_related_sensitive_sections(): void
    {
        [$reservation, $payment] = $this->reservationWithPayment();
        Storage::fake('local');
        Storage::disk('local')->put('receipts/test.jpg', 'receipt');

        $user = User::factory()->create();
        $user->assignRole(User::ROLE_CONSULTANT);
        $user->givePermissionTo([
            'view_reservation_sensitive_info',
            'view_student_personal_data',
            'view_reservation_payment_info',
            'view_prepayment_receipts',
            'view_student_public_link',
        ]);

        $this->actingAs($user)
            ->get(route('admin.reservations.show', $reservation))
            ->assertOk()
            ->assertSee('دانش‌آموز محرمانه')
            ->assertSee('09120000000')
            ->assertSee($reservation->public_token)
            ->assertSee(PersianDate::money(450000));

        $this->assertTrue($user->can('view_prepayment_receipts'));
        $this->actingAs($user)->get(route('admin.reservations.receipt', $reservation))->assertOk();
        $this->actingAs($user)->get(route('admin.payments.receipt', $payment))->assertOk();
    }

    #[Test]
    public function accountant_can_access_payment_and_receipt_without_student_personal_data(): void
    {
        [$reservation] = $this->reservationWithPayment();
        $accountant = User::factory()->create();
        $accountant->assignRole('Accountant');

        $this->actingAs($accountant)
            ->get(route('admin.payments.show', $reservation->payment))
            ->assertOk()
            ->assertDontSee('دانش‌آموز محرمانه')
            ->assertSee(PersianDate::money(450000));
    }

    /** @return array{Reservation, ReservationPayment} */
    private function reservationWithPayment(): array
    {
        $student = Student::factory()->create(['full_name' => 'دانش‌آموز محرمانه']);
        StudentPhone::query()->create(['student_id' => $student->id, 'phone' => '09120000000', 'is_primary' => true]);
        $reservation = Reservation::query()->create([
            'student_id' => $student->id,
            'slot_id' => ReservationSlot::factory()->create()->id,
            'status' => ReservationStatus::PendingPaymentApproval,
            'prepayment_required' => true,
            'prepayment_amount' => 450000,
            'public_token' => str()->random(80),
            'public_token_expires_at' => now()->addDay(),
        ]);
        $payment = ReservationPayment::query()->create([
            'reservation_id' => $reservation->id,
            'amount' => 450000,
            'status' => PaymentStatus::PendingApproval,
            'receipt_image_path' => 'receipts/test.jpg',
            'uploaded_at' => now(),
        ]);

        return [$reservation, $payment];
    }
}
