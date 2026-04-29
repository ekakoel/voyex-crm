<?php

use App\Support\UiTranslator;

if (! function_exists('ui_token')) {
    /**
     * Generic shared dictionary translate helper.
     *
     * @param  array<int, string>  $groups
     */
    function ui_token(?string $value, array $groups = ['terms']): string
    {
        return UiTranslator::token($value, $groups);
    }
}

if (! function_exists('ui_entity')) {
    function ui_entity(?string $value): string
    {
        return UiTranslator::token($value, ['entities', 'terms']);
    }
}

if (! function_exists('ui_action')) {
    function ui_action(?string $value): string
    {
        return UiTranslator::token($value, ['actions', 'terms']);
    }
}

if (! function_exists('ui_phrase')) {
    /**
     * Reusable phrase dictionary from one centralized namespace.
     */
    function ui_phrase(?string $key, array $replace = []): string
    {
        $original = trim((string) $key);
        if ($original === '') {
            return '';
        }
        $normalized = UiTranslator::coreKey($original);
        $core = \Illuminate\Support\Facades\Lang::get('ui_core');
        if (! is_array($core)) {
            $core = [];
        }

        // 1) Exact phrase key lookup (supports human-readable keys with spaces).
        if (array_key_exists($original, $core) && is_string($core[$original])) {
            return __(
                'ui_core.'.$original,
                $replace
            );
        }

        // 2) Normalized lookup.
        if (array_key_exists($normalized, $core) && is_string($core[$normalized])) {
            return __(
                'ui_core.'.$normalized,
                $replace
            );
        }

        $legacyMap = config('ui_legacy_map', []);
        if (is_array($legacyMap)) {
            if (isset($legacyMap[$original]) && is_string($legacyMap[$original])) {
                $mapped = (string) $legacyMap[$original];
                if (array_key_exists($mapped, $core) && is_string($core[$mapped])) {
                    return __('ui_core.'.$mapped, $replace);
                }
            }
            if (isset($legacyMap[$normalized]) && is_string($legacyMap[$normalized])) {
                $mapped = (string) $legacyMap[$normalized];
                if (array_key_exists($mapped, $core) && is_string($core[$mapped])) {
                    return __('ui_core.'.$mapped, $replace);
                }
            }
        }

        $parts = array_values(array_filter(explode('_', $normalized), static fn ($part) => $part !== ''));
        $partCount = count($parts);
        for ($i = min(4, $partCount - 1); $i >= 1; $i--) {
            $suffix = implode('_', array_slice($parts, -$i));
            if (array_key_exists($suffix, $core) && is_string($core[$suffix])) {
                return __('ui_core.'.$suffix, $replace);
            }
        }

        return $original;
    }
}

if (! function_exists('ui_choice')) {
    /**
     * Plural-aware phrase resolver from core dictionary.
     */
    function ui_choice(string $key, int|float $number, array $replace = []): string
    {
        $original = trim($key);
        $normalized = UiTranslator::coreKey($original);
        $legacyMap = config('ui_legacy_map', []);
        if (is_array($legacyMap) && isset($legacyMap[$normalized]) && is_string($legacyMap[$normalized])) {
            $normalized = (string) $legacyMap[$normalized];
        }
        $coreKey = "ui_core.{$normalized}";

        if (\Illuminate\Support\Facades\Lang::has($coreKey)) {
            return trans_choice($coreKey, $number, $replace);
        }

        return $original;
    }
}
