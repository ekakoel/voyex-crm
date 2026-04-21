<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Currency;

trait NormalizesDisplayCurrencyToIdr
{
    protected function displayCurrencyToIdr(float $amount): float
    {
        $activeCurrency = strtoupper((string) Currency::current());
        $displayToIdrRate = (float) Currency::rate($activeCurrency, 'IDR');

        if (! is_finite($displayToIdrRate) || $displayToIdrRate <= 0) {
            $displayToIdrRate = 1.0;
        }

        return $amount * $displayToIdrRate;
    }
}

