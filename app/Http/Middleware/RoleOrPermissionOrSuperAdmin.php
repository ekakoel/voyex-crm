<?php

namespace App\Http\Middleware;

use Closure;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

class RoleOrPermissionOrSuperAdmin extends RoleOrPermissionMiddleware
{
    public function handle($request, Closure $next, $roleOrPermission, $guard = null)
    {
        $user = $request->user();

        if ($user && $user->hasRole('Super Admin')) {
            return $next($request);
        }

        return parent::handle($request, $next, $roleOrPermission, $guard);
    }
}
