<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $supportedLocales = array_keys((array) config('app.supported_locales', []));
        $defaultLocale = (string) config('app.locale', 'en');

        $sessionLocale = (string) $request->session()->get('locale', '');
        $locale = in_array($sessionLocale, $supportedLocales, true)
            ? $sessionLocale
            : $defaultLocale;

        App::setLocale($locale);

        return $next($request);
    }
}

