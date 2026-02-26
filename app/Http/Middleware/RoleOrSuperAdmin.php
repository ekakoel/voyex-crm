<?php

namespace App\Http\Middleware;

use Closure;
use Spatie\Permission\Middleware\RoleMiddleware;

class RoleOrSuperAdmin extends RoleMiddleware
{
    public function handle($request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if ($user && $user->hasRole('Super Admin')) {
            return $next($request);
        }

        return parent::handle($request, $next, ...$roles);
    }
}
