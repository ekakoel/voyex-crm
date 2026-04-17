<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;

class DashboardRedirectController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * This method checks the user's role and redirects them
     * to the appropriate dashboard.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (! $user) {
            abort(403, 'Unauthenticated.');
        }

        // Permission-first dashboard routing.
        // Priority matters when a user has multiple dashboard permissions.
        $dashboardPermissionPriority = [
            'dashboard.superadmin.view' => 'superadmin.dashboard',
            'dashboard.administrator.view' => 'dashboard.administrator',
            'dashboard.director.view' => 'dashboard.director',
            'dashboard.finance.view' => 'dashboard.finance',
            'dashboard.reservation.view' => 'dashboard.reservation',
            'dashboard.manager.view' => 'dashboard.manager',
            'dashboard.marketing.view' => 'dashboard.marketing',
            'dashboard.editor.view' => 'dashboard.editor',
        ];

        foreach ($dashboardPermissionPriority as $permission => $routeName) {
            if ($user->can($permission)) {
                return redirect()->route($routeName);
            }
        }

        abort(403, 'This user has no dashboard permission.');
    }
}
