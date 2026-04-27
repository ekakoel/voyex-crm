<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Str;

class UiTranslator
{
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
     * Translate a token from the shared dictionary.
     *
     * @param  array<int, string>  $groups
     * @param  array<int, string>  $prefixes
     */
    public static function token(
        ?string $value,
        array $groups = ['terms'],
        array $prefixes = ['ui.shared', 'ui.superadmin.shared']
    ): string {
        $normalized = self::normalizeToken($value);
        if ($normalized === '') {
            return __('ui.common.unknown');
        }

        foreach ($prefixes as $prefix) {
            foreach ($groups as $group) {
                $key = trim($prefix).'.'.trim($group).'.'.$normalized;
                if (Lang::has($key)) {
                    return __($key);
                }
            }
        }

        return __(Str::headline($normalized));
    }
}

