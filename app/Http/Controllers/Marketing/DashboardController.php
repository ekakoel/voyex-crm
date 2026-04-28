<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\InquiryFollowUp;
use App\Models\Quotation;
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
        $assignedId = $user?->id;
        $canCustomers = (bool) $user?->can('module.customer_management.access');
        $canInquiries = (bool) $user?->can('module.inquiries.access');
        $canQuotations = (bool) $user?->can('module.quotations.access');
        $bookingsModuleEnabled = ModuleService::isEnabledStatic('bookings');
        $canBookings = $bookingsModuleEnabled && (bool) $user?->can('module.bookings.access');

        $kpis = [];
        $funnel = [];

        // Data for Funnel & KPIs
        $totalInquiries = $canInquiries
            ? Inquiry::query()->where('assigned_to', $assignedId)->count()
            : 0;
        $totalQuotations = $canQuotations
            ? Quotation::query()->whereHas('inquiry', fn ($q) => $q->where('assigned_to', $assignedId))->count()
            : 0;
        $totalBookings = $canBookings
            ? Booking::query()->whereHas('quotation.inquiry', fn ($q) => $q->where('assigned_to', $assignedId))->count()
            : 0;

        // KPI: My Revenue This Month
        $kpis['monthly_revenue'] = $canBookings
            ? Booking::query()
                ->join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
                ->join('inquiries', 'quotations.inquiry_id', '=', 'inquiries.id')
                ->where('bookings.status', 'confirmed')
                ->where('inquiries.assigned_to', $assignedId)
                ->whereMonth('bookings.travel_date', $now->month)
                ->whereYear('bookings.travel_date', $now->year)
                ->sum('quotations.final_amount')
            : 0;
        
        // KPI: My Conversion Rate
        $kpis['conversion_rate'] = ($canBookings && $canInquiries && $totalInquiries > 0)
            ? round(($totalBookings / $totalInquiries) * 100, 1)
            : 0;

        // KPI: My Active Inquiries (not closed or converted)
        $kpis['active_inquiries'] = $canInquiries
            ? Inquiry::query()
                ->where('assigned_to', $assignedId)
                ->whereNotIn('status', ['converted', 'closed'])
                ->count()
            : 0;

        // KPI: My Overdue Follow-ups
        $kpis['overdue_followups'] = $canInquiries
            ? InquiryFollowUp::query()
                ->where('is_done', false)
                ->whereDate('due_date', '<', $now->toDateString())
                ->whereHas('inquiry', fn ($q) => $q->where('assigned_to', $assignedId))
                ->count()
            : 0;

        // Build Funnel for the authenticated user
        $funnel = [];
        if ($canInquiries) {
            $funnel[] = ['label' => ui_phrase('marketing_dashboard_funnel_label', ['term' => ui_term('inquiries')]), 'value' => $totalInquiries];
        }
        if ($canQuotations) {
            $funnel[] = ['label' => ui_phrase('marketing_dashboard_funnel_label', ['term' => ui_term('quotations')]), 'value' => $totalQuotations];
        }
        if ($canBookings) {
            $funnel[] = ['label' => ui_phrase('marketing_dashboard_funnel_label', ['term' => ui_term('bookings')]), 'value' => $totalBookings];
        }

        // Inquiry stats by status
        $inquiryByStatus = $canInquiries
            ? Inquiry::query()
                ->select('status', DB::raw('COUNT(*) as total'))
                ->where('assigned_to', $assignedId)
                ->groupBy('status')
                ->pluck('total', 'status')
            : collect();

        // Lists for Action Center
        $upcomingFollowUps = $canInquiries
            ? InquiryFollowUp::query()
                ->where('is_done', false)
                ->whereHas('inquiry', fn ($query) => $query->where('assigned_to', $assignedId))
                ->with('inquiry:id,inquiry_number')
                ->orderBy('due_date')
                ->limit(7)
                ->get()
            : collect();

        $recentInquiries = $canInquiries
            ? Inquiry::query()
                ->with('customer:id,name')
                ->where('assigned_to', $assignedId)
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        return view('marketing.dashboard', compact(
            'user',
            'kpis',
            'canCustomers',
            'canInquiries',
            'canQuotations',
            'canBookings',
            'funnel',
            'inquiryByStatus',
            'upcomingFollowUps',
            'recentInquiries'
        ));
    }
}
