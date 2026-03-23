<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\Accommodation;
use App\Models\Activity;
use App\Models\Airport;
use App\Models\Destination;
use App\Models\FoodBeverage;
use App\Models\TouristAttraction;
use App\Models\Transport;
use App\Models\Vendor;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $canDestinations = (bool) $user?->can('module.destinations.access');
        $canVendors = (bool) $user?->can('module.vendor_management.access');
        $canActivities = (bool) $user?->can('module.activities.access');
        $canAccommodations = (bool) $user?->can('module.accommodations.access');

        $catalogCounts = [
            'destinations' => $canDestinations ? Destination::query()->count() : 0,
            'vendors' => $canVendors ? Vendor::query()->count() : 0,
            'activities' => $canActivities ? Activity::query()->count() : 0,
            'accommodations' => $canAccommodations ? Accommodation::query()->count() : 0,
        ];

        $recentDestinations = $canDestinations
            ? Destination::query()->latest()->limit(5)->get(['id', 'name', 'updated_at'])
            : collect();

        return view('editor.dashboard', compact(
            'catalogCounts',
            'recentDestinations',
            'canDestinations',
            'canVendors',
            'canActivities',
            'canAccommodations'
        ));
    }
}
