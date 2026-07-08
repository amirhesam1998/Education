<?php

namespace App\Enums;

enum ReservationStatus: string
{
    case Draft = 'draft';
    case PendingCompletion = 'pending_completion';
    case PendingPrepayment = 'pending_prepayment';
    case PendingPaymentApproval = 'pending_payment_approval';
    case Confirmed = 'confirmed';
    case PaymentRejected = 'payment_rejected';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case Completed = 'completed';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'پیش نویس',
            self::PendingCompletion => 'در انتظار تکمیل اطلاعات',
            self::PendingPrepayment => 'در انتظار پرداخت',
            self::PendingPaymentApproval => 'در انتظار تأیید فیش',
            self::Confirmed => 'تأیید شده',
            self::PaymentRejected => 'فیش رد شده',
            self::Expired => 'منقضی شده',
            self::Cancelled => 'لغو شده',
            self::Completed => 'انجام شده',
            self::NoShow => 'عدم حضور',
        };
    }

    /**
     * These statuses keep a slot locked until the reservation is resolved.
     *
     * @return array<int, string>
     */
    public static function activeValues(bool $includePaymentRejected = false): array
    {
        $values = [
            self::PendingCompletion->value,
            self::PendingPrepayment->value,
            self::PendingPaymentApproval->value,
            self::Confirmed->value,
        ];

        if ($includePaymentRejected) {
            $values[] = self::PaymentRejected->value;
        }

        return $values;
    }

    /**
     * @return array<int, string>
     */
    public static function pendingValues(): array
    {
        return [
            self::PendingCompletion->value,
            self::PendingPrepayment->value,
            self::PendingPaymentApproval->value,
        ];
    }

    public function allowsPublicUpdates(): bool
    {
        return in_array($this, [
            self::PendingCompletion,
            self::PendingPrepayment,
            self::PaymentRejected,
        ], true);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status) => [$status->value => $status->label()])
            ->all();
    }
}
