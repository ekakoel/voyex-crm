<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureDirectorOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user || ! ($user->can('dashboard.director.view') || $user->can('dashboard.superadmin.view'))) {
            abort(403, 'This page is accessible by Director only.');
        }

        return $next($request);
    }
}
