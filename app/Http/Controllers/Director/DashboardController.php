<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\Quotation;
use App\Models\SalesTarget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();

        $monthlyRevenue = Booking::join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
            ->whereMonth('bookings.created_at', $now->month)
            ->whereYear('bookings.created_at', $now->year)
            ->sum('quotations.final_amount');

        $totalInquiry = Inquiry::count();
        $totalBooking = Booking::count();

        $conversionRate = $totalInquiry > 0
            ? round(($totalBooking / $totalInquiry) * 100, 2)
            : 0;

        $target = SalesTarget::where('year', $now->year)
            ->where('month', $now->month)
            ->first();

        $targetAmount = $target->target_amount ?? 0;

        $achievement = $targetAmount > 0
            ? round(($monthlyRevenue / $targetAmount) * 100, 2)
            : 0;

        $monthlyData = Booking::join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
            ->select(
                DB::raw('MONTH(bookings.created_at) as month'),
                DB::raw('SUM(quotations.final_amount) as total')
            )
            ->whereYear('bookings.created_at', $now->year)
            ->groupBy(DB::raw('MONTH(bookings.created_at)'))
            ->orderBy(DB::raw('MONTH(bookings.created_at)'))
            ->pluck('total', 'month');

        $deadlineQuotations = Quotation::where('status', 'sent')
            ->whereDate('validity_date', '<=', Carbon::now()->addDays(7))
            ->orderBy('validity_date')
            ->limit(5)
            ->get();

        $upcomingBookings = Booking::whereDate('travel_date', '>=', $now)
            ->orderBy('travel_date')
            ->limit(5)
            ->get();

        return view('director.dashboard', compact(
            'monthlyRevenue',
            'conversionRate',
            'targetAmount',
            'achievement',
            'monthlyData',
            'deadlineQuotations',
            'upcomingBookings'
        ));
    }
}
