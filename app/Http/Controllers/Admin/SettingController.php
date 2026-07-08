<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(SettingsService $settings): View
    {
        return view('admin.settings.edit', [
            'settings' => $settings,
        ]);
    }

    public function update(UpdateSettingsRequest $request, SettingsService $settings): RedirectResponse
    {
        $data = $request->validated();

        $settings->set('institute_name', $data['institute_name']);
        $settings->set('contact_phone', $data['contact_phone']);
        $settings->set('default_payment_deadline_hours', $data['default_payment_deadline_hours'], 'integer');
        $settings->set('default_public_link_expiration_hours', $data['default_public_link_expiration_hours'], 'integer');
        $settings->set('reservation_duration_minutes', $data['reservation_duration_minutes'], 'integer');
        $settings->set('default_prepayment_amount', $data['default_prepayment_amount'] ?? null, 'integer');
        $settings->set('max_receipt_image_size_kb', $data['max_receipt_image_size_kb'], 'integer');
        $settings->set('allowed_receipt_formats', preg_split('/[\s,]+/', $data['allowed_receipt_formats']) ?: [], 'array');
        $settings->set('public_link_message_template', $data['public_link_message_template'] ?? null);
        $settings->set('exam_types', preg_split('/\r\n|\r|\n/', $data['exam_types'] ?? '') ?: [], 'array');
        $settings->set('majors', preg_split('/\r\n|\r|\n/', $data['majors'] ?? '') ?: [], 'array');
        $settings->set('expired_message', $data['expired_message']);
        $settings->set('cancelled_message', $data['cancelled_message']);
        $settings->set('release_slot_after_payment_rejection', $data['release_slot_after_payment_rejection'] ?? false, 'boolean');

        return back()->with('success', 'تنظیمات ذخیره شد.');
    }
}
