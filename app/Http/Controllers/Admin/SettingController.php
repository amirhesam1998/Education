<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\PaymentCard;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(SettingsService $settings): View
    {
        return view('admin.settings.edit', [
            'settings' => $settings,
            'paymentCards' => PaymentCard::query()->orderByDesc('is_active')->orderBy('bank_name')->get(),
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
        $settings->set('prepayment_amount_presets', $this->prepaymentPresets($data['prepayment_presets'] ?? []), 'json');
        $settings->set('max_receipt_image_size_kb', $data['max_receipt_image_size_kb'], 'integer');
        $settings->set('report_card_max_upload_size_mb', $data['report_card_max_upload_size_mb'], 'integer');
        $settings->set('allowed_receipt_formats', preg_split('/[\s,]+/', $data['allowed_receipt_formats']) ?: [], 'array');
        $settings->set('public_link_message_template', $data['public_link_message_template'] ?? null);
        $settings->set('exam_types', preg_split('/\r\n|\r|\n/', $data['exam_types'] ?? '') ?: [], 'array');
        $settings->set('majors', preg_split('/\r\n|\r|\n/', $data['majors'] ?? '') ?: [], 'array');
        $settings->set('expired_message', $data['expired_message']);
        $settings->set('cancelled_message', $data['cancelled_message']);
        $settings->set('release_slot_after_payment_rejection', $data['release_slot_after_payment_rejection'] ?? false, 'boolean');
        $this->syncPaymentCards($data['payment_cards'] ?? []);

        return back()->with('success', 'تنظیمات ذخیره شد.');
    }

    private function prepaymentPresets(array $rows): array
    {
        return collect($rows)
            ->map(fn (array $row) => [
                'amount' => (int) ($row['amount'] ?? 0),
                'label' => trim((string) ($row['label'] ?? '')),
                'is_active' => (bool) ($row['is_active'] ?? false),
            ])
            ->filter(fn (array $row) => $row['amount'] > 0)
            ->values()
            ->all();
    }

    private function syncPaymentCards(array $rows): void
    {
        foreach ($rows as $row) {
            if (blank($row['holder_name'] ?? null) && blank($row['card_number'] ?? null) && blank($row['bank_name'] ?? null)) {
                continue;
            }

            PaymentCard::query()->updateOrCreate(
                ['id' => $row['id'] ?? null],
                [
                    'holder_name' => $row['holder_name'],
                    'card_number' => preg_replace('/\D+/', '', (string) $row['card_number']),
                    'bank_name' => $row['bank_name'],
                    'description' => $row['description'] ?? null,
                    'is_active' => (bool) ($row['is_active'] ?? false),
                ],
            );
        }
    }
}
