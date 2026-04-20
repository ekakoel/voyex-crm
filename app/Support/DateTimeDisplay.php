<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

class DateTimeDisplay
{
    public const DATE_FORMAT = 'Y-m-d';
    public const DATETIME_FORMAT = 'Y-m-d (H:i)';

    public static function date(mixed $value, string $placeholder = '-'): string
    {
        $parsed = self::parse($value);
        return $parsed?->format(self::DATE_FORMAT) ?? $placeholder;
    }

    public static function datetime(mixed $value, string $placeholder = '-'): string
    {
        $parsed = self::parse($value);
        return $parsed?->format(self::DATETIME_FORMAT) ?? $placeholder;
    }

    private static function parse(mixed $value): ?CarbonImmutable
    {
        if ($value instanceof DateTimeInterface) {
            return CarbonImmutable::instance($value);
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return CarbonImmutable::parse($value);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}

