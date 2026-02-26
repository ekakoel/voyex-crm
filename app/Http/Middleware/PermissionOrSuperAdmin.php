<?php

namespace App\Http\Middleware;

use Closure;
use Spatie\Permission\Middleware\PermissionMiddleware;

class PermissionOrSuperAdmin extends PermissionMiddleware
{
    public function handle($request, Closure $next, $permission, $guard = null)
    {
        $user = $request->user();

        if ($user && $user->hasRole('Super Admin')) {
            return $next($request);
        }

        return parent::handle($request, $next, $permission, $guard);
    }
}
