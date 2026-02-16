<?php

namespace App\Http\Middleware;

use App\Services\ModuleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        if (! ModuleService::isEnabledStatic($moduleKey)) {
            abort(404);
        }

        return $next($request);
    }
}
