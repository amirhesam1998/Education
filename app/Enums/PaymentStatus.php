<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case NotRequired = 'not_required';
    case PendingUpload = 'pending_upload';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::NotRequired => 'نیاز نیست',
            self::PendingUpload => 'در انتظار آپلود فیش',
            self::PendingApproval => 'در انتظار تأیید فیش',
            self::Approved => 'تأیید شده',
            self::Rejected => 'رد شده',
            self::Expired => 'منقضی شده',
        };
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
