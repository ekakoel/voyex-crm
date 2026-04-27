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

