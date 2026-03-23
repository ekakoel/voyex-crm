<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\Quotation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $now = Carbon::now();
        $canInquiries = (bool) $user?->can('module.inquiries.access');
        $canQuotations = (bool) $user?->can('module.quotations.access');
        $canBookings = (bool) $user?->can('module.bookings.access');

        $monthlyRevenue = $canBookings
            ? Booking::join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
                ->whereMonth('bookings.created_at', $now->month)
                ->whereYear('bookings.created_at', $now->year)
                ->sum('quotations.final_amount')
            : 0;

        $totalInquiry = $canInquiries ? Inquiry::count() : 0;
        $totalBooking = $canBookings ? Booking::count() : 0;

        $conversionRate = ($canBookings && $canInquiries && $totalInquiry > 0)
            ? round(($totalBooking / $totalInquiry) * 100, 2)
            : 0;

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

        $pendingApprovals = $canQuotations
            ? Quotation::where('status', 'pending')
                ->latest()
                ->limit(5)
                ->get(['id', 'quotation_number', 'status', 'validity_date'])
            : collect();

        $upcomingBookings = $canBookings
            ? Booking::whereDate('travel_date', '>=', $now)
                ->orderBy('travel_date')
                ->limit(5)
                ->get()
            : collect();

        return view('director.dashboard', compact(
            'user',
            'monthlyRevenue',
            'conversionRate',
            'monthlyData',
            'pendingApprovals',
            'upcomingBookings',
            'canInquiries',
            'canQuotations',
            'canBookings'
        ));
    }
}
