<?php

namespace App\Support;

use App\Models\CompanySetting;
use App\Models\Currency as CurrencyModel;
use Illuminate\Support\Facades\Schema;

class Currency
{
    public static function current(): string
    {
        if (Schema::hasTable('currencies')) {
            $session = session('currency');
            if (is_string($session)) {
                $active = CurrencyModel::query()
                    ->where('code', strtoupper($session))
                    ->where('is_active', true)
                    ->exists();
                if ($active) {
                    return strtoupper($session);
                }
            }

            $default = CurrencyModel::query()
                ->where('is_default', true)
                ->where('is_active', true)
                ->value('code');
            if (is_string($default) && $default !== '') {
                return strtoupper($default);
            }
        }

        $settings = CompanySetting::query()->first();
        $base = strtoupper((string) ($settings?->currency ?? 'IDR'));
        return $base !== '' ? $base : 'IDR';
    }

    public static function rate(string $from, string $to): float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);
        if ($from === $to) {
            return 1.0;
        }

        if (Schema::hasTable('currencies')) {
            $fromRate = (float) CurrencyModel::query()->where('code', $from)->value('rate_to_idr');
            $toRate = (float) CurrencyModel::query()->where('code', $to)->value('rate_to_idr');
        } else {
            $fromRate = 0;
            $toRate = 0;
        }

        if ($fromRate <= 0 || $toRate <= 0) {
            $settings = CompanySetting::query()->first();
            $idrPerUsd = (float) ($settings?->usd_rate ?? 16000);
            $idrPerUsd = $idrPerUsd > 0 ? $idrPerUsd : 16000;

            if ($from === 'IDR' && $to === 'USD') {
                return 1 / $idrPerUsd;
            }
            if ($from === 'USD' && $to === 'IDR') {
                return $idrPerUsd;
            }

            return 1.0;
        }

        return $fromRate / $toRate;
    }

    public static function convert(float $amount, string $from, ?string $to = null): float
    {
        $target = $to ?: static::current();
        return $amount * static::rate($from, $target);
    }

    public static function meta(string $code): ?array
    {
        if (! Schema::hasTable('currencies')) {
            return null;
        }

        $row = CurrencyModel::query()
            ->where('code', strtoupper($code))
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'code' => $row->code,
            'name' => $row->name,
            'symbol' => $row->symbol,
            'rate_to_idr' => (float) $row->rate_to_idr,
            'decimal_places' => (int) $row->decimal_places,
        ];
    }

    public static function format(?float $amount, string $fromCurrency = 'IDR', ?string $toCurrency = null): string
    {
        if ($amount === null) {
            return '-';
        }

        $target = $toCurrency ?: static::current();
        $value = static::convert((float) $amount, $fromCurrency, $target);
        $meta = static::meta($target);
        $symbol = $meta['symbol'] ?? ($target === 'USD' ? '$' : 'Rp');
        $decimals = $meta['decimal_places'] ?? ($target === 'USD' ? 2 : 0);

        if ($target === 'USD') {
            return $symbol . ' ' . number_format($value, $decimals, '.', ',');
        }

        return $symbol . ' ' . number_format($value, $decimals, ',', '.');
    }
}
