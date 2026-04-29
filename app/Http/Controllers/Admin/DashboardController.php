<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CompanySetting;
use App\Models\Inquiry;
use App\Models\Module;
use App\Models\Quotation;
use App\Models\User;
use App\Services\ModuleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $now = Carbon::now();
        $canUsers = (bool) $user?->can('module.user_manager.access');
        $canRoles = (bool) $user?->can('module.role_manager.access');
        $canServices = (bool) $user?->can('module.service_manager.access');
        $canInquiries = (bool) $user?->can('module.inquiries.access');
        $canQuotations = (bool) $user?->can('module.quotations.access');
        $bookingsModuleEnabled = ModuleService::isEnabledStatic('bookings');
        $canBookings = $bookingsModuleEnabled && (bool) $user?->can('module.bookings.access');

        /*
        |--------------------------------------------------------------------------
        | 1. Monthly Revenue
        |--------------------------------------------------------------------------
        | Use bookings.created_at to avoid ambiguity
        */
        $monthlyRevenue = $canBookings
            ? Booking::join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
                ->whereMonth('bookings.created_at', $now->month)
                ->whereYear('bookings.created_at', $now->year)
                ->sum('quotations.final_amount')
            : 0;

        /*
        |--------------------------------------------------------------------------
        | 2. Conversion Rate
        |--------------------------------------------------------------------------
        */
        $totalInquiry = $canInquiries ? Inquiry::count() : 0;
        $totalBooking = $canBookings ? Booking::count() : 0;

        $conversionRate = ($canBookings && $canInquiries && $totalInquiry > 0)
            ? round(($totalBooking / $totalInquiry) * 100, 2)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | 3. Deadline Quotations (Next 7 Days)
        |--------------------------------------------------------------------------
        */
        $deadlineQuotations = $canQuotations
            ? Quotation::where('status', 'processed')
                ->whereDate('validity_date', '<=', Carbon::now()->addDays(7))
                ->orderBy('validity_date')
                ->get()
            : collect();

        /*
        |--------------------------------------------------------------------------
        | 4. Upcoming Bookings
        |--------------------------------------------------------------------------
        */
        $upcomingBookings = $canBookings
            ? Booking::whereDate('travel_date', '>=', $now)
                ->orderBy('travel_date')
                ->limit(5)
                ->get()
            : collect();

        /*
        |--------------------------------------------------------------------------
        | 5. Monthly Revenue Chart (Fix Ambiguous)
        |--------------------------------------------------------------------------
        */
        $monthlyData = $canBookings
            ? Booking::join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
                ->select(
                    DB::raw('MONTH(bookings.created_at) as month'),
                    DB::raw('SUM(quotations.final_amount) as total')
                )
                ->whereYear('bookings.created_at', $now->year)
                ->groupBy(DB::raw('MONTH(bookings.created_at)'))
                ->orderBy(DB::raw('MONTH(bookings.created_at)'))
                ->pluck('total', 'month')
            : collect();

        $isEditor = (bool) ($user?->can('dashboard.editor.view') && ! $user?->can('dashboard.administrator.view'));
        $dashboardTitle = $isEditor
            ? ui_phrase('Editor Dashboard')
            : ui_phrase('Administrator Dashboard');
        $dashboardSubtitle = $isEditor
            ? ui_phrase('Focus on content management and service catalog quality.')
            : ui_phrase('Welcome back, :name. Here\'s your performance overview.', ['name' => $user?->name]);

        $userBaseQuery = User::query()->withoutSuperAdmin();

        $teamStats = [
            'total_users' => $canUsers ? (clone $userBaseQuery)->count() : 0,
            'sales_team' => $canUsers ? User::role(['Manager', 'Marketing'])->count() : 0,
            'operations' => $canUsers ? User::role('Reservation')->count() : 0,
            'finance' => $canUsers ? User::role('Finance')->count() : 0,
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
            'hotels',
            'airports',
            'transports',
            'tourist_attractions',
            'user_manager',
        ];

        $managedModules = $canServices
            ? Module::query()
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
                ->values()
            : collect();

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
            'isEditor',
            'dashboardTitle',
            'dashboardSubtitle',
            'teamStats',
            'managedModules',
            'moduleGovernance',
            'companyProfileReady',
            'canUsers',
            'canRoles',
            'canServices',
            'canInquiries',
            'canQuotations',
            'canBookings'
        ));
    }
}
