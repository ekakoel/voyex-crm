<?php

namespace App\Support;

class StatusCatalog
{
    public static function options(string $module): array
    {
        return (array) config("statuses.{$module}.options", []);
    }

    public static function default(string $module): ?string
    {
        $value = config("statuses.{$module}.default");
        return is_string($value) && $value !== '' ? $value : null;
    }

    public static function legacyMap(string $module): array
    {
        return (array) config("statuses.{$module}.legacy_map", []);
    }

    public static function mapLegacy(string $module, ?string $status): ?string
    {
        $normalized = strtolower(trim((string) $status));
        if ($normalized === '') {
            return self::default($module);
        }

        $map = self::legacyMap($module);
        $mapped = $map[$normalized] ?? $normalized;

        return self::isValid($module, $mapped) ? $mapped : self::default($module);
    }

    public static function isValid(string $module, ?string $status): bool
    {
        $normalized = strtolower(trim((string) $status));
        if ($normalized === '') {
            return false;
        }

        return in_array($normalized, self::options($module), true);
    }

    public static function isTerminal(string $module, ?string $status): bool
    {
        $terminal = (array) config("statuses.{$module}.terminal", []);
        return in_array(strtolower(trim((string) $status)), $terminal, true);
    }

    public static function badgeClass(?string $status): string
    {
        $key = strtolower(trim((string) $status));
        $map = (array) config('statuses.badge_classes', []);

        return (string) ($map[$key] ?? ($map['default'] ?? ''));
    }
}
