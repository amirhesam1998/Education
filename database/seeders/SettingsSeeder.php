<?php

namespace Database\Seeders;

use App\Services\SettingsService;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(SettingsService $settings): void
    {
        $settings->set('institute_name', 'آموزشگاه مشاوره کنکور');
        $settings->set('contact_phone', '02100000000');
        $settings->set('default_payment_deadline_hours', 24, 'integer');
        $settings->set('default_public_link_expiration_hours', 48, 'integer');
        $settings->set('reservation_duration_minutes', 15, 'integer');
        $settings->set('default_prepayment_amount', 500000, 'integer');
        $settings->set('max_receipt_image_size_kb', 5120, 'integer');
        $settings->set('allowed_receipt_formats', ['jpg', 'jpeg', 'png', 'webp'], 'array');
        $settings->set('public_link_message_template', 'برای مشاهده و تکمیل رزرو مشاوره خود وارد لینک زیر شوید:');
        $settings->set('exam_types', ['کنکور سراسری', 'کنکور تجربی', 'کنکور ریاضی', 'کنکور انسانی', 'کنکور هنر', 'کنکور زبان'], 'array');
        $settings->set('majors', ['تجربی', 'ریاضی', 'انسانی', 'هنر', 'زبان'], 'array');
        $settings->set('expired_message', 'مهلت تکمیل اطلاعات یا پرداخت به پایان رسیده است. لطفاً با آموزشگاه تماس بگیرید.');
        $settings->set('cancelled_message', 'این رزرو توسط آموزشگاه لغو شده است. لطفاً با آموزشگاه تماس بگیرید.');
        $settings->set('release_slot_after_payment_rejection', true, 'boolean');
    }
}
