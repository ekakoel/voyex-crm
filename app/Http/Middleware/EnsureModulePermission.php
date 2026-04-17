<?php

namespace App\Http\Middleware;

use App\Services\ModuleService;
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
        if (! ModuleService::isEnabledStatic($moduleKey)) {
            abort(404);
        }

        $user = $request->user();

        if ($user && $user->isSuperAdmin()) {
            return $next($request);
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
