<?php

namespace App\Support;

class PermissionLabels
{
    public static function permission(string $name): string
    {
        return self::permissions()[$name] ?? $name;
    }

    public static function role(string $name): string
    {
        return [
            'Super Admin' => 'مدیر کل',
            'Admin / Branch Manager' => 'مدیر / مدیر شعبه',
            'Operator' => 'اپراتور',
            'Accountant' => 'حسابدار',
            'Consultant' => 'مشاور',
        ][$name] ?? $name;
    }

    /**
     * @return array<string, string>
     */
    private static function permissions(): array
    {
        return [
            'view_dashboard' => 'مشاهده داشبورد',
            'view_slots' => 'مشاهده تایمها',
            'create_slots' => 'ایجاد تایم',
            'update_slots' => 'ویرایش تایم',
            'delete_slots' => 'حذف تایم',
            'view_reservations' => 'مشاهده رزروها',
            'create_reservations' => 'ایجاد رزرو',
            'update_reservations' => 'ویرایش رزرو',
            'cancel_reservations' => 'لغو رزرو',
            'change_reservation_slot' => 'تغییر تایم رزرو',
            'confirm_reservations' => 'نهایی کردن یا ثبت وضعیت جلسه',
            'view_payments' => 'مشاهده پرداختها',
            'approve_payments' => 'تأیید فیش',
            'reject_payments' => 'رد فیش',
            'view_reports' => 'مشاهده گزارشها',
            'manage_users' => 'مدیریت کاربران',
            'manage_roles' => 'مدیریت نقشها',
            'manage_settings' => 'مدیریت تنظیمات',
        ];
    }
}
