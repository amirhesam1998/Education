<?php

namespace App\Services;

use App\Models\Reservation;
use App\Models\ReservationRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReservationRequestService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly ReservationService $reservations,
    ) {
    }

    public function createFromPublic(array $data): ReservationRequest
    {
        return DB::transaction(function () use ($data): ReservationRequest {
            if (ReservationRequest::query()->where('phone_1', $data['phone_1'])->where('status', ReservationRequest::STATUS_PENDING)->exists()) {
                throw ValidationException::withMessages([
                    'phone_1' => 'شما قبلاً یک درخواست در انتظار بررسی ثبت کرده‌اید. لطفاً منتظر تماس آموزشگاه بمانید.',
                ]);
            }

            $request = ReservationRequest::query()->create($data + ['status' => ReservationRequest::STATUS_PENDING]);
            $this->activityLog->log('reservation_request_created', newValues: ['reservation_request_id' => $request->id]);

            return $request;
        });
    }

    public function approve(ReservationRequest $request, User $user): ReservationRequest
    {
        return $this->review($request, $user, ReservationRequest::STATUS_APPROVED);
    }

    public function reject(ReservationRequest $request, User $user, string $reason): ReservationRequest
    {
        return $this->review($request, $user, ReservationRequest::STATUS_REJECTED, $reason);
    }

    public function convertToReservation(ReservationRequest $request, array $reservationData, User $user): Reservation
    {
        return DB::transaction(function () use ($request, $reservationData, $user): Reservation {
            $request = ReservationRequest::query()->lockForUpdate()->findOrFail($request->id);

            if (! $request->isApproved()) {
                throw ValidationException::withMessages(['reservation_request_id' => 'فقط درخواست تأییدشده قابل تبدیل به رزرو است.']);
            }

            $reservation = $this->reservations->createFromAdmin($reservationData);

            $request->forceFill([
                'status' => ReservationRequest::STATUS_CONVERTED,
                'converted_reservation_id' => $reservation->id,
            ])->save();

            $this->activityLog->log('reservation_request_converted', $reservation, $user, null, [
                'reservation_request_id' => $request->id,
                'converted_reservation_id' => $reservation->id,
            ]);

            return $reservation;
        });
    }

    private function review(ReservationRequest $request, User $user, string $status, ?string $reason = null): ReservationRequest
    {
        return DB::transaction(function () use ($request, $user, $status, $reason): ReservationRequest {
            $request = ReservationRequest::query()->lockForUpdate()->findOrFail($request->id);

            if (! $request->isPending()) {
                throw ValidationException::withMessages(['status' => 'این درخواست قبلاً بررسی شده است.']);
            }

            $request->forceFill([
                'status' => $status,
                'reviewed_by' => $user->id,
                'reviewed_at' => now(),
                'rejection_reason' => $status === ReservationRequest::STATUS_REJECTED ? $reason : null,
            ])->save();

            $this->activityLog->log(
                $status === ReservationRequest::STATUS_APPROVED ? 'reservation_request_approved' : 'reservation_request_rejected',
                user: $user,
                newValues: ['reservation_request_id' => $request->id, 'status' => $status],
                description: $reason,
            );

            return $request;
        });
    }
}
