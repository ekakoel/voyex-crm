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

if (! function_exists('ui_term')) {
    function ui_term(?string $value): string
    {
        return UiTranslator::token($value, ['terms']);
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
    function ui_phrase(string $key, array $replace = []): string
    {
        $original = trim($key);
        $normalized = UiTranslator::coreKey($original);
        $coreKey = "ui_core.{$normalized}";

        if (\Illuminate\Support\Facades\Lang::has($coreKey)) {
            return __($coreKey, $replace);
        }

        $parts = array_values(array_filter(explode('_', $normalized), static fn ($part) => $part !== ''));
        $partCount = count($parts);
        for ($i = min(4, $partCount - 1); $i >= 1; $i--) {
            $suffix = implode('_', array_slice($parts, -$i));
            $suffixKey = "ui_core.{$suffix}";
            if (\Illuminate\Support\Facades\Lang::has($suffixKey)) {
                return __($suffixKey, $replace);
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
        $coreKey = "ui_core.{$normalized}";

        if (\Illuminate\Support\Facades\Lang::has($coreKey)) {
            return trans_choice($coreKey, $number, $replace);
        }

        return $original;
    }
}
