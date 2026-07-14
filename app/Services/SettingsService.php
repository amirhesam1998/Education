<?php

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = Setting::query()->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOL),
            'json' => json_decode((string) $setting->value, true) ?: $default,
            'array' => array_values(array_filter(array_map('trim', explode("\n", (string) $setting->value)))),
            default => $setting->value,
        };
    }

    public function set(string $key, mixed $value, string $type = 'string'): Setting
    {
        if ($type === 'json') {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        } elseif (is_array($value)) {
            $value = implode("\n", array_filter(array_map('trim', $value)));
            $type = 'array';
        }

        return Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type],
        );
    }

    public function receiptMaxKilobytes(): int
    {
        return max(1, (int) $this->get('max_receipt_image_size_kb', 5120));
    }

    public function reportCardMaxKilobytes(): int
    {
        return max(1, (int) $this->get('report_card_max_upload_size_mb', 10)) * 1024;
    }

    public function reservationDurationMinutes(): int
    {
        return max(1, (int) $this->get('reservation_duration_minutes', 15));
    }

    /**
     * @return array<int, array{amount:int, label:string, is_active:bool}>
     */
    public function prepaymentAmountPresets(): array
    {
        $presets = $this->get('prepayment_amount_presets', []);

        return collect(is_array($presets) ? $presets : [])
            ->map(fn (array $preset) => [
                'amount' => (int) ($preset['amount'] ?? 0),
                'label' => (string) ($preset['label'] ?? ''),
                'is_active' => (bool) ($preset['is_active'] ?? false),
            ])
            ->filter(fn (array $preset) => $preset['amount'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{amount:int, label:string, is_active:bool}>
     */
    public function activePrepaymentAmountPresets(): array
    {
        return collect($this->prepaymentAmountPresets())
            ->filter(fn (array $preset) => $preset['is_active'])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function receiptFormats(): array
    {
        return $this->get('allowed_receipt_formats', ['jpg', 'jpeg', 'png', 'webp']);
    }
}
