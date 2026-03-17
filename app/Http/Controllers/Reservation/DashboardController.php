<?php

namespace App\Http\Controllers\Reservation;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Quotation;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now();
        $kpis = [];

        $kpis['ready_to_book'] = Quotation::query()
            ->where('status', 'approved')
            ->count();

        $kpis['upcoming_trips'] = Booking::query()
            ->where('status', 'confirmed')
            ->whereDate('travel_date', '>=', $now->toDateString())
            ->count();

        $kpis['pending_closure'] = Booking::query()
            ->where('status', 'confirmed')
            ->whereDate('travel_date', '<', $now->toDateString())
            ->count();
        
        $kpis['total_booked_value'] = Booking::query()
            ->join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
            ->where('bookings.status', 'confirmed')
            ->sum('quotations.final_amount');

        $readyToBookQuotations = Quotation::query()
            ->where('status', 'approved')
            ->with('inquiry.customer:id,name')
            ->latest()
            ->limit(7)
            ->get();
        
        $upcomingTrips = Booking::query()
            ->where('status', 'confirmed')
            ->whereDate('travel_date', '>=', $now)
            ->with('quotation.inquiry.customer:id,name')
            ->orderBy('travel_date')
            ->limit(5)
            ->get();
        
        $recentBookings = Booking::query()
            ->with('quotation.inquiry.customer:id,name')
            ->latest()
            ->limit(5)
            ->get();

        return view('reservation.dashboard', compact(
            'kpis',
            'readyToBookQuotations',
            'upcomingTrips',
            'recentBookings'
        ));
    }
}
