<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\User;
use App\Services\ModuleService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $now = Carbon::now();
        $salesTeamIds = [];
        $canInquiries = (bool) $user?->can('module.inquiries.access');
        $bookingsModuleEnabled = ModuleService::isEnabledStatic('bookings');
        $canBookings = $bookingsModuleEnabled && (bool) $user?->can('module.bookings.access');

        // Determine data scope based on permission
        if ($user?->can('dashboard.manager.view')) {
            // Manager sees the whole sales team data
            $salesTeamIds = User::role(['Manager', 'Marketing'])->pluck('id')->toArray();
        } else {
            // Marketing sees only their own data
            $salesTeamIds = [$user->id];
        }

        // 1. Monthly Revenue (team or individual based)
        $monthlyRevenue = $canBookings
            ? Booking::join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
                ->join('inquiries', 'quotations.inquiry_id', '=', 'inquiries.id')
                ->whereIn('inquiries.assigned_to', $salesTeamIds)
                ->whereMonth('bookings.created_at', $now->month)
                ->whereYear('bookings.created_at', $now->year)
                ->sum('quotations.final_amount')
            : 0;

        // 2. Conversion Rate (team or individual based)
        $totalInquiry = $canInquiries ? Inquiry::whereIn('assigned_to', $salesTeamIds)->count() : 0;
        $totalBooking = $canBookings
            ? Booking::join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
                ->join('inquiries', 'quotations.inquiry_id', '=', 'inquiries.id')
                ->whereIn('inquiries.assigned_to', $salesTeamIds)
                ->count()
            : 0;

        $conversionRate = ($canBookings && $canInquiries && $totalInquiry > 0)
            ? round(($totalBooking / $totalInquiry) * 100, 2)
            : 0;

        // 4. Inquiries that need follow-up (status 'draft' or 'processed')
        $pendingInquiries = $canInquiries
            ? Inquiry::with('customer')
                ->whereIn('status', ['draft', 'processed'])
                ->whereIn('assigned_to', $salesTeamIds)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
            : collect();

        return view('sales.dashboard', compact(
            'user',
            'monthlyRevenue',
            'conversionRate',
            'pendingInquiries',
            'canInquiries',
            'canBookings'
        ));
    }
}
