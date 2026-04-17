<?php

namespace App\Http\Controllers\Administrator;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Destination;
use App\Models\FoodBeverage;
use App\Models\Inquiry;
use App\Models\Module;
use App\Models\Quotation;
use App\Models\TouristAttraction;
use App\Models\Transport;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ModuleService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $canUsers = (bool) $user?->can('module.user_manager.access');
        $canRoles = (bool) $user?->can('module.role_manager.access');
        $canServices = (bool) $user?->can('module.service_manager.access');
        $canCustomers = (bool) $user?->can('module.customer_management.access');
        $canInquiries = (bool) $user?->can('module.inquiries.access');
        $canQuotations = (bool) $user?->can('module.quotations.access');
        $bookingsModuleEnabled = ModuleService::isEnabledStatic('bookings');
        $canBookings = $bookingsModuleEnabled && (bool) $user?->can('module.bookings.access');
        $canVendors = (bool) $user?->can('module.vendor_management.access');
        $canDestinations = (bool) $user?->can('module.destinations.access');
        $canActivities = (bool) $user?->can('module.activities.access');
        $canTransports = (bool) $user?->can('module.transports.access');
        $canAttractions = (bool) $user?->can('module.tourist_attractions.access');
        $canFoodBeverages = (bool) $user?->can('module.food_beverages.access');
        $canAirports = (bool) $user?->can('module.airports.access');

        $userBaseQuery = User::query()->withoutSuperAdmin();
        $roleBaseQuery = Role::query()->where('name', '!=', 'Super Admin');

        $systemManagementStats = [
            'users' => $canUsers ? (clone $userBaseQuery)->count() : 0,
            'roles' => $canRoles ? (clone $roleBaseQuery)->count() : 0,
            'permissions' => ($canUsers || $canRoles) ? Permission::query()->count() : 0,
            'modules_enabled' => $canServices ? Module::query()->where('is_enabled', true)->count() : 0,
            'modules_disabled' => $canServices ? Module::query()->where('is_enabled', false)->count() : 0,
        ];

        $operationalStats = [
            'customers' => $canCustomers ? Customer::query()->count() : 0,
            'inquiries' => $canInquiries ? Inquiry::query()->count() : 0,
            'quotations' => $canQuotations ? Quotation::query()->count() : 0,
            'bookings' => $canBookings ? Booking::query()->count() : 0,
        ];

        $masterDataStats = [
            'vendors' => $canVendors ? Vendor::query()->count() : 0,
            'destinations' => $canDestinations ? Destination::query()->count() : 0,
            'activities' => $canActivities ? Activity::query()->count() : 0,
            'transports' => $canTransports ? Transport::query()->count() : 0,
            'tourist_attractions' => $canAttractions ? TouristAttraction::query()->count() : 0,
            'food_beverages' => $canFoodBeverages ? FoodBeverage::query()->count() : 0,
            'airports' => $canAirports ? Airport::query()->count() : 0,
        ];

        $pendingQuotations = $canQuotations
            ? Quotation::query()
                ->where('status', 'pending')
                ->latest()
                ->limit(5)
                ->with('inquiry:id,customer_id', 'inquiry.customer:id,name')
                ->get()
            : collect();

        $recentUsers = $canUsers
            ? User::query()
                ->withoutSuperAdmin()
                ->latest('updated_at')
                ->limit(5)
                ->get()
            : collect();

        return view('administrator.dashboard', compact(
            'systemManagementStats',
            'operationalStats',
            'masterDataStats',
            'pendingQuotations',
            'recentUsers',
            'canUsers',
            'canRoles',
            'canServices',
            'canCustomers',
            'canInquiries',
            'canQuotations',
            'canBookings',
            'canVendors',
            'canDestinations',
            'canActivities',
            'canTransports',
            'canAttractions',
            'canFoodBeverages',
            'canAirports'
        ));
    }
}
