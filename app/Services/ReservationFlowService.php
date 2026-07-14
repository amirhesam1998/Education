<?php

namespace App\Services;

use App\Enums\ReservationStatus;
use App\Models\Reservation;

class ReservationFlowService
{
    public function getPublicFlowSteps(Reservation $reservation): array
    {
        $keys = $reservation->prepayment_required
            ? [
                'created' => 'ثبت اولیه',
                'completion' => 'تکمیل اطلاعات',
                'prepayment' => 'پرداخت پیش‌پرداخت',
                'receipt_approval' => 'تأیید فیش',
                'confirmed' => 'تأیید نهایی',
                'completed' => 'انجام شده',
            ]
            : [
                'created' => 'ثبت اولیه',
                'completion' => 'تکمیل اطلاعات',
                'confirmed' => 'تأیید نهایی',
                'completed' => 'انجام شده',
            ];

        $currentKey = $this->getCurrentStep($reservation);
        $currentKey = array_key_exists($currentKey, $keys) ? $currentKey : 'confirmed';
        $currentIndex = array_search($currentKey, array_keys($keys), true);

        return collect($keys)
            ->map(function (string $label, string $key) use ($reservation, $currentIndex, $keys): array {
                $index = array_search($key, array_keys($keys), true);
                $state = match (true) {
                    $reservation->status === ReservationStatus::Completed => 'completed',
                    in_array($reservation->status, [ReservationStatus::Cancelled, ReservationStatus::Expired, ReservationStatus::NoShow, ReservationStatus::PaymentRejected], true)
                        && $key === $this->getCurrentStep($reservation) => 'failed',
                    $index < $currentIndex => 'completed',
                    $index === $currentIndex => 'active',
                    default => 'pending',
                };

                return compact('key', 'label', 'state');
            })
            ->values()
            ->all();
    }

    public function getCurrentStep(Reservation $reservation): string
    {
        return match ($reservation->status) {
            ReservationStatus::PendingCompletion, ReservationStatus::Draft => 'completion',
            ReservationStatus::PendingPrepayment, ReservationStatus::PaymentRejected => 'prepayment',
            ReservationStatus::PendingPaymentApproval => 'receipt_approval',
            ReservationStatus::Completed => 'completed',
            ReservationStatus::NoShow => 'completed',
            ReservationStatus::Confirmed => 'confirmed',
            ReservationStatus::Expired, ReservationStatus::Cancelled => 'completion',
        };
    }

    public function getStatusMessage(Reservation $reservation): string
    {
        return match ($reservation->status) {
            ReservationStatus::PendingPaymentApproval => 'فیش شما ثبت شد و در انتظار تأیید آموزشگاه است.',
            ReservationStatus::Confirmed => 'رزرو شما با موفقیت نهایی شده است.',
            ReservationStatus::Completed => 'جلسه مشاوره شما انجام شده است.',
            ReservationStatus::NoShow => 'عدم حضور برای این رزرو ثبت شده است.',
            ReservationStatus::Expired => 'مهلت تکمیل اطلاعات یا پرداخت به پایان رسیده است. لطفاً با آموزشگاه تماس بگیرید.',
            ReservationStatus::Cancelled => 'این رزرو توسط آموزشگاه لغو شده است. لطفاً با آموزشگاه تماس بگیرید.',
            ReservationStatus::PaymentRejected => 'فیش پرداختی شما توسط آموزشگاه رد شده است. لطفاً با آموزشگاه تماس بگیرید.',
            default => '',
        };
    }

    public function shouldShowCompletionWarning(Reservation $reservation): bool
    {
        return $reservation->status === ReservationStatus::PendingCompletion;
    }

    public function shouldShowPaymentWarning(Reservation $reservation): bool
    {
        return $reservation->status === ReservationStatus::PendingPrepayment
            && $reservation->prepayment_required;
    }
}
