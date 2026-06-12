<?php

namespace App\Http\Controllers\Reservation;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\Itinerary;
use App\Models\Quotation;
use App\Services\ModuleService;
use App\Support\Concerns\ResolvesInquiryHandler;
use App\Support\InquiryDeadlineReminder;
use App\Support\SchemaInspector;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ResolvesInquiryHandler;

    public function index()
    {
        $user = auth()->user();
        $userId = (int) ($user?->id ?? 0);
        $now = Carbon::now();
        $today = $now->copy()->startOfDay();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $canInquiries = (bool) $user?->can('module.inquiries.access');
        $canItineraries = (bool) $user?->can('module.itineraries.access');
        $canQuotations = (bool) $user?->can('module.quotations.access');
        $canBookings = ModuleService::isEnabledStatic('bookings') && (bool) $user?->can('module.bookings.access');

        $activeBookingStatuses = ['created', 'vendor_confirmation', 'voucher_preparation', 'pending_confirmation', 'confirmed', 'awaiting_dp', 'dp_received', 'awaiting_balance', 'ready_to_operate', 'in_operation', 'service_completed', 'reconciliation', 'invoiced', 'completed_unsettled', 'completed_settled'];
        $needValidationStatuses = [Quotation::STATUS_NEED_VALIDATION, Quotation::STATUS_PENDING_VALIDATION];
        $readyToSendStatuses = [Quotation::STATUS_READY_TO_SEND, Quotation::STATUS_VALIDATED];
        $approvedStatuses = [Quotation::STATUS_APPROVED, Quotation::STATUS_CUSTOMER_APPROVED];
        $openQuotationStatuses = array_merge([Quotation::STATUS_DRAFT, Quotation::STATUS_SENT], $needValidationStatuses, $readyToSendStatuses, $approvedStatuses);
        $activeInquiryStatuses = ['new_request', 'need_customer_data', 'registered', 'assigned', 'contacted', 'waiting_customer', 'qualified', 'itinerary_in_progress', 'quotation_in_progress', 'quotation_sent', 'under_negotiation', 'accepted'];

        $myInquiryQuery = $canInquiries && $userId > 0
            ? $this->myInquiryQuery($user)->whereIn('status', $activeInquiryStatuses)
            : null;
        $myQuotationQuery = $canQuotations && $userId > 0
            ? $this->myQuotationQuery($user)
            : null;
        $myBookingQuery = $canBookings && $userId > 0
            ? $this->myBookingQuery($user)
            : null;

        $deadlineWatch = $canInquiries && $userId > 0
            ? InquiryDeadlineReminder::queryForUser($user)
                ->with(['customer:id,name,company_name', 'handledBy:id,name', 'assignedTo:id,name'])
                ->orderBy('deadline')
                ->latest('id')
                ->limit(6)
                ->get(['id', 'inquiry_number', 'customer_id', 'status', 'priority', 'deadline', 'handled_by', 'assigned_to'])
            : collect();

        $activeInquiryCount = $myInquiryQuery ? (clone $myInquiryQuery)->count() : 0;
        $unquotedInquiryCount = $myInquiryQuery
            ? (clone $myInquiryQuery)->whereDoesntHave('quotation')->count()
            : 0;
        $deadlineWatchCount = $canInquiries && $userId > 0
            ? InquiryDeadlineReminder::queryForUser($user)->count()
            : 0;
        $overdueInquiryCount = $myInquiryQuery
            ? (clone $myInquiryQuery)
                ->whereDoesntHave('quotation')
                ->whereNotNull('deadline')
                ->whereDate('deadline', '<', $today->toDateString())
                ->count()
            : 0;

        $itineraryCount = $canItineraries && $userId > 0
            ? Itinerary::query()
                ->where('created_by', $userId)
                ->whereNull('deleted_at')
                ->count()
            : 0;
        $itineraryDraftCount = $canItineraries && $userId > 0
            ? Itinerary::query()
                ->where('created_by', $userId)
                ->whereNull('deleted_at')
                ->whereIn('status', ['draft', 'in_progress', 'revised'])
                ->count()
            : 0;

        $openQuotationCount = $myQuotationQuery
            ? (clone $myQuotationQuery)->whereIn('status', $openQuotationStatuses)->count()
            : 0;
        $quotationDraftCount = $myQuotationQuery
            ? (clone $myQuotationQuery)->where('status', Quotation::STATUS_DRAFT)->count()
            : 0;
        $quotationPendingValidationCount = $myQuotationQuery
            ? (clone $myQuotationQuery)->whereIn('status', $needValidationStatuses)->count()
            : 0;
        $quotationSentCount = $myQuotationQuery
            ? (clone $myQuotationQuery)->where('status', Quotation::STATUS_SENT)->count()
            : 0;

        $readyToBookQuery = $myQuotationQuery
            ? (clone $myQuotationQuery)
                ->whereIn('status', $approvedStatuses)
                ->whereDoesntHave('booking')
            : null;
        $readyToBookCount = $readyToBookQuery ? (clone $readyToBookQuery)->count() : 0;
        $readyToBookQuotations = $readyToBookQuery
            ? (clone $readyToBookQuery)
                ->with(['inquiry.customer:id,name,company_name'])
                ->latest('updated_at')
                ->limit(6)
                ->get(['id', 'quotation_number', 'order_number', 'inquiry_id', 'status', 'final_amount', 'updated_at'])
            : collect();

        $upcomingTripsQuery = $myBookingQuery
            ? (clone $myBookingQuery)
                ->whereIn('status', $activeBookingStatuses)
                ->whereDate('travel_date', '>=', $today->toDateString())
                ->whereDate('travel_date', '<=', $today->copy()->addDays(14)->toDateString())
            : null;
        $upcomingTrips = $upcomingTripsQuery
            ? (clone $upcomingTripsQuery)
                ->with(['quotation.inquiry.customer:id,name,company_name'])
                ->orderBy('travel_date')
                ->limit(6)
                ->get(['id', 'booking_number', 'quotation_id', 'travel_date', 'status'])
            : collect();
        $upcomingTripsCount = $upcomingTripsQuery ? (clone $upcomingTripsQuery)->count() : 0;

        $pendingClosureCount = $myBookingQuery
            ? (clone $myBookingQuery)
                ->whereNotIn('status', ['closed', 'cancelled'])
                ->whereDate('travel_date', '<', $today->toDateString())
                ->count()
            : 0;
        $monthlyBookingCount = $myBookingQuery
            ? (clone $myBookingQuery)
                ->whereBetween('bookings.created_at', [$startOfMonth, $now])
                ->count('bookings.id')
            : 0;
        $monthlyBookedValue = $myBookingQuery
            ? (float) (clone $myBookingQuery)
                ->join('quotations as booking_quotations', 'bookings.quotation_id', '=', 'booking_quotations.id')
                ->whereBetween('bookings.created_at', [$startOfMonth, $now])
                ->sum('booking_quotations.final_amount')
            : 0.0;

        $pipelineStages = [
            ['label' => ui_phrase('Assigned Inquiries'), 'value' => $activeInquiryCount, 'icon' => 'inbox', 'color' => 'amber'],
            ['label' => ui_phrase('Itineraries'), 'value' => $itineraryCount, 'icon' => 'route', 'color' => 'indigo'],
            ['label' => ui_phrase('Open Quotations'), 'value' => $openQuotationCount, 'icon' => 'file-invoice-dollar', 'color' => 'teal'],
            ['label' => ui_phrase('Ready to Book'), 'value' => $readyToBookCount, 'icon' => 'calendar-plus', 'color' => 'emerald'],
            ['label' => ui_phrase('Upcoming Trips'), 'value' => $upcomingTripsCount, 'icon' => 'plane-departure', 'color' => 'sky'],
        ];

        $statsCards = [
            ['label' => ui_phrase('Deadline Watch'), 'value' => $deadlineWatchCount, 'caption' => ui_phrase('Needs action by priority'), 'icon' => 'hourglass-half', 'color' => $deadlineWatchCount > 0 ? 'rose' : 'slate'],
            ['label' => ui_phrase('Unquoted Inquiries'), 'value' => $unquotedInquiryCount, 'caption' => ui_phrase('Assigned and no quotation'), 'icon' => 'file-circle-plus', 'color' => 'amber'],
            ['label' => ui_phrase('Ready to Book'), 'value' => $readyToBookCount, 'caption' => ui_phrase('Customer approved'), 'icon' => 'calendar-check', 'color' => 'emerald'],
            ['label' => ui_phrase('Trips Next 14 Days'), 'value' => $upcomingTripsCount, 'caption' => ui_phrase('Operational attention'), 'icon' => 'plane-departure', 'color' => 'sky'],
        ];

        $quotationWorkbench = [
            ['label' => ui_phrase('Draft'), 'value' => $quotationDraftCount, 'color' => 'slate'],
            ['label' => ui_phrase('Pending Validation'), 'value' => $quotationPendingValidationCount, 'color' => 'amber'],
            ['label' => ui_phrase('Sent'), 'value' => $quotationSentCount, 'color' => 'sky'],
            ['label' => ui_phrase('Ready to Book'), 'value' => $readyToBookCount, 'color' => 'emerald'],
        ];

        $travelBars = $this->buildTravelBars($upcomingTripsQuery, $today);
        $recentInquiries = $myInquiryQuery
            ? (clone $myInquiryQuery)
                ->with(['customer:id,name,company_name', 'quotation:id,inquiry_id,status'])
                ->latest('updated_at')
                ->limit(6)
                ->get(['id', 'inquiry_number', 'customer_id', 'status', 'priority', 'deadline', 'updated_at'])
            : collect();

        return view('reservation.dashboard', compact(
            'canInquiries',
            'canItineraries',
            'canQuotations',
            'canBookings',
            'statsCards',
            'pipelineStages',
            'quotationWorkbench',
            'deadlineWatch',
            'readyToBookQuotations',
            'upcomingTrips',
            'recentInquiries',
            'travelBars',
            'activeInquiryCount',
            'unquotedInquiryCount',
            'overdueInquiryCount',
            'itineraryDraftCount',
            'openQuotationCount',
            'monthlyBookingCount',
            'monthlyBookedValue',
            'pendingClosureCount'
        ));
    }

    private function myInquiryQuery($user): Builder
    {
        return Inquiry::query()
            ->whereNull('deleted_at')
            ->where(function (Builder $query) use ($user): void {
                $this->applyInquiryHandlerScope($query, (int) $user->id, 'inquiries');
            });
    }

    private function myQuotationQuery($user): Builder
    {
        return Quotation::query()
            ->whereNull('quotations.deleted_at')
            ->whereHas('inquiry', function (Builder $query) use ($user): void {
                $this->applyInquiryOwnershipScope($query, $user);
            });
    }

    private function myBookingQuery($user): Builder
    {
        return Booking::query()
            ->whereHas('quotation.inquiry', function (Builder $query) use ($user): void {
                $this->applyInquiryOwnershipScope($query, $user);
            });
    }

    private function applyInquiryOwnershipScope(Builder $query, $user): void
    {
        $this->applyInquiryHandlerScope($query, (int) $user->id, 'inquiries');
    }

    private function buildTravelBars(?Builder $upcomingTripsQuery, Carbon $today): array
    {
        if (! $upcomingTripsQuery) {
            return [];
        }

        $days = collect(range(0, 13))
            ->map(fn (int $offset) => $today->copy()->addDays($offset));
        $counts = (clone $upcomingTripsQuery)
            ->selectRaw('DATE(travel_date) as travel_day, COUNT(*) as total')
            ->groupBy('travel_day')
            ->pluck('total', 'travel_day')
            ->toArray();
        $max = max(array_map('intval', array_values($counts)) ?: [0]);

        return $days
            ->map(function (Carbon $date) use ($counts, $max): array {
                $count = (int) ($counts[$date->toDateString()] ?? 0);

                return [
                    'label' => $date->format('d M'),
                    'count' => $count,
                    'height' => $max > 0 ? max(10, (int) round(($count / $max) * 78)) : 10,
                ];
            })
            ->all();
    }
}
