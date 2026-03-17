<?php

namespace App\Http\Controllers\Administrator;

use App\Http\Controllers\Controller;
use App\Models\Accommodation;
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
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index()
    {
        $systemManagementStats = [
            'users' => User::query()->count(),
            'roles' => Role::query()->count(),
            'permissions' => Permission::query()->count(),
            'modules_enabled' => Module::query()->where('is_enabled', true)->count(),
            'modules_disabled' => Module::query()->where('is_enabled', false)->count(),
        ];

        $operationalStats = [
            'customers' => Customer::query()->count(),
            'inquiries' => Inquiry::query()->count(),
            'quotations' => Quotation::query()->count(),
            'bookings' => Booking::query()->count(),
        ];

        $masterDataStats = [
            'vendors' => Vendor::query()->count(),
            'destinations' => Destination::query()->count(),
            'activities' => Activity::query()->count(),
            'accommodations' => Accommodation::query()->count(),
            'transports' => Transport::query()->count(),
            'tourist_attractions' => TouristAttraction::query()->count(),
            'food_beverages' => FoodBeverage::query()->count(),
            'airports' => Airport::query()->count(),
        ];

        $pendingQuotations = Quotation::query()
            ->where('status', 'pending')
            ->latest()
            ->limit(5)
            ->with('inquiry:id,customer_id', 'inquiry.customer:id,name')
            ->get();

        $recentUsers = User::query()
            ->latest('updated_at')
            ->limit(5)
            ->get();

        return view('administrator.dashboard', compact(
            'systemManagementStats',
            'operationalStats',
            'masterDataStats',
            'pendingQuotations',
            'recentUsers'
        ));
    }
}
