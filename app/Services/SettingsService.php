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
            'array' => array_values(array_filter(array_map('trim', explode("\n", (string) $setting->value)))),
            default => $setting->value,
        };
    }

    public function set(string $key, mixed $value, string $type = 'string'): Setting
    {
        if (is_array($value)) {
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

    public function reservationDurationMinutes(): int
    {
        return max(1, (int) $this->get('reservation_duration_minutes', 15));
    }

    /**
     * @return array<int, string>
     */
    public function receiptFormats(): array
    {
        return $this->get('allowed_receipt_formats', ['jpg', 'jpeg', 'png', 'webp']);
    }
}
