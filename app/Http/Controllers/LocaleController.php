<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function set(Request $request): RedirectResponse
    {
        $supportedLocales = array_keys((array) config('app.supported_locales', []));

        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:' . implode(',', $supportedLocales)],
        ]);

        $request->session()->put('locale', (string) $validated['locale']);

        return back();
    }
}

