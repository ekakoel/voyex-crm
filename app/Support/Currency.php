<?php

namespace App\Support;

use App\Models\Currency as CurrencyModel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class Currency
{
    private const CACHE_KEY = 'currencies:all_meta:v1';
    private const CACHE_TTL_SECONDS = 300;

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function current(): string
    {
        $currencies = static::allMeta();

        if ($currencies->isNotEmpty()) {
            $session = session('currency');
            if (is_string($session)) {
                $sessionCode = strtoupper($session);
                $active = $currencies->first(fn (array $currency): bool => $currency['code'] === $sessionCode && $currency['is_active']);
                if ($active) {
                    return $sessionCode;
                }
            }

            $default = $currencies->first(fn (array $currency): bool => $currency['is_default'] && $currency['is_active']);
            if (is_array($default) && ! empty($default['code'])) {
                return (string) $default['code'];
            }
        }

        return 'IDR';
    }

    public static function rate(string $from, string $to): float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);
        if ($from === $to) {
            return 1.0;
        }

        $currencies = static::allMeta()->keyBy('code');
        $fromRate = (float) ($currencies->get($from)['rate_to_idr'] ?? 0);
        $toRate = (float) ($currencies->get($to)['rate_to_idr'] ?? 0);

        if ($fromRate <= 0 || $toRate <= 0) {
            $idrPerUsd = 16000.0;

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
        return static::allMeta()
            ->first(fn (array $currency): bool => $currency['code'] === strtoupper($code));
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
        $decimals = $meta['decimal_places'] ?? 0;

        if ($target === 'USD') {
            return trim((string) $symbol) . number_format($value, $decimals, '.', ',');
        }

        return $symbol . ' ' . number_format($value, $decimals, ',', '.');
    }

    public static function activeOptions(): Collection
    {
        return static::allMeta()
            ->filter(fn (array $currency): bool => $currency['is_active'])
            ->values()
            ->map(fn (array $currency) => (object) $currency);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{
     *     code: string,
     *     name: string,
     *     symbol: string|null,
     *     rate_to_idr: float,
     *     decimal_places: int,
     *     is_active: bool,
     *     is_default: bool
     * }>
     */
    private static function allMeta(): Collection
    {
        if (! SchemaInspector::hasTable('currencies')) {
            return collect();
        }

        return Cache::remember(self::CACHE_KEY, now()->addSeconds(self::CACHE_TTL_SECONDS), function (): Collection {
            return CurrencyModel::query()
                ->orderByDesc('is_default')
                ->orderBy('code')
                ->get(['code', 'name', 'symbol', 'rate_to_idr', 'decimal_places', 'is_active', 'is_default'])
                ->map(function (CurrencyModel $currency): array {
                    $code = strtoupper((string) $currency->code);
                    $decimalPlaces = (int) $currency->decimal_places;
                    if ($code === 'USD') {
                        $decimalPlaces = 0;
                    }

                    return [
                        'code' => $code,
                        'name' => (string) $currency->name,
                        'symbol' => $currency->symbol,
                        'rate_to_idr' => (float) $currency->rate_to_idr,
                        'decimal_places' => $decimalPlaces,
                        'is_active' => (bool) $currency->is_active,
                        'is_default' => (bool) $currency->is_default,
                    ];
                })
                ->values();
        });
    }
}
