<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\InquiryFollowUp;
use App\Models\Itinerary;
use App\Models\Quotation;
use App\Models\User;
use App\Services\ModuleService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $now = Carbon::now();
        $canInquiries = (bool) $user?->can('module.inquiries.access');
        $canQuotations = (bool) $user?->can('module.quotations.access');
        $canItineraries = (bool) $user?->can('module.itineraries.access');
        $bookingsModuleEnabled = ModuleService::isEnabledStatic('bookings');
        $canBookings = $bookingsModuleEnabled && (bool) $user?->can('module.bookings.access');

        // Manager dashboard scope: Manager + Marketing team data.
        $teamIds = User::role(['Manager', 'Marketing'])->pluck('id')->toArray();
        if ($user?->id && ! in_array($user->id, $teamIds, true)) {
            $teamIds[] = $user->id;
        }
        if (empty($teamIds) && $user?->id) {
            $teamIds = [$user->id];
        }

        $currentMonthStart = $now->copy()->startOfMonth();
        $currentMonthEnd = $now->copy()->endOfDay();

        $teamInquiryQuery = Inquiry::query()->whereIn('assigned_to', $teamIds);
        $teamQuotationQuery = Quotation::query()->whereHas('inquiry', fn (Builder $q) => $q->whereIn('assigned_to', $teamIds));
        $teamBookingQuery = Booking::query()
            ->join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
            ->join('inquiries', 'quotations.inquiry_id', '=', 'inquiries.id')
            ->whereIn('inquiries.assigned_to', $teamIds);

        $excludeCreatorScope = function (Builder $query) use ($user): void {
            if (! Schema::hasColumn('quotations', 'created_by')) {
                return;
            }

            $query->where(function (Builder $inner) use ($user): void {
                $inner->whereNull('created_by')
                    ->orWhere('created_by', '!=', (int) ($user?->id ?? 0));
            });
        };

        $totalInquiries = $canInquiries ? (clone $teamInquiryQuery)->count() : 0;
        $inquiriesThisMonth = $canInquiries
            ? (clone $teamInquiryQuery)->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])->count()
            : 0;

        $totalQuotations = $canQuotations ? (clone $teamQuotationQuery)->count() : 0;
        $quotationsThisMonth = $canQuotations
            ? (clone $teamQuotationQuery)->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])->count()
            : 0;

        $totalItineraries = $canItineraries
            ? Itinerary::query()->whereHas('inquiry', fn (Builder $q) => $q->whereIn('assigned_to', $teamIds))->count()
            : 0;

        $totalBookings = $canBookings ? (clone $teamBookingQuery)->count('bookings.id') : 0;
        $bookingsThisMonth = $canBookings
            ? (clone $teamBookingQuery)->whereBetween('bookings.created_at', [$currentMonthStart, $currentMonthEnd])->count('bookings.id')
            : 0;

        $kpis = [];
        $kpis['monthly_revenue'] = $canBookings
            ? (clone $teamBookingQuery)
                ->whereBetween('bookings.created_at', [$currentMonthStart, $currentMonthEnd])
                ->sum('quotations.final_amount')
            : 0;

        $kpis['conversion_rate'] = ($canBookings && $canInquiries && $totalInquiries > 0)
            ? round(($totalBookings / $totalInquiries) * 100, 1)
            : 0;

        $kpis['overdue_followups'] = $canInquiries
            ? InquiryFollowUp::query()
                ->where('is_done', false)
                ->whereDate('due_date', '<', $now->toDateString())
                ->whereHas('inquiry', fn (Builder $q) => $q->whereIn('assigned_to', $teamIds))
                ->count()
            : 0;

        $teamPendingQuotations = $canQuotations
            ? (clone $teamQuotationQuery)->where('status', 'pending')
            : Quotation::query()->whereRaw('1 = 0');

        $nonCreatorApprovalCountSql = Schema::hasColumn('quotations', 'created_by')
            ? '(SELECT COUNT(*) FROM quotation_approvals qa'
                .' WHERE qa.quotation_id = quotations.id'
                .' AND (quotations.created_by IS NULL OR qa.user_id <> quotations.created_by))'
            : '(SELECT COUNT(*) FROM quotation_approvals qa WHERE qa.quotation_id = quotations.id)';

        $needsReservationApprovalCount = $canQuotations
            ? (clone $teamPendingQuotations)
                ->whereRaw("{$nonCreatorApprovalCountSql} = 0")
                ->count()
            : 0;

        $needsManagerApprovalCount = $canQuotations
            ? (clone $teamPendingQuotations)
                ->where($excludeCreatorScope)
                ->whereDoesntHave('approvals', fn (Builder $q) => $q->where('user_id', (int) ($user?->id ?? 0)))
                ->whereRaw("{$nonCreatorApprovalCountSql} < 2")
                ->count()
            : 0;

        $needsDirectorApprovalCount = $canQuotations
            ? (clone $teamPendingQuotations)
                ->whereRaw("{$nonCreatorApprovalCountSql} = 1")
                ->count()
            : 0;

        $statsCards = [];

        if ($canBookings) {
            $statsCards[] = [
                'key' => 'revenue',
                'label' => __('ui.manager_dashboard.cards.team_revenue_mtd'),
                'value' => (int) round($kpis['monthly_revenue']),
                'caption' => __('ui.manager_dashboard.captions.date_bookings', ['date' => $now->format('Y-m-d')]),
                'tone' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
            ];
        }

        if ($canInquiries) {
            $statsCards[] = [
                'key' => 'inquiries',
                'label' => __('ui.manager_dashboard.cards.team_term', ['term' => ui_term('inquiries')]),
                'value' => $totalInquiries,
                'caption' => __('ui.manager_dashboard.captions.count_this_month', ['count' => number_format($inquiriesThisMonth)]),
                'tone' => 'bg-slate-50 text-slate-700 border-slate-100',
            ];
        }

        if ($canQuotations) {
            $statsCards[] = [
                'key' => 'quotations',
                'label' => __('ui.manager_dashboard.cards.team_term', ['term' => ui_term('quotations')]),
                'value' => $totalQuotations,
                'caption' => __('ui.manager_dashboard.captions.count_this_month', ['count' => number_format($quotationsThisMonth)]),
                'tone' => 'bg-sky-50 text-sky-700 border-sky-100',
            ];
        }

        if ($canBookings) {
            $statsCards[] = [
                'key' => 'bookings',
                'label' => __('ui.manager_dashboard.cards.team_term', ['term' => ui_term('bookings')]),
                'value' => $totalBookings,
                'caption' => __('ui.manager_dashboard.captions.count_this_month', ['count' => number_format($bookingsThisMonth)]),
                'tone' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
            ];
        }

        if ($canQuotations) {
            $statsCards[] = [
                'key' => 'manager_action_queue',
                'label' => __('ui.manager_dashboard.cards.manager_action_queue'),
                'value' => $needsManagerApprovalCount,
                'caption' => __('ui.manager_dashboard.captions.need_your_approval'),
                'tone' => 'bg-amber-50 text-amber-700 border-amber-100',
            ];
        }

        if ($canInquiries) {
            $statsCards[] = [
                'key' => 'overdue',
                'label' => __('ui.manager_dashboard.cards.overdue_followups'),
                'value' => $kpis['overdue_followups'],
                'caption' => __('ui.manager_dashboard.captions.past_due_followups'),
                'tone' => 'bg-rose-50 text-rose-700 border-rose-100',
            ];
        }

        // Standard dashboard KPI grid: max 6 cards.
        $statsCards = array_values(array_slice($statsCards, 0, 6));

        $funnel = [];
        if ($canInquiries) {
            $funnel[] = ['label' => ui_term('inquiries'), 'value' => $totalInquiries, 'is_percent' => false];
        }
        if ($canQuotations) {
            $funnel[] = ['label' => ui_term('quotations'), 'value' => $totalQuotations, 'is_percent' => false];
        }
        if ($canItineraries) {
            $funnel[] = ['label' => ui_term('itineraries'), 'value' => $totalItineraries, 'is_percent' => false];
        }
        if ($canBookings) {
            $funnel[] = ['label' => ui_term('bookings'), 'value' => $totalBookings, 'is_percent' => false];
        }
        if ($canBookings && $canInquiries) {
            $funnel[] = ['label' => __('ui.manager_dashboard.funnel.conversion_pct'), 'value' => $kpis['conversion_rate'], 'is_percent' => true];
        }

        $inquiryByStatus = $canInquiries
            ? (clone $teamInquiryQuery)
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
            : collect();

        $managerApprovalQueue = $canQuotations
            ? (clone $teamPendingQuotations)
                ->where($excludeCreatorScope)
                ->whereDoesntHave('approvals', fn (Builder $q) => $q->where('user_id', (int) ($user?->id ?? 0)))
                ->whereRaw("{$nonCreatorApprovalCountSql} < 2")
                ->with('inquiry.customer:id,name')
                ->orderByRaw('validity_date IS NULL, validity_date ASC')
                ->latest('id')
                ->limit(8)
                ->get(['id', 'quotation_number', 'status', 'validity_date', 'final_amount', 'inquiry_id'])
            : collect();

        $quotationStatusCounts = $canQuotations
            ? (clone $teamQuotationQuery)
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
            : collect();

        $upcomingFollowUps = $canInquiries
            ? InquiryFollowUp::query()
                ->where('is_done', false)
                ->whereHas('inquiry', fn (Builder $query) => $query->whereIn('assigned_to', $teamIds))
                ->with('inquiry:id,inquiry_number')
                ->orderBy('due_date')
                ->limit(7)
                ->get()
            : collect();

        $recentInquiries = $canInquiries
            ? (clone $teamInquiryQuery)
                ->with('customer:id,name', 'assignedUser:id,name')
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
            'managerApprovalQueue',
            'recentInquiries',
            'upcomingFollowUps',
            'needsReservationApprovalCount',
            'needsManagerApprovalCount',
            'needsDirectorApprovalCount',
            'quotationStatusCounts'
        ));
    }
}
