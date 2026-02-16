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

        // Role priority when a user has more than one role.
        if ($user->hasRole('Admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('Director')) {
            return redirect()->route('director.dashboard');
        }

        if ($user->hasRole('Finance')) {
            return redirect()->route('finance.dashboard');
        }

        if ($user->hasRole('Operations')) {
            return redirect()->route('operations.dashboard');
        }

        if ($user->hasAnyRole(['Sales Manager', 'Sales Agent'])) {
            return redirect()->route('sales.dashboard');
        }

        abort(403, 'This role has no dashboard.');
    }
}
