<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\SalesTarget;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $now = Carbon::now();
        $salesTeamIds = [];

        // Determine data scope based on role
        if ($user->hasRole('Sales Manager')) {
            // Sales Manager sees the whole sales team data
            $salesTeamIds = User::role(['Sales Manager', 'Sales Agent'])->pluck('id')->toArray();
        } else {
            // Sales Agent sees only their own data
            $salesTeamIds = [$user->id];
        }

        // 1. Monthly Revenue (team or individual based)
        $monthlyRevenue = Booking::join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
            ->join('inquiries', 'quotations.inquiry_id', '=', 'inquiries.id')
            ->whereIn('inquiries.assigned_to', $salesTeamIds)
            ->whereMonth('bookings.created_at', $now->month)
            ->whereYear('bookings.created_at', $now->year)
            ->sum('quotations.final_amount');

        // 2. Conversion Rate (team or individual based)
        $totalInquiry = Inquiry::whereIn('assigned_to', $salesTeamIds)->count();
        $totalBooking = Booking::join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
            ->join('inquiries', 'quotations.inquiry_id', '=', 'inquiries.id')
            ->whereIn('inquiries.assigned_to', $salesTeamIds)
            ->count();

        $conversionRate = $totalInquiry > 0
            ? round(($totalBooking / $totalInquiry) * 100, 2)
            : 0;

        // 3. Target vs Achievement (sales target applies to the whole team)
        $target = SalesTarget::where('year', $now->year)
            ->where('month', $now->month)
            ->first();

        $targetAmount = $target->target_amount ?? 0;

        $achievement = $targetAmount > 0
            ? round(($monthlyRevenue / $targetAmount) * 100, 2)
            : 0;

        // 4. Inquiries that need follow-up (status 'new' or 'follow_up')
        $pendingInquiries = Inquiry::with('customer')
            ->whereIn('status', ['new', 'follow_up'])
            ->whereIn('assigned_to', $salesTeamIds)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('sales.dashboard', compact(
            'user',
            'monthlyRevenue',
            'conversionRate',
            'targetAmount',
            'achievement',
            'pendingInquiries'
        ));
    }
}
