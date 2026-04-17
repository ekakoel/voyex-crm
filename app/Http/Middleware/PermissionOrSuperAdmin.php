<?php

namespace App\Http\Middleware;

use App\Services\ModuleService;
use Closure;
use Spatie\Permission\Middleware\PermissionMiddleware;

class PermissionOrSuperAdmin extends PermissionMiddleware
{
    public function handle($request, Closure $next, $permission, $guard = null)
    {
        foreach ($this->extractModuleKeys((string) $permission) as $moduleKey) {
            if (! ModuleService::isEnabledStatic($moduleKey)) {
                abort(404);
            }
        }

        $user = $request->user();

        if ($user && $user->isSuperAdmin()) {
            return $next($request);
        }

        return parent::handle($request, $next, $permission, $guard);
    }

    /**
     * @return array<int, string>
     */
    private function extractModuleKeys(string $permissionString): array
    {
        $moduleKeys = [];
        $permissions = array_filter(array_map('trim', explode('|', $permissionString)));

        foreach ($permissions as $permission) {
            if (! str_starts_with($permission, 'module.')) {
                continue;
            }

            $parts = explode('.', $permission);
            if (count($parts) < 3 || trim((string) ($parts[1] ?? '')) === '') {
                continue;
            }

            $moduleKeys[] = trim((string) $parts[1]);
        }

        return array_values(array_unique($moduleKeys));
    }
}
