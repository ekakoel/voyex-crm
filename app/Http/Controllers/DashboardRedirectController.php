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
        if ($user->hasRole('Super Admin')) {
            return redirect()->route('superadmin.dashboard');
        }

        if ($user->hasRole('Administrator')) {
            return redirect()->route('dashboard.administrator');
        }

        if ($user->hasRole('Editor')) {
            return redirect()->route('dashboard.editor');
        }

        if ($user->hasRole('Director')) {
            return redirect()->route('dashboard.director');
        }

        if ($user->hasRole('Finance')) {
            return redirect()->route('dashboard.finance');
        }

        if ($user->hasRole('Reservation')) {
            return redirect()->route('dashboard.reservation');
        }

        if ($user->hasRole('Manager')) {
            return redirect()->route('dashboard.manager');
        }

        if ($user->hasRole('Marketing')) {
            return redirect()->route('dashboard.marketing');
        }

        abort(403, 'This role has no dashboard.');
    }
}

