<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\InquiryFollowUp;
use App\Models\Itinerary;
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
        $canInquiries = (bool) $user?->can('module.inquiries.access');
        $canQuotations = (bool) $user?->can('module.quotations.access');
        $canItineraries = (bool) $user?->can('module.itineraries.access');
        $canBookings = (bool) $user?->can('module.bookings.access');

        // A manager sees data from their whole team (Marketing + other Managers)
        $teamIds = User::role(['Manager', 'Marketing'])->pluck('id')->toArray();
        if ($user?->id && !in_array($user->id, $teamIds, true)) {
            $teamIds[] = $user->id;
        }
        if (empty($teamIds) && $user?->id) {
            $teamIds = [$user->id];
        }

        $kpis = [];
        $funnel = [];

        // KPI: Team Revenue This Month
        $kpis['monthly_revenue'] = $canBookings
            ? Booking::query()
                ->join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
                ->join('inquiries', 'quotations.inquiry_id', '=', 'inquiries.id')
                ->whereIn('bookings.status', ['processed', 'approved', 'final'])
                ->whereIn('inquiries.assigned_to', $teamIds)
                ->whereMonth('bookings.travel_date', $now->month)
                ->whereYear('bookings.travel_date', $now->year)
                ->sum('quotations.final_amount')
            : 0;

        // Data for Funnel & Conversion Rate
        $totalInquiries = $canInquiries
            ? Inquiry::query()->count()
            : 0;
        $totalQuotations = $canQuotations
            ? Quotation::query()->count()
            : 0;
        $totalItineraries = $canItineraries
            ? Itinerary::query()->count()
            : 0;
        $totalBookings = $canBookings
            ? Booking::query()->count()
            : 0;

        // KPI: Conversion Rate (Inquiry to Booking)
        $kpis['conversion_rate'] = ($canBookings && $canInquiries && $totalInquiries > 0)
            ? round(($totalBookings / $totalInquiries) * 100, 1)
            : 0;
        
        // KPI: Pending Quotations
        $kpis['pending_quotations'] = $canQuotations
            ? Quotation::query()
                ->where('status', 'pending')
                ->whereHas('inquiry', fn ($q) => $q->whereIn('assigned_to', $teamIds))
                ->count()
            : 0;

        // KPI: Overdue Follow-ups
        $kpis['overdue_followups'] = $canInquiries
            ? InquiryFollowUp::query()
                ->where('is_done', false)
                ->whereDate('due_date', '<', $now->toDateString())
                ->whereHas('inquiry', fn ($q) => $q->whereIn('assigned_to', $teamIds))
                ->count()
            : 0;

        $statsCards = [];
        if ($canBookings) {
            $statsCards[] = [
                'key' => 'revenue',
                'label' => 'Revenue (Month)',
                'value' => (int) round($kpis['monthly_revenue']),
                'caption' => $now->format('M Y'),
                'tone' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            ];
        }
        if ($canInquiries) {
            $statsCards[] = [
                'key' => 'inquiries',
                'label' => 'Team Inquiries',
                'value' => $totalInquiries,
                'caption' => 'Total assigned',
                'tone' => 'bg-slate-50 text-slate-700 border-slate-100',
            ];
        }
        if ($canBookings) {
            $statsCards[] = [
                'key' => 'bookings',
                'label' => 'Bookings',
                'value' => $totalBookings,
                'caption' => 'Converted',
                'tone' => 'bg-sky-50 text-sky-700 border-sky-100',
            ];
        }
        if ($canBookings && $canInquiries) {
            $statsCards[] = [
                'key' => 'sales',
                'label' => 'Conversion Rate',
                'value' => (int) round($kpis['conversion_rate']),
                'caption' => 'Inquiry → Booking (%)',
                'tone' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
            ];
        }
        if ($canQuotations) {
            $statsCards[] = [
                'key' => 'pending',
                'label' => 'Pending Approvals',
                'value' => $kpis['pending_quotations'],
                'caption' => 'Awaiting action',
                'tone' => 'bg-amber-50 text-amber-700 border-amber-100',
            ];
        }
        if ($canInquiries) {
            $statsCards[] = [
                'key' => 'inactive',
                'label' => 'Overdue Follow-ups',
                'value' => $kpis['overdue_followups'],
                'caption' => 'Past due',
                'tone' => 'bg-rose-50 text-rose-700 border-rose-100',
            ];
        }
            
        // Build Funnel
        $funnel = [];
        if ($canInquiries) {
            $funnel[] = ['label' => 'Inquiries', 'value' => $totalInquiries];
        }
        if ($canQuotations) {
            $funnel[] = ['label' => 'Quotations', 'value' => $totalQuotations];
        }
        if ($canItineraries) {
            $funnel[] = ['label' => 'Itineraries', 'value' => $totalItineraries];
        }
        if ($canBookings) {
            $funnel[] = ['label' => 'Bookings', 'value' => $totalBookings];
        }

        // Inquiry stats by status
        $inquiryByStatus = $canInquiries
            ? Inquiry::query()
                ->select('status', DB::raw('COUNT(*) as total'))
                ->whereIn('assigned_to', $teamIds)
                ->groupBy('status')
                ->pluck('total', 'status')
            : collect();

        // Lists for Action Center
        $pendingQuotationsList = $canQuotations
            ? Quotation::query()
                ->where('status', 'pending')
                ->whereHas('inquiry', fn ($query) => $query->whereIn('assigned_to', $teamIds))
                ->with('inquiry.customer:id,name')
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        $upcomingFollowUps = $canInquiries
            ? InquiryFollowUp::query()
                ->where('is_done', false)
                ->whereHas('inquiry', fn ($query) => $query->whereIn('assigned_to', $teamIds))
                ->with('inquiry:id,inquiry_number')
                ->orderBy('due_date')
                ->limit(7)
                ->get()
            : collect();
            
        $recentInquiries = $canInquiries
            ? Inquiry::query()
                ->with('customer:id,name', 'assignedUser:id,name')
                ->whereIn('assigned_to', $teamIds)
                ->latest()
                ->limit(5)
                ->get()
            : collect();

        return view('manager.dashboard', compact(
            'user',
            'kpis',
            'statsCards',
            'canInquiries',
            'canQuotations',
            'canItineraries',
            'canBookings',
            'funnel',
            'inquiryByStatus',
            'pendingQuotationsList',
            'recentInquiries',
            'upcomingFollowUps'
        ));
    }
}
