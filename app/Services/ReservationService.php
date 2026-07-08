<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ReservationPayment;
use App\Models\ReservationSlot;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ReservationService
{
    public function __construct(
        private readonly SlotAvailabilityService $slotAvailability,
        private readonly PublicReservationLinkService $publicLink,
        private readonly ActivityLogService $activityLog,
        private readonly SettingsService $settings,
    ) {
    }

    public function createFromAdmin(array $data): Reservation
    {
        /** @var User|null $user */
        $user = auth()->user();

        return DB::transaction(function () use ($data, $user): Reservation {
            $slot = ReservationSlot::query()->whereKey($data['slot_id'])->lockForUpdate()->firstOrFail();
            $this->slotAvailability->assertIntervalAvailable($slot, $data['reserved_start_time'], $data['reserved_end_time']);

            $student = Student::query()->create(Arr::only($data, [
                'full_name',
                'major',
                'score',
                'exam_type',
            ]));

            $this->syncPhones($student, $data);

            $prepaymentRequired = (bool) ($data['prepayment_required'] ?? false);

            $reservation = Reservation::query()->create([
                'student_id' => $student->id,
                'slot_id' => $slot->id,
                'advisor_id' => $slot->advisor_id,
                'reserved_start_time' => $data['reserved_start_time'],
                'reserved_end_time' => $data['reserved_end_time'],
                'status' => ReservationStatus::Draft,
                'prepayment_required' => $prepaymentRequired,
                'prepayment_amount' => $prepaymentRequired ? $data['prepayment_amount'] : null,
                'payment_deadline_at' => $prepaymentRequired ? $data['payment_deadline_at'] : null,
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
                'admin_note' => $data['admin_note'] ?? null,
            ]);

            $this->syncPaymentRequirement($reservation);
            $this->advanceStatus($reservation->refresh()->load('student.phones', 'payment'));
            $this->publicLink->generate($reservation);
            $this->activityLog->log('reservation_created', $reservation, $user);

            return $reservation->load(['student.phones', 'slot.advisor', 'payment']);
        });
    }

    public function updateFromAdmin(Reservation $reservation, array $data): Reservation
    {
        /** @var User|null $user */
        $user = auth()->user();

        return DB::transaction(function () use ($reservation, $data, $user): Reservation {
            $reservation = Reservation::query()
                ->with(['student.phones', 'payment', 'slot'])
                ->whereKey($reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $old = $reservation->getOriginal();

            $reservation->student->update(Arr::only($data, [
                'full_name',
                'major',
                'score',
                'exam_type',
            ]));

            $this->syncPhones($reservation->student, $data);
            $this->slotAvailability->assertIntervalAvailable($reservation->slot, $data['reserved_start_time'], $data['reserved_end_time'], $reservation);

            $prepaymentRequired = (bool) ($data['prepayment_required'] ?? false);
            $reservation->fill([
                'reserved_start_time' => $data['reserved_start_time'],
                'reserved_end_time' => $data['reserved_end_time'],
                'prepayment_required' => $prepaymentRequired,
                'prepayment_amount' => $prepaymentRequired ? ($data['prepayment_amount'] ?? null) : null,
                'payment_deadline_at' => $prepaymentRequired ? ($data['payment_deadline_at'] ?? null) : null,
                'updated_by' => $user?->id,
                'admin_note' => $data['admin_note'] ?? null,
            ])->save();

            $this->syncPaymentRequirement($reservation);

            if (! $reservation->isTerminal()) {
                $this->advanceStatus($reservation->refresh()->load('student.phones', 'payment'));
            }

            $this->activityLog->log('reservation_updated', $reservation, $user, $old, $reservation->getChanges());

            return $reservation->load(['student.phones', 'slot.advisor', 'payment']);
        });
    }

    public function updateStudentMissingFields(Reservation $reservation, array $data): Reservation
    {
        return DB::transaction(function () use ($reservation, $data): Reservation {
            $reservation = Reservation::query()
                ->with(['student.phones', 'payment'])
                ->whereKey($reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            foreach (['full_name', 'major', 'score', 'exam_type'] as $field) {
                if (blank($reservation->student->{$field}) && array_key_exists($field, $data)) {
                    $reservation->student->{$field} = $data[$field];
                }
            }

            $reservation->student->save();

            if (! $reservation->student->phones()->exists() && filled($data['phone_one'] ?? null)) {
                $reservation->student->phones()->create([
                    'phone' => $data['phone_one'],
                    'label' => 'شماره اول',
                    'is_primary' => true,
                ]);
            }

            if (filled($data['phone_two'] ?? null) && $reservation->student->phones()->count() < 2) {
                $reservation->student->phones()->create([
                    'phone' => $data['phone_two'],
                    'label' => 'شماره دوم',
                    'is_primary' => false,
                ]);
            }

            $this->advanceStatus($reservation->refresh()->load('student.phones', 'payment'));
            $this->activityLog->log('student_completed_missing_fields', $reservation);

            return $reservation;
        });
    }

    public function cancel(Reservation $reservation, User $user, ?string $reason = null): Reservation
    {
        return DB::transaction(function () use ($reservation, $user, $reason): Reservation {
            $reservation = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            $reservation->forceFill([
                'status' => ReservationStatus::Cancelled,
                'cancelled_at' => now(),
                'updated_by' => $user->id,
                'admin_note' => trim(($reservation->admin_note ? $reservation->admin_note."\n" : '').($reason ?? '')),
            ])->save();

            $this->activityLog->log('reservation_cancelled', $reservation, $user, description: $reason);

            return $reservation;
        });
    }

    public function changeSlot(Reservation $reservation, ReservationSlot $newSlot): Reservation
    {
        /** @var User|null $user */
        $user = auth()->user();

        return DB::transaction(function () use ($reservation, $newSlot, $user): Reservation {
            $reservation = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if ((int) $reservation->slot_id === (int) $newSlot->id) {
                return $reservation;
            }

            $lockedSlot = ReservationSlot::query()->whereKey($newSlot->id)->lockForUpdate()->firstOrFail();
            $startTime = $reservation->assignedStartTime();
            $endTime = $reservation->assignedEndTime();

            if (
                ! $startTime
                || ! $endTime
                || ! $this->slotAvailability->isIntervalAvailable($lockedSlot, $startTime, $endTime, $reservation)
            ) {
                $interval = $this->slotAvailability
                    ->generateIntervalsForSlot($lockedSlot, $this->settings->reservationDurationMinutes(), $reservation)
                    ->firstWhere('available', true);

                if (! $interval) {
                    $this->slotAvailability->assertIntervalAvailable($lockedSlot, $startTime ?: '00:00', $endTime ?: '00:00', $reservation);
                }

                $startTime = $interval['start_time'];
                $endTime = $interval['end_time'];
            }

            $old = ['slot_id' => $reservation->slot_id, 'advisor_id' => $reservation->advisor_id];

            $reservation->forceFill([
                'slot_id' => $lockedSlot->id,
                'advisor_id' => $lockedSlot->advisor_id,
                'reserved_start_time' => $startTime,
                'reserved_end_time' => $endTime,
                'updated_by' => $user?->id,
            ])->save();

            $this->activityLog->log('slot_changed', $reservation, $user, $old, [
                'slot_id' => $lockedSlot->id,
                'advisor_id' => $lockedSlot->advisor_id,
            ]);

            return $reservation->load(['student.phones', 'slot.advisor', 'payment']);
        });
    }

    public function confirm(Reservation $reservation): Reservation
    {
        return DB::transaction(function () use ($reservation): Reservation {
            $reservation = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();
            $this->setConfirmed($reservation);
            $this->activityLog->log('reservation_confirmed', $reservation, auth()->user());

            return $reservation;
        });
    }

    public function markCompleted(Reservation $reservation): Reservation
    {
        return $this->markFinal($reservation, ReservationStatus::Completed, 'reservation_completed');
    }

    public function markNoShow(Reservation $reservation): Reservation
    {
        return $this->markFinal($reservation, ReservationStatus::NoShow, 'reservation_no_show');
    }

    public function expire(Reservation $reservation): Reservation
    {
        return DB::transaction(function () use ($reservation): Reservation {
            $reservation = Reservation::query()->with('payment')->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if ($reservation->isTerminal()) {
                return $reservation;
            }

            $reservation->forceFill([
                'status' => ReservationStatus::Expired,
                'expired_at' => now(),
            ])->save();

            if ($reservation->payment && in_array($reservation->payment->status, [
                PaymentStatus::PendingUpload,
                PaymentStatus::PendingApproval,
                PaymentStatus::Rejected,
            ], true)) {
                $reservation->payment->forceFill(['status' => PaymentStatus::Expired])->save();
            }

            $this->activityLog->log('reservation_expired', $reservation);

            return $reservation;
        });
    }

    private function markFinal(Reservation $reservation, ReservationStatus $status, string $action): Reservation
    {
        /** @var User|null $user */
        $user = auth()->user();

        return DB::transaction(function () use ($reservation, $status, $action, $user): Reservation {
            $reservation = Reservation::query()->whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            $reservation->forceFill([
                'status' => $status,
                'completed_at' => now(),
                'updated_by' => $user?->id,
            ])->save();

            $this->activityLog->log($action, $reservation, $user);

            return $reservation;
        });
    }

    private function syncPaymentRequirement(Reservation $reservation): void
    {
        if ($reservation->prepayment_required) {
            $payment = $reservation->payment ?: new ReservationPayment(['reservation_id' => $reservation->id]);

            if (! in_array($payment->status, [PaymentStatus::Approved, PaymentStatus::PendingApproval], true)) {
                $payment->status = PaymentStatus::PendingUpload;
            }

            $payment->amount = $reservation->prepayment_amount;
            $payment->save();

            return;
        }

        $reservation->payment()->updateOrCreate(
            ['reservation_id' => $reservation->id],
            [
                'amount' => null,
                'status' => PaymentStatus::NotRequired,
                'rejection_reason' => null,
            ],
        );
    }

    private function advanceStatus(Reservation $reservation): void
    {
        $previousStatus = $reservation->status;

        if (! $reservation->hasCompleteStudentData()) {
            $reservation->forceFill(['status' => ReservationStatus::PendingCompletion])->save();

            return;
        }

        if (! $reservation->prepayment_required) {
            $this->setConfirmed($reservation);
            $this->logConfirmationIfStatusChanged($reservation, $previousStatus);

            return;
        }

        $paymentStatus = $reservation->payment?->status;

        match ($paymentStatus) {
            PaymentStatus::Approved => $this->setConfirmed($reservation),
            PaymentStatus::PendingApproval => $reservation->forceFill(['status' => ReservationStatus::PendingPaymentApproval])->save(),
            PaymentStatus::Rejected => $reservation->forceFill(['status' => ReservationStatus::PaymentRejected])->save(),
            PaymentStatus::Expired => $reservation->forceFill(['status' => ReservationStatus::Expired, 'expired_at' => now()])->save(),
            default => $reservation->forceFill(['status' => ReservationStatus::PendingPrepayment])->save(),
        };

        $this->logConfirmationIfStatusChanged($reservation, $previousStatus);
    }

    private function setConfirmed(Reservation $reservation): void
    {
        $reservation->forceFill([
            'status' => ReservationStatus::Confirmed,
            'confirmed_at' => $reservation->confirmed_at ?: now(),
        ])->save();
    }

    private function logConfirmationIfStatusChanged(Reservation $reservation, ReservationStatus $previousStatus): void
    {
        if ($previousStatus !== ReservationStatus::Confirmed && $reservation->status === ReservationStatus::Confirmed) {
            $this->activityLog->log('reservation_confirmed', $reservation, auth()->user());
        }
    }

    private function syncPhones(Student $student, array $data): void
    {
        if (! array_key_exists('phone_one', $data) && ! array_key_exists('phone_two', $data)) {
            return;
        }

        $student->phones()->delete();

        if (filled($data['phone_one'] ?? null)) {
            $student->phones()->create([
                'phone' => $data['phone_one'],
                'label' => 'شماره اول',
                'is_primary' => true,
            ]);
        }

        if (filled($data['phone_two'] ?? null)) {
            $student->phones()->create([
                'phone' => $data['phone_two'],
                'label' => 'شماره دوم',
                'is_primary' => false,
            ]);
        }
    }
}
