<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\InquiryFollowUp;
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
        $assignedId = $user?->id;

        $kpis = [];
        $funnel = [];

        // Data for Funnel & KPIs
        $totalInquiries = Inquiry::query()->where('assigned_to', $assignedId)->count();
        $totalQuotations = Quotation::query()->whereHas('inquiry', fn ($q) => $q->where('assigned_to', $assignedId))->count();
        $totalBookings = Booking::query()->whereHas('quotation.inquiry', fn ($q) => $q->where('assigned_to', $assignedId))->count();

        // KPI: My Revenue This Month
        $kpis['monthly_revenue'] = Booking::query()
            ->join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
            ->join('inquiries', 'quotations.inquiry_id', '=', 'inquiries.id')
            ->where('bookings.status', 'confirmed')
            ->where('inquiries.assigned_to', $assignedId)
            ->whereMonth('bookings.travel_date', $now->month)
            ->whereYear('bookings.travel_date', $now->year)
            ->sum('quotations.final_amount');
        
        // KPI: My Conversion Rate
        $kpis['conversion_rate'] = $totalInquiries > 0
            ? round(($totalBookings / $totalInquiries) * 100, 1)
            : 0;

        // KPI: My Active Inquiries (not closed or converted)
        $kpis['active_inquiries'] = Inquiry::query()
            ->where('assigned_to', $assignedId)
            ->whereNotIn('status', ['converted', 'closed'])
            ->count();

        // KPI: My Overdue Follow-ups
        $kpis['overdue_followups'] = InquiryFollowUp::query()
            ->where('is_done', false)
            ->whereDate('due_date', '<', $now->toDateString())
            ->whereHas('inquiry', fn ($q) => $q->where('assigned_to', $assignedId))
            ->count();

        // Build Funnel for the authenticated user
        $funnel = [
            ['label' => 'My Inquiries', 'value' => $totalInquiries],
            ['label' => 'My Quotations', 'value' => $totalQuotations],
            ['label' => 'My Bookings', 'value' => $totalBookings],
        ];

        // Inquiry stats by status
        $inquiryByStatus = Inquiry::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->where('assigned_to', $assignedId)
            ->groupBy('status')
            ->pluck('total', 'status');

        // Lists for Action Center
        $upcomingFollowUps = InquiryFollowUp::query()
            ->where('is_done', false)
            ->whereHas('inquiry', fn ($query) => $query->where('assigned_to', $assignedId))
            ->with('inquiry:id,inquiry_number')
            ->orderBy('due_date')
            ->limit(7)
            ->get();

        $recentInquiries = Inquiry::query()
            ->with('customer:id,name')
            ->where('assigned_to', $assignedId)
            ->latest()
            ->limit(5)
            ->get();

        return view('marketing.dashboard', compact(
            'user',
            'kpis',
            'funnel',
            'inquiryByStatus',
            'upcomingFollowUps',
            'recentInquiries'
        ));
    }
}
