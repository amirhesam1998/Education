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

    /** @return array<string, string> */
    private static function permissions(): array
    {
        return [
            'view_dashboard' => 'مشاهده داشبورد',
            'view_slots' => 'مشاهده تایم‌ها',
            'create_slots' => 'ایجاد تایم',
            'update_slots' => 'ویرایش تایم',
            'delete_slots' => 'حذف تایم',
            'view_reservations' => 'مشاهده رزروها',
            'create_reservations' => 'ایجاد رزرو',
            'update_reservations' => 'ویرایش رزرو',
            'view_reservation_requests' => 'مشاهده درخواست‌های رزرو',
            'approve_reservation_requests' => 'تأیید درخواست رزرو',
            'reject_reservation_requests' => 'رد درخواست رزرو',
            'convert_reservation_requests' => 'تبدیل درخواست به رزرو',
            'cancel_reservations' => 'لغو رزرو',
            'change_reservation_slot' => 'تغییر تایم رزرو',
            'confirm_reservations' => 'نهایی کردن یا ثبت وضعیت جلسه',
            'view_payments' => 'مشاهده پرداخت‌ها',
            'approve_payments' => 'تأیید فیش',
            'reject_payments' => 'رد فیش',
            'manage_field_selection' => 'مدیریت انتخاب رشته',
            'view_field_selection' => 'مشاهده انتخاب رشته',
            'view_reservation_sensitive_info' => 'مشاهده اطلاعات حساس رزرو',
            'view_student_personal_data' => 'مشاهده اطلاعات شخصی دانش‌آموز',
            'view_reservation_payment_info' => 'مشاهده اطلاعات پرداخت رزرو',
            'view_prepayment_receipts' => 'مشاهده فیش پیش‌پرداخت',
            'view_student_public_link' => 'مشاهده لینک دانش‌آموز',
            'view_reservation_documents' => 'مشاهده مدارک رزرو',
            'view_consultant_stats' => 'مشاهده آمار مشاوران',
            'view_operator_field_selection_stats' => 'مشاهده آمار انتخاب رشته اپراتورها',
            'view_reports' => 'مشاهده گزارش‌ها',
            'view_study_programs' => 'مشاهده رشته‌محل‌ها',
            'create_study_programs' => 'افزودن رشته‌محل',
            'update_study_programs' => 'ویرایش رشته‌محل',
            'delete_study_programs' => 'حذف رشته‌محل',
            'manage_users' => 'مدیریت کاربران',
            'manage_roles' => 'مدیریت نقش‌ها',
            'manage_settings' => 'مدیریت تنظیمات',
        ];
    }
}
