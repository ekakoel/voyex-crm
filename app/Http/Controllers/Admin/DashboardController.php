<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CompanySetting;
use App\Models\Inquiry;
use App\Models\Module;
use App\Models\Quotation;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $now = Carbon::now();

        /*
        |--------------------------------------------------------------------------
        | 1. Monthly Revenue
        |--------------------------------------------------------------------------
        | Use bookings.created_at to avoid ambiguity
        */
        $monthlyRevenue = Booking::join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
            ->whereMonth('bookings.created_at', $now->month)
            ->whereYear('bookings.created_at', $now->year)
            ->sum('quotations.final_amount');

        /*
        |--------------------------------------------------------------------------
        | 2. Conversion Rate
        |--------------------------------------------------------------------------
        */
        $totalInquiry = Inquiry::count();
        $totalBooking = Booking::count();

        $conversionRate = $totalInquiry > 0
            ? round(($totalBooking / $totalInquiry) * 100, 2)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | 3. Deadline Quotations (Next 7 Days)
        |--------------------------------------------------------------------------
        */
        $deadlineQuotations = Quotation::where('status', 'sent')
            ->whereDate('validity_date', '<=', Carbon::now()->addDays(7))
            ->orderBy('validity_date')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 4. Upcoming Bookings
        |--------------------------------------------------------------------------
        */
        $upcomingBookings = Booking::whereDate('travel_date', '>=', $now)
            ->orderBy('travel_date')
            ->limit(5)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | 5. Monthly Revenue Chart (Fix Ambiguous)
        |--------------------------------------------------------------------------
        */
        $monthlyData = Booking::join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
            ->select(
                DB::raw('MONTH(bookings.created_at) as month'),
                DB::raw('SUM(quotations.final_amount) as total')
            )
            ->whereYear('bookings.created_at', $now->year)
            ->groupBy(DB::raw('MONTH(bookings.created_at)'))
            ->orderBy(DB::raw('MONTH(bookings.created_at)'))
            ->pluck('total', 'month');

        $isAdminUser = (bool) ($user?->hasRole('Admin User') && ! $user?->hasRole('Admin'));
        $dashboardTitle = $isAdminUser ? 'Company Admin Dashboard' : 'Admin Dashboard';
        $dashboardSubtitle = $isAdminUser
            ? "Fokus pengelolaan perusahaan, user internal, dan modul operasional."
            : "Welcome back, {$user?->name}. Here's your performance overview.";

        $teamStats = [
            'total_users' => User::query()->count(),
            'sales_team' => User::role(['Sales Manager', 'Sales Agent'])->count(),
            'operations' => User::role('Operations')->count(),
            'finance' => User::role('Finance')->count(),
        ];

        $managedModuleKeys = [
            'customer_management',
            'inquiries',
            'itineraries',
            'quotations',
            'bookings',
            'invoices',
            'vendor_management',
            'destinations',
            'activities',
            'accommodations',
            'airports',
            'transports',
            'tourist_attractions',
            'user_manager',
            'quotation_templates',
        ];

        $managedModules = Module::query()
            ->whereIn('key', $managedModuleKeys)
            ->orderBy('name')
            ->get(['key', 'name', 'is_enabled'])
            ->map(function (Module $module) use ($user): array {
                $permission = "module.{$module->key}.access";

                return [
                    'key' => $module->key,
                    'name' => $module->name,
                    'is_enabled' => (bool) $module->is_enabled,
                    'can_access' => (bool) $user?->can($permission),
                ];
            })
            ->values();

        $moduleGovernance = [
            'visible' => $managedModules->where('can_access', true)->count(),
            'enabled' => $managedModules->where('can_access', true)->where('is_enabled', true)->count(),
            'disabled' => $managedModules->where('can_access', true)->where('is_enabled', false)->count(),
        ];

        $companyProfileReady = CompanySetting::query()->exists();

        return view('admin.dashboard', compact(
            'monthlyRevenue',
            'conversionRate',
            'deadlineQuotations',
            'upcomingBookings',
            'monthlyData',
            'isAdminUser',
            'dashboardTitle',
            'dashboardSubtitle',
            'teamStats',
            'managedModules',
            'moduleGovernance',
            'companyProfileReady'
        ));
    }
}
