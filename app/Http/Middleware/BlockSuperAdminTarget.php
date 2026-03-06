<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockSuperAdminTarget
{
    /**
     * Block access when route target user is Super Admin.
     */
    public function handle(Request $request, Closure $next, string $parameter = 'user'): Response
    {
        $target = $request->route($parameter);

        if (is_numeric($target)) {
            $target = User::query()->find((int) $target);
        }

        if ($target instanceof User && $target->isSuperAdmin()) {
            abort(404);
        }

        return $next($request);
    }
}

