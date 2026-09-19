<?php

namespace Database\Seeders;

use App\Services\SettingsService;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(SettingsService $settings): void
    {
        $settings->setIfMissing('institute_name', 'آموزشگاه مشاوره کنکور');
        $settings->setIfMissing('contact_phone', '02100000000');
        $settings->setIfMissing('default_payment_deadline_hours', 24, 'integer');
        $settings->setIfMissing('default_public_link_expiration_hours', 48, 'integer');
        $settings->setIfMissing('default_prepayment_amount', 500000, 'integer');
        $settings->setIfMissing('prepayment_amount_presets', [
            ['amount' => 500000, 'label' => '۵۰۰,۰۰۰ تومان', 'is_active' => true],
            ['amount' => 1000000, 'label' => '۱,۰۰۰,۰۰۰ تومان', 'is_active' => true],
            ['amount' => 2000000, 'label' => '۲,۰۰۰,۰۰۰ تومان', 'is_active' => true],
        ], 'json');
        $settings->setIfMissing('max_receipt_image_size_kb', 5120, 'integer');
        $settings->setIfMissing('report_card_max_upload_size_mb', 10, 'integer');
        $settings->setIfMissing('allowed_receipt_formats', ['jpg', 'jpeg', 'png', 'webp'], 'array');
        $settings->setIfMissing('public_link_message_template', 'برای مشاهده و تکمیل رزرو مشاوره خود وارد لینک زیر شوید:');
        $settings->setIfMissing('exam_types', ['کنکور سراسری', 'کنکور تجربی', 'کنکور ریاضی', 'کنکور انسانی', 'کنکور هنر', 'کنکور زبان'], 'array');
        $settings->setIfMissing('majors', ['تجربی', 'ریاضی', 'انسانی', 'هنر', 'زبان'], 'array');
        $settings->setIfMissing('expired_message', 'مهلت تکمیل اطلاعات یا پرداخت به پایان رسیده است. لطفاً با آموزشگاه تماس بگیرید.');
        $settings->setIfMissing('cancelled_message', 'این رزرو توسط آموزشگاه لغو شده است. لطفاً با آموزشگاه تماس بگیرید.');
    }
}
