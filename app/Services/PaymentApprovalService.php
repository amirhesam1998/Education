<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\ReservationStatus;
use App\Models\Reservation;
use App\Models\ReservationPayment;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PaymentApprovalService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly ReservationService $reservationService,
    ) {
    }

    public function uploadReceipt(Reservation $reservation, UploadedFile $file): ReservationPayment
    {
        if ($reservation->payment_deadline_at && $reservation->payment_deadline_at->isPast()) {
            $this->reservationService->expire($reservation);

            throw ValidationException::withMessages([
                'receipt_image' => 'مهلت پرداخت به پایان رسیده است. لطفاً با آموزشگاه تماس بگیرید.',
            ]);
        }

        return DB::transaction(function () use ($reservation, $file): ReservationPayment {
            $reservation = Reservation::query()
                ->with(['student.phones', 'payment'])
                ->whereKey($reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $reservation->prepayment_required) {
                throw ValidationException::withMessages([
                    'receipt_image' => 'برای این رزرو پیش پرداخت لازم نیست.',
                ]);
            }

            if (! $reservation->hasCompleteStudentData()) {
                throw ValidationException::withMessages([
                    'receipt_image' => 'ابتدا اطلاعات ناقص رزرو را تکمیل کنید.',
                ]);
            }

            if (! in_array($reservation->status, [
                ReservationStatus::PendingPrepayment,
                ReservationStatus::PaymentRejected,
            ], true)) {
                throw ValidationException::withMessages([
                    'receipt_image' => 'در وضعیت فعلی امکان آپلود فیش وجود ندارد.',
                ]);
            }

            $path = $file->store('receipts/'.$reservation->id, 'local');

            $payment = $reservation->payment ?: new ReservationPayment(['reservation_id' => $reservation->id]);
            $payment->forceFill([
                'amount' => $reservation->prepayment_amount,
                'receipt_image_path' => $path,
                'status' => PaymentStatus::PendingApproval,
                'uploaded_at' => now(),
                'approved_at' => null,
                'rejected_at' => null,
                'approved_by' => null,
                'rejection_reason' => null,
            ])->save();

            $reservation->forceFill(['status' => ReservationStatus::PendingPaymentApproval])->save();
            $this->activityLog->log('receipt_uploaded', $reservation);

            return $payment;
        });
    }

    public function approve(ReservationPayment $payment, User $user): ReservationPayment
    {
        return DB::transaction(function () use ($payment, $user): ReservationPayment {
            $payment = ReservationPayment::query()
                ->with('reservation')
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $payment->forceFill([
                'status' => PaymentStatus::Approved,
                'approved_at' => now(),
                'approved_by' => $user->id,
                'rejected_at' => null,
                'rejection_reason' => null,
            ])->save();

            $this->reservationService->confirm($payment->reservation);
            $this->activityLog->log('receipt_approved', $payment->reservation, $user);

            return $payment;
        });
    }

    public function reject(ReservationPayment $payment, User $user, string $reason): ReservationPayment
    {
        return DB::transaction(function () use ($payment, $user, $reason): ReservationPayment {
            $payment = ReservationPayment::query()
                ->with('reservation')
                ->whereKey($payment->id)
                ->lockForUpdate()
                ->firstOrFail();

            $payment->forceFill([
                'status' => PaymentStatus::Rejected,
                'rejected_at' => now(),
                'approved_at' => null,
                'approved_by' => null,
                'rejection_reason' => $reason,
            ])->save();

            $payment->reservation->forceFill(['status' => ReservationStatus::PaymentRejected])->save();
            $this->activityLog->log('receipt_rejected', $payment->reservation, $user, description: $reason);

            return $payment;
        });
    }

    public function receiptResponse(ReservationPayment $payment)
    {
        abort_unless($payment->receipt_image_path && Storage::disk('local')->exists($payment->receipt_image_path), 404);

        return Storage::disk('local')->response($payment->receipt_image_path);
    }
}
