<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function set(Request $request)
    {
        $validated = $request->validate([
            'currency' => ['required', 'string'],
        ]);

        $code = strtoupper((string) $validated['currency']);
        $exists = \App\Models\Currency::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->exists();

        if (! $exists) {
            return back()->withErrors(['currency' => 'Selected currency is not available.']);
        }

        session(['currency' => $code]);

        return back();
    }
}
