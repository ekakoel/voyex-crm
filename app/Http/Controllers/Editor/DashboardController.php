<?php

namespace App\Http\Controllers\Editor;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityLog;
use App\Models\Destination;
use App\Models\FoodBeverage;
use App\Models\TouristAttraction;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Builder;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $canDestinations = (bool) $user?->can('module.destinations.access');
        $canVendors = (bool) $user?->can('module.vendor_management.access');
        $canActivities = (bool) $user?->can('module.activities.access');
        $canAttractions = (bool) $user?->can('module.tourist_attractions.access');
        $canFoodBeverages = (bool) $user?->can('module.food_beverages.access');
        $canManualQueue = (bool) (
            $user?->can('itineraries.manual_item_queue.view')
        );

        $catalogCounts = [
            'destinations' => $canDestinations ? Destination::query()->count() : 0,
            'vendors' => $canVendors ? Vendor::query()->count() : 0,
            'activities' => $canActivities ? Activity::query()->count() : 0,
            'attractions' => $canAttractions ? TouristAttraction::query()->count() : 0,
            'food_beverages' => $canFoodBeverages ? FoodBeverage::query()->count() : 0,
        ];

        $pendingManualItemsCount = 0;
        $recentPendingManualItems = collect();
        $myValidatedTodayCount = 0;
        $recentlyValidatedByMe = collect();
        if ($canManualQueue) {
            $pendingManualQuery = $this->pendingManualItemValidationQuery($user);
            $pendingManualItemsCount = (int) (clone $pendingManualQuery)->count();
            $recentPendingManualItems = (clone $pendingManualQuery)
                ->latest('id')
                ->limit(8)
                ->get();

            $myValidatedTodayQuery = ActivityLog::query()
                ->where('module', 'itinerary_day_planner')
                ->where('action', 'manual_item_created')
                ->whereRaw("JSON_EXTRACT(properties, '$.validated_by') = ?", [(int) ($user?->id ?? 0)])
                ->whereDate('updated_at', today());
            $myValidatedTodayCount = (int) (clone $myValidatedTodayQuery)->count();
            $recentlyValidatedByMe = (clone $myValidatedTodayQuery)
                ->latest('updated_at')
                ->limit(6)
                ->get();
        }

        return view('editor.dashboard', compact(
            'catalogCounts',
            'canDestinations',
            'canVendors',
            'canActivities',
            'canAttractions',
            'canFoodBeverages',
            'canManualQueue',
            'pendingManualItemsCount',
            'recentPendingManualItems',
            'myValidatedTodayCount',
            'recentlyValidatedByMe',
        ));
    }

    private function pendingManualItemValidationQuery($user): Builder
    {
        return ActivityLog::query()
            ->where('module', 'itinerary_day_planner')
            ->where('action', 'manual_item_created')
            ->where(function (Builder $query) use ($user): void {
                $query->whereNull('user_id')
                    ->orWhere('user_id', '!=', (int) ($user?->id ?? 0));
            })
            ->where(function (Builder $query): void {
                $query->whereNull('properties')
                    ->orWhereRaw("JSON_EXTRACT(properties, '$.validated_at') IS NULL");
            });
    }
}
