<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class UiTranslator
{
    public static function coreKey(string $value): string
    {
        $key = trim($value);
        $key = str_replace(['-', ' '], '_', $key);
        $key = trim($key, '.');
        if (str_starts_with($key, 'ui.')) {
            $key = substr($key, 3);
        }

        return str_replace('.', '_', $key);
    }

    /**
     * Normalize free-form token into a reusable dictionary key.
     * Examples:
     * - App\Models\Itinerary => itinerary
     * - itinerary_day_planner => itinerary_day_planner
     * - FoodBeverage => food_beverage
     */
    public static function normalizeToken(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '' || $value === '-') {
            return '';
        }

        if (str_contains($value, '\\')) {
            $value = Str::afterLast($value, '\\');
        }

        $value = Str::snake($value);
        $value = preg_replace('/[^a-z0-9_]+/', '_', (string) $value) ?? '';
        $value = preg_replace('/_+/', '_', (string) $value) ?? '';

        return trim((string) $value, '_');
    }

    /**
     * Translate a token from the unified core dictionary.
     *
     * @param  array<int, string>  $groups
     */
    public static function token(
        ?string $value,
        array $groups = ['terms']
    ): string {
        $normalized = self::normalizeToken($value);
        if ($normalized === '') {
            if (Lang::has('ui_core.na')) {
                return __('ui_core.na');
            }

            return ui_phrase('common_unknown');
        }

        $coreKey = "ui_core.{$normalized}";
        if (Lang::has($coreKey)) {
            return __($coreKey);
        }

        return __(Str::headline($normalized));
    }
}
