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
        $catalogCounts = [
            'destinations' => Destination::query()->count(),
            'vendors' => Vendor::query()->count(),
            'activities' => Activity::query()->count(),
            'accommodations' => Accommodation::query()->count(),
        ];

        $recentDestinations = Destination::query()->latest()->limit(5)->get(['id', 'name', 'updated_at']);

        return view('editor.dashboard', compact(
            'catalogCounts',
            'recentDestinations'
        ));
    }
}
