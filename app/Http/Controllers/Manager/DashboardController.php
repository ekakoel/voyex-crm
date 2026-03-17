<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\InquiryFollowUp;
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

        // A manager sees data from their whole team (Marketing + other Managers)
        $teamIds = User::role(['Manager', 'Marketing'])->pluck('id')->toArray();

        $kpis = [];
        $funnel = [];

        // KPI: Team Revenue This Month
        $kpis['monthly_revenue'] = Booking::query()
            ->join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
            ->join('inquiries', 'quotations.inquiry_id', '=', 'inquiries.id')
            ->where('bookings.status', 'confirmed')
            ->whereIn('inquiries.assigned_to', $teamIds)
            ->whereMonth('bookings.travel_date', $now->month)
            ->whereYear('bookings.travel_date', $now->year)
            ->sum('quotations.final_amount');

        // Data for Funnel & Conversion Rate
        $totalInquiries = Inquiry::query()->whereIn('assigned_to', $teamIds)->count();
        $totalQuotations = Quotation::query()->whereHas('inquiry', fn ($q) => $q->whereIn('assigned_to', $teamIds))->count();
        $totalBookings = Booking::query()->whereHas('quotation.inquiry', fn ($q) => $q->whereIn('assigned_to', $teamIds))->count();

        // KPI: Conversion Rate (Inquiry to Booking)
        $kpis['conversion_rate'] = $totalInquiries > 0
            ? round(($totalBookings / $totalInquiries) * 100, 1)
            : 0;
        
        // KPI: Pending Quotations
        $kpis['pending_quotations'] = Quotation::query()
            ->where('status', 'pending')
            ->whereHas('inquiry', fn ($q) => $q->whereIn('assigned_to', $teamIds))
            ->count();

        // KPI: Overdue Follow-ups
        $kpis['overdue_followups'] = InquiryFollowUp::query()
            ->where('is_done', false)
            ->whereDate('due_date', '<', $now->toDateString())
            ->whereHas('inquiry', fn ($q) => $q->whereIn('assigned_to', $teamIds))
            ->count();
            
        // Build Funnel
        $funnel = [
            ['label' => 'Inquiries', 'value' => $totalInquiries],
            ['label' => 'Quotations', 'value' => $totalQuotations],
            ['label' => 'Bookings', 'value' => $totalBookings],
        ];

        // Inquiry stats by status
        $inquiryByStatus = Inquiry::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->whereIn('assigned_to', $teamIds)
            ->groupBy('status')
            ->pluck('total', 'status');

        // Lists for Action Center
        $pendingQuotationsList = Quotation::query()
            ->where('status', 'pending')
            ->whereHas('inquiry', fn ($query) => $query->whereIn('assigned_to', $teamIds))
            ->with('inquiry.customer:id,name')
            ->latest()
            ->limit(5)
            ->get();

        $upcomingFollowUps = InquiryFollowUp::query()
            ->where('is_done', false)
            ->whereHas('inquiry', fn ($query) => $query->whereIn('assigned_to', $teamIds))
            ->with('inquiry:id,inquiry_number')
            ->orderBy('due_date')
            ->limit(7)
            ->get();
            
        $recentInquiries = Inquiry::query()
            ->with('customer:id,name', 'assignedUser:id,name')
            ->whereIn('assigned_to', $teamIds)
            ->latest()
            ->limit(5)
            ->get();

        return view('manager.dashboard', compact(
            'user',
            'kpis',
            'funnel',
            'inquiryByStatus',
            'pendingQuotationsList',
            'recentInquiries',
            'upcomingFollowUps'
        ));
    }
}
