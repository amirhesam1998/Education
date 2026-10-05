<?php

namespace Tests\Feature;

use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ReservationSlot;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicReservationUploadTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function student_can_upload_report_card_through_public_link(): void
    {
        Storage::fake('local');
        $reservation = $this->reservation();

        $this->withoutExceptionHandling()
            ->from(route('public.reservations.show', $reservation->public_token))
            ->post(route('public.reservations.report-card.store', $reservation->public_token), [
                'report_card' => UploadedFile::fake()->image('karname.jpg'),
            ])
            ->assertRedirect(route('public.reservations.show', $reservation->public_token))
            ->assertSessionHas('success');

        $this->assertCount(1, $reservation->reportCards()->get());

        $this->withoutExceptionHandling()
            ->get(route('public.reservations.show', $reservation->public_token))
            ->assertOk();
    }

    #[Test]
    public function student_can_upload_payment_receipt_through_public_link(): void
    {
        Storage::fake('local');
        $reservation = $this->reservation([
            'status' => ReservationStatus::PendingPrepayment,
            'prepayment_required' => true,
            'prepayment_amount' => 500000,
            'payment_deadline_at' => now()->addHour(),
        ]);
        $reservation->student->forceFill(['major' => 'تجربی', 'region' => Student::REGION_ONE, 'score' => '12000'])->save();
        $reservation->student->phones()->create(['phone' => '09120000000']);

        $this->withoutExceptionHandling()
            ->from(route('public.reservations.show', $reservation->public_token))
            ->post(route('public.reservations.upload-receipt', $reservation->public_token), [
                'receipt_image' => UploadedFile::fake()->image('fish.jpg'),
            ])
            ->assertRedirect(route('public.reservations.show', $reservation->public_token))
            ->assertSessionHasNoErrors();

        $this->assertSame(ReservationStatus::PendingPaymentApproval, $reservation->refresh()->status);

        $this->withoutExceptionHandling()
            ->get(route('public.reservations.show', $reservation->public_token))
            ->assertOk();
    }

    #[Test]
    public function unwritable_storage_returns_a_form_error_instead_of_a_server_error(): void
    {
        config(['filesystems.disks.local.root' => '/proc/unwritable-upload-root']);
        app('filesystem')->forgetDisk('local');
        $reservation = $this->reservation();

        $this->from(route('public.reservations.show', $reservation->public_token))
            ->post(route('public.reservations.report-card.store', $reservation->public_token), [
                'report_card' => UploadedFile::fake()->image('karname.jpg'),
            ])
            ->assertRedirect(route('public.reservations.show', $reservation->public_token))
            ->assertSessionHasErrors('report_card');

        $this->assertCount(0, $reservation->reportCards()->get());
    }

    private function reservation(array $overrides = []): Reservation
    {
        $student = Student::factory()->create(['full_name' => 'دانش‌آموز آزمایشی']);

        return Reservation::query()->create([
            ...[
                'student_id' => $student->id,
                'slot_id' => ReservationSlot::factory()->create()->id,
                'status' => ReservationStatus::Confirmed,
                'public_token' => str()->random(80),
                'public_token_expires_at' => now()->addDay(),
            ],
            ...$overrides,
        ])->load('student');
    }
}
