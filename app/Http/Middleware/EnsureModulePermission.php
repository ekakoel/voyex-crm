<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModulePermission
{
    /**
     * Enforce CRUD-level permission for a module resource.
     */
    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('Super Admin')) {
            return $next($request);
        }

        // Deletion across module resources is restricted to Super Admin only.
        if ($request->isMethod('DELETE')) {
            abort(403, 'Only Super Admin can delete module data.');
        }

        $action = $request->route()?->getActionMethod();
        $permission = match ($action) {
            'index', 'show' => "module.{$moduleKey}.read",
            'create', 'store' => "module.{$moduleKey}.create",
            'edit', 'update' => "module.{$moduleKey}.update",
            'destroy' => "module.{$moduleKey}.delete",
            default => "module.{$moduleKey}.access",
        };

        if ($user && $user->can($permission)) {
            return $next($request);
        }

        abort(403, 'You do not have permission to access this resource.');
    }
}
