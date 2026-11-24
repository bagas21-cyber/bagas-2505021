<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;

class SettingService
{
    public function get(string $key, mixed $default = null): mixed
    {
        if (! app()->bound('db') || ! Schema::hasTable('settings')) {
            return $default;
        }

        return Setting::query()->where('key', $key)->value('value') ?? $default;
    }

    /** @return array<string, string|null> */
    public function all(): array
    {
        return Setting::query()->pluck('value', 'key')->all();
    }

    public function set(string $key, mixed $value): Setting
    {
        return Setting::updateOrCreate(['key' => $key], ['value' => $value === null ? null : (string) $value]);
    }

    public function enabled(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
