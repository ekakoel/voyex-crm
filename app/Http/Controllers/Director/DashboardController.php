<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Inquiry;
use App\Models\Quotation;
use App\Services\ModuleService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
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
        $bookingsModuleEnabled = ModuleService::isEnabledStatic('bookings');
        $canBookings = $bookingsModuleEnabled && (bool) $user?->can('module.bookings.access');
        $currentMonthStart = $now->copy()->startOfMonth();
        $previousMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $previousMonthEnd = $previousMonthStart->copy()->endOfMonth();

        $monthlyRevenue = $canBookings
            ? Booking::join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
                ->whereBetween('bookings.created_at', [$currentMonthStart, $now])
                ->sum('quotations.final_amount')
            : 0;
        $previousMonthlyRevenue = $canBookings
            ? Booking::join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
                ->whereBetween('bookings.created_at', [$previousMonthStart, $previousMonthEnd])
                ->sum('quotations.final_amount')
            : 0;
        $revenueGrowthPercent = $previousMonthlyRevenue > 0
            ? round((($monthlyRevenue - $previousMonthlyRevenue) / $previousMonthlyRevenue) * 100, 2)
            : ($monthlyRevenue > 0 ? 100 : 0);

        $totalInquiry = $canInquiries ? Inquiry::count() : 0;
        $inquiriesThisMonth = $canInquiries
            ? Inquiry::query()->whereBetween('created_at', [$currentMonthStart, $now])->count()
            : 0;

        $totalQuotation = $canQuotations ? Quotation::query()->count() : 0;
        $quotationStatusCounts = $canQuotations
            ? Quotation::query()
                ->select('status', DB::raw('COUNT(*) as total'))
                ->groupBy('status')
                ->pluck('total', 'status')
            : collect();

        $totalBooking = $canBookings ? Booking::count() : 0;
        $bookingsThisMonth = $canBookings
            ? Booking::query()->whereBetween('created_at', [$currentMonthStart, $now])->count()
            : 0;

        $conversionRate = ($canBookings && $canInquiries && $totalInquiry > 0)
            ? round(($totalBooking / $totalInquiry) * 100, 2)
            : 0;

        $monthlyDataRaw = $canBookings
            ? Booking::join('quotations', 'bookings.quotation_id', '=', 'quotations.id')
                ->select(
                    DB::raw('MONTH(bookings.created_at) as month'),
                    DB::raw('SUM(quotations.final_amount) as total')
                )
                ->whereYear('bookings.created_at', $now->year)
                ->groupBy(DB::raw('MONTH(bookings.created_at)'))
                ->orderBy(DB::raw('MONTH(bookings.created_at)'))
                ->pluck('total', 'month')
            : collect();

        $monthlyData = collect(range(1, 12))->map(function (int $month) use ($monthlyDataRaw) {
            return [
                'month' => $month,
                'label' => Carbon::create()->locale((string) app()->getLocale())->month($month)->translatedFormat('M'),
                'total' => (float) ($monthlyDataRaw[$month] ?? 0),
            ];
        });

        $maxMonthlyRevenue = (float) $monthlyData->max('total');
        $nonCreatorApprovalCountSql = '(SELECT COUNT(*) FROM quotation_approvals qa'
            .' WHERE qa.quotation_id = quotations.id'
            .' AND (quotations.created_by IS NULL OR qa.user_id <> quotations.created_by))';

        $needsReservationApprovalCount = $canQuotations
            ? Quotation::query()
                ->where('status', 'pending')
                ->whereRaw("{$nonCreatorApprovalCountSql} = 0")
                ->count()
            : 0;
        $needsManagerApprovalCount = $canQuotations
            ? Quotation::query()
                ->where('status', 'pending')
                ->whereRaw("{$nonCreatorApprovalCountSql} = 1")
                ->count()
            : 0;
        $needsDirectorApprovalCount = $canQuotations
            ? Quotation::query()
                ->where('status', 'pending')
                ->where(function (Builder $q) use ($user): void {
                    $q->whereNull('created_by')
                        ->orWhere('created_by', '!=', (int) ($user?->id ?? 0));
                })
                ->whereDoesntHave('approvals', fn (Builder $q) => $q->where('user_id', (int) ($user?->id ?? 0)))
                ->whereRaw("{$nonCreatorApprovalCountSql} < 2")
                ->count()
            : 0;

        $pendingApprovals = $canQuotations
            ? Quotation::query()
                ->with(['inquiry.customer'])
                ->where('status', 'pending')
                ->where(function (Builder $q) use ($user): void {
                    $q->whereNull('created_by')
                        ->orWhere('created_by', '!=', (int) ($user?->id ?? 0));
                })
                ->whereDoesntHave('approvals', fn (Builder $q) => $q->where('user_id', (int) ($user?->id ?? 0)))
                ->whereRaw("{$nonCreatorApprovalCountSql} < 2")
                ->orderByRaw('validity_date IS NULL, validity_date ASC')
                ->orderByDesc('id')
                ->limit(8)
                ->get(['id', 'quotation_number', 'status', 'validity_date', 'final_amount', 'inquiry_id'])
            : collect();

        $upcomingBookings = $canBookings
            ? Booking::query()
                ->with(['quotation:id,quotation_number,inquiry_id', 'quotation.inquiry:id,customer_id', 'quotation.inquiry.customer:id,name'])
                ->whereDate('travel_date', '>=', $now)
                ->orderBy('travel_date')
                ->limit(6)
                ->get(['id', 'booking_number', 'quotation_id', 'travel_date', 'status'])
            : collect();

        return view('director.dashboard', compact(
            'user',
            'monthlyRevenue',
            'previousMonthlyRevenue',
            'revenueGrowthPercent',
            'conversionRate',
            'monthlyData',
            'maxMonthlyRevenue',
            'pendingApprovals',
            'upcomingBookings',
            'totalInquiry',
            'inquiriesThisMonth',
            'totalQuotation',
            'quotationStatusCounts',
            'totalBooking',
            'bookingsThisMonth',
            'needsReservationApprovalCount',
            'needsManagerApprovalCount',
            'needsDirectorApprovalCount',
            'canInquiries',
            'canQuotations',
            'canBookings'
        ));
    }
}
