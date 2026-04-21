<?php

namespace App\Http\Controllers;

use App\Support\Currency as CurrencySupport;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function set(Request $request)
    {
        $validated = $request->validate([
            'currency' => ['required', 'string'],
        ]);

        $code = strtoupper((string) $validated['currency']);
        $exists = CurrencySupport::activeOptions()
            ->contains(fn ($currency): bool => strtoupper((string) ($currency->code ?? '')) === $code);

        if (! $exists) {
            return back()->withErrors(['currency' => 'Selected currency is not available.']);
        }

        session(['currency' => $code]);

        return back();
    }
}
