<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Booking;
use App\Models\Quotation;
use App\Models\SalesTarget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
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
        | 5. Target vs Achievement
        |--------------------------------------------------------------------------
        */
        $target = SalesTarget::where('year', $now->year)
            ->where('month', $now->month)
            ->first();

        $targetAmount = $target->target_amount ?? 0;

        $achievement = $targetAmount > 0
            ? round(($monthlyRevenue / $targetAmount) * 100, 2)
            : 0;

        /*
        |--------------------------------------------------------------------------
        | 6. Monthly Revenue Chart (Fix Ambiguous)
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

        return view('admin.dashboard', compact(
            'monthlyRevenue',
            'conversionRate',
            'deadlineQuotations',
            'upcomingBookings',
            'targetAmount',
            'achievement',
            'monthlyData'
        ));
    }
}
