<?php

namespace App\Http\Controllers\Reservation;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\Itinerary;
use App\Models\Quotation;
use App\Services\ModuleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $userId = $user?->id;
        $canInquiries = (bool) $user?->can('module.inquiries.access');
        $canItineraries = (bool) $user?->can('module.itineraries.access');
        $canQuotations = (bool) $user?->can('module.quotations.access');
        $bookingsModuleEnabled = ModuleService::isEnabledStatic('bookings');
        $canBookings = $bookingsModuleEnabled && (bool) $user?->can('module.bookings.access');
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $kpis = [];
        $bookingStatusCounts = [];
        $bookingStatusBreakdown = [];
        $bookingCountMonth = 0;
        $topDestinationSummary = null;
        $slaDaysAvg = null;
        $overdueCloseCount = 0;
        $weeklyBookingTrend = [];
        $bookingByStaff = collect();
        $topCustomers = collect();

        $kpis['pending_closure'] = $canBookings
            ? Booking::query()
                ->where('status', 'confirmed')
                ->whereDate('travel_date', '<', $now->toDateString())
                ->count()
            : 0;
        
        $kpis['total_booked_value'] = $canBookings
            ? Booking::query()
                ->join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
                ->where('bookings.status', 'confirmed')
                ->sum('quotations.final_amount')
            : 0;

        $statusKeyMap = [
            'draft' => 'D',
            'pending' => 'P',
            'approved' => 'A',
            'rejected' => 'R',
            'final' => 'F',
        ];

        $statusSummary = function ($counts) use ($statusKeyMap): string {
            return collect($statusKeyMap)
                ->map(fn ($code, $status) => $code . (int) ($counts[$status] ?? 0))
                ->implode('-');
        };

        $inquiryCount = $canInquiries && $userId
            ? Inquiry::query()->where('assigned_to', $userId)->count()
            : 0;
        $inquiryStatusCounts = $canInquiries && $userId
            ? Inquiry::query()
                ->select('status', DB::raw('COUNT(*) as total'))
                ->where('assigned_to', $userId)
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray()
            : [];

        $itineraryCount = $canItineraries && $userId
            ? Itinerary::query()->where('created_by', $userId)->count()
            : 0;
        $itineraryStatusCounts = $canItineraries && $userId
            ? Itinerary::query()
                ->select('status', DB::raw('COUNT(*) as total'))
                ->where('created_by', $userId)
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray()
            : [];

        $quotationCount = $canQuotations && $userId
            ? Quotation::query()
                ->whereHas('inquiry', fn ($query) => $query->where('assigned_to', $userId))
                ->count()
            : 0;
        $quotationStatusCounts = $canQuotations && $userId
            ? Quotation::query()
                ->select('status', DB::raw('COUNT(*) as total'))
                ->whereHas('inquiry', fn ($query) => $query->where('assigned_to', $userId))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray()
            : [];

        $readyToBookQuotations = $canQuotations
            ? Quotation::query()
                ->where('status', 'approved')
                ->with('inquiry.customer:id,name')
                ->latest()
                ->limit(7)
                ->get()
            : collect();
        
        $upcomingTrips = $canBookings
            ? Booking::query()
                ->where('status', 'confirmed')
                ->whereDate('travel_date', '>=', $now)
                ->with('quotation.inquiry.customer:id,name')
                ->orderBy('travel_date')
                ->limit(5)
                ->get()
            : collect();
        
        $recentBookings = $canBookings
            ? Booking::query()
                ->with('quotation.inquiry.customer:id,name')
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        if ($canBookings) {
            $bookingStatusCounts = Booking::query()
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            $bookingCountMonth = Booking::query()
                ->whereBetween('created_at', [$startOfMonth, $now])
                ->count();

            $confirmedCount = (int) ($bookingStatusCounts['approved'] ?? 0)
                + (int) ($bookingStatusCounts['final'] ?? 0)
                + (int) ($bookingStatusCounts['processed'] ?? 0);

            $bookingStatusBreakdown = [
                ['label' => 'Pending', 'count' => (int) ($bookingStatusCounts['pending'] ?? 0), 'bg' => 'bg-amber-100', 'text' => 'text-amber-700'],
                ['label' => 'Confirmed', 'count' => $confirmedCount, 'bg' => 'bg-emerald-100', 'text' => 'text-emerald-700'],
                ['label' => 'Cancelled', 'count' => (int) ($bookingStatusCounts['rejected'] ?? 0), 'bg' => 'bg-rose-100', 'text' => 'text-rose-700'],
            ];

            $topDestinationRow = Booking::query()
                ->join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
                ->join('itineraries', 'quotations.itinerary_id', '=', 'itineraries.id')
                ->leftJoin('destinations', 'itineraries.destination_id', '=', 'destinations.id')
                ->whereBetween('bookings.created_at', [$startOfMonth, $now])
                ->selectRaw("COALESCE(destinations.name, itineraries.destination, 'Unknown') as destination_name, COUNT(*) as total")
                ->groupBy('destination_name')
                ->orderByDesc('total')
                ->first();

            if ($topDestinationRow) {
                $topDestinationSummary = $topDestinationRow->destination_name . ' (' . $topDestinationRow->total . ')';
            }

            $slaDaysAvg = Booking::query()
                ->join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
                ->whereNotNull('quotations.approved_at')
                ->avg(DB::raw('DATEDIFF(bookings.created_at, quotations.approved_at)'));

            $overdueCloseCount = Booking::query()
                ->whereDate('travel_date', '<', $now->toDateString())
                ->whereNotIn('status', ['final', 'rejected'])
                ->count();

            $weekStart = $now->copy()->startOfWeek()->subWeeks(5);
            $weekKeys = collect(range(0, 5))
                ->map(fn ($offset) => $weekStart->copy()->addWeeks($offset));

            $weeklyCounts = Booking::query()
                ->where('created_at', '>=', $weekStart)
                ->selectRaw('YEARWEEK(created_at, 1) as year_week, COUNT(*) as total')
                ->groupBy('year_week')
                ->pluck('total', 'year_week')
                ->toArray();

            $weeklyBookingTrend = $weekKeys
                ->map(function ($weekDate) use ($weeklyCounts) {
                    $key = $weekDate->format('oW');
                    return [
                        'label' => $weekDate->format('d M'),
                        'count' => (int) ($weeklyCounts[$key] ?? 0),
                    ];
                })
                ->toArray();

            $bookingByStaff = Booking::query()
                ->join('users', 'bookings.created_by', '=', 'users.id')
                ->whereBetween('bookings.created_at', [$startOfMonth, $now])
                ->select('users.name', DB::raw('COUNT(*) as total'))
                ->groupBy('users.name')
                ->orderByDesc('total')
                ->limit(5)
                ->get();

            $topCustomers = Booking::query()
                ->join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
                ->join('inquiries', 'quotations.inquiry_id', '=', 'inquiries.id')
                ->join('customers', 'inquiries.customer_id', '=', 'customers.id')
                ->whereBetween('bookings.created_at', [$startOfMonth, $now])
                ->select('customers.name', DB::raw('SUM(quotations.final_amount) as total_value'), DB::raw('COUNT(*) as total_bookings'))
                ->groupBy('customers.name')
                ->orderByDesc('total_value')
                ->limit(3)
                ->get();
        }

        return view('reservation.dashboard', compact(
            'kpis',
            'canInquiries',
            'canItineraries',
            'canQuotations',
            'canBookings',
            'inquiryCount',
            'itineraryCount',
            'quotationCount',
            'inquiryStatusCounts',
            'itineraryStatusCounts',
            'quotationStatusCounts',
            'statusSummary',
            'readyToBookQuotations',
            'upcomingTrips',
            'recentBookings',
            'bookingStatusCounts',
            'bookingStatusBreakdown',
            'bookingCountMonth',
            'topDestinationSummary',
            'slaDaysAvg',
            'overdueCloseCount',
            'weeklyBookingTrend',
            'bookingByStaff',
            'topCustomers'
        ));
    }
}
