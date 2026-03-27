<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class CurrencyMarketRateSyncService
{
    /**
     * @return array{updated:int, skipped:int, fetched_at:Carbon|null}
     */
    public function sync(bool $force = false): array
    {
        $currencies = Currency::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'rate_to_idr', 'market_rate_to_idr', 'market_rate_synced_at']);

        if ($currencies->isEmpty()) {
            return ['updated' => 0, 'skipped' => 0, 'fetched_at' => null];
        }

        $today = now()->toDateString();
        if (! $force) {
            $allUpdatedToday = $currencies->every(function (Currency $currency) use ($today): bool {
                return optional($currency->market_rate_synced_at)->toDateString() === $today;
            });
            if ($allUpdatedToday) {
                return ['updated' => 0, 'skipped' => $currencies->count(), 'fetched_at' => null];
            }
        }

        $codes = $currencies->pluck('code')
            ->map(fn ($code) => strtoupper((string) $code))
            ->push('IDR')
            ->push('USD')
            ->unique()
            ->values();

        try {
            $response = Http::timeout((int) config('services.fx.timeout', 10))
                ->acceptJson()
                ->get((string) config('services.fx.url', 'https://api.frankfurter.app/latest'), [
                    'base' => (string) config('services.fx.base', 'USD'),
                    'symbols' => $codes->implode(','),
                ]);
        } catch (Throwable $exception) {
            Log::warning('Currency market sync failed: connection/provider error.', [
                'message' => $exception->getMessage(),
            ]);
            return ['updated' => 0, 'skipped' => $currencies->count(), 'fetched_at' => null];
        }

        if (! $response->ok()) {
            Log::warning('Currency market sync failed: bad HTTP response.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return ['updated' => 0, 'skipped' => $currencies->count(), 'fetched_at' => null];
        }

        $rates = (array) $response->json('rates', []);
        $idrPerUsd = isset($rates['IDR']) ? (float) $rates['IDR'] : 0.0;
        if ($idrPerUsd <= 0) {
            Log::warning('Currency market sync failed: IDR rate missing from provider response.');
            return ['updated' => 0, 'skipped' => $currencies->count(), 'fetched_at' => null];
        }

        $now = now();
        $updated = 0;
        $skipped = 0;

        foreach ($currencies as $currency) {
            $code = strtoupper((string) $currency->code);
            $marketRate = $this->computeIdrPerUnit($code, $rates, $idrPerUsd);
            if ($marketRate === null || $marketRate <= 0) {
                $skipped++;
                continue;
            }

            $currency->forceFill([
                'market_rate_to_idr' => $marketRate,
                'market_rate_synced_at' => $now,
            ])->save();

            $updated++;
        }

        return ['updated' => $updated, 'skipped' => $skipped, 'fetched_at' => $now];
    }

    /**
     * Provider base = USD.
     */
    private function computeIdrPerUnit(string $code, array $rates, float $idrPerUsd): ?float
    {
        if ($code === 'IDR') {
            return 1.0;
        }

        if ($code === 'USD') {
            return $idrPerUsd;
        }

        $perUsd = isset($rates[$code]) ? (float) $rates[$code] : 0.0;
        if ($perUsd <= 0) {
            return null;
        }

        return $idrPerUsd / $perUsd;
    }
}
