<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\InquiryFollowUp;
use App\Models\Invoice;
use App\Models\Itinerary;
use App\Models\Module;
use App\Models\Quotation;
use App\Models\TouristAttraction;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index()
    {
        $today = now();

        $systemCounts = [
            'users' => User::query()->count(),
            'customers' => Customer::query()->count(),
            'inquiries' => Inquiry::query()->count(),
            'quotations' => Quotation::query()->count(),
            'bookings' => Booking::query()->count(),
            'invoices' => Invoice::query()->count(),
            'itineraries' => Itinerary::query()->count(),
            'vendors' => Vendor::query()->count(),
            'activities' => Activity::query()->count(),
            'tourist_attractions' => TouristAttraction::query()->count(),
        ];

        $moduleStats = [
            'enabled' => Module::query()->where('is_enabled', true)->count(),
            'disabled' => Module::query()->where('is_enabled', false)->count(),
            'total' => Module::query()->count(),
        ];

        $rolesAndPermissions = [
            'roles' => Role::query()->count(),
            'permissions' => Permission::query()->count(),
        ];

        $inquiryByStatus = Inquiry::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $quotationByStatus = Quotation::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $bookingByStatus = Booking::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $recentSystemHistory = $this->buildRecentSystemHistory();

        $operationalAlerts = [
            'pending_followups' => InquiryFollowUp::query()->where('is_done', false)->count(),
            'followups_due_today' => InquiryFollowUp::query()
                ->where('is_done', false)
                ->whereDate('due_date', $today->toDateString())
                ->count(),
            'followups_overdue' => InquiryFollowUp::query()
                ->where('is_done', false)
                ->whereDate('due_date', '<', $today->toDateString())
                ->count(),
            'quotations_expiring_7d' => Quotation::query()
                ->whereDate('validity_date', '>=', $today->toDateString())
                ->whereDate('validity_date', '<=', $today->copy()->addDays(7)->toDateString())
                ->count(),
            'upcoming_bookings_7d' => Booking::query()
                ->whereDate('travel_date', '>=', $today->toDateString())
                ->whereDate('travel_date', '<=', $today->copy()->addDays(7)->toDateString())
                ->count(),
        ];

        $upcomingFollowUps = InquiryFollowUp::query()
            ->with('inquiry:id,inquiry_number')
            ->where('is_done', false)
            ->orderBy('due_date')
            ->limit(8)
            ->get(['id', 'inquiry_id', 'due_date', 'channel', 'note', 'is_done']);

        $expiringQuotations = Quotation::query()
            ->orderBy('validity_date')
            ->limit(8)
            ->get(['id', 'quotation_number', 'status', 'validity_date']);

        $upcomingBookings = Booking::query()
            ->orderBy('travel_date')
            ->limit(8)
            ->get(['id', 'booking_number', 'status', 'travel_date']);

        $activityLogs = collect();
        if (Schema::hasTable('activity_log')) {
            $activityLogs = DB::table('activity_log')
                ->select(['id', 'log_name', 'description', 'subject_type', 'event', 'causer_id', 'created_at'])
                ->orderByDesc('created_at')
                ->limit(12)
                ->get();
        }

        $failedJobs = collect();
        $failedJobsCount = 0;
        $queueBacklogCount = 0;
        if (Schema::hasTable('jobs')) {
            $queueBacklogCount = DB::table('jobs')->count();
        }
        if (Schema::hasTable('failed_jobs')) {
            $failedJobs = DB::table('failed_jobs')
                ->select(['id', 'exception', 'failed_at'])
                ->orderByDesc('failed_at')
                ->limit(8)
                ->get();
            $failedJobsCount = DB::table('failed_jobs')->count();
        }

        $healthInfo = [
            'environment' => app()->environment(),
            'debug' => (bool) config('app.debug'),
            'queue_connection' => (string) config('queue.default'),
            'cache_driver' => (string) config('cache.default'),
            'session_driver' => (string) config('session.driver'),
            'database_connection' => (string) config('database.default'),
            'queue_backlog' => $queueBacklogCount,
            'failed_jobs' => $failedJobsCount,
        ];

        return view('superadmin.dashboard', compact(
            'systemCounts',
            'moduleStats',
            'rolesAndPermissions',
            'inquiryByStatus',
            'quotationByStatus',
            'bookingByStatus',
            'operationalAlerts',
            'upcomingFollowUps',
            'expiringQuotations',
            'upcomingBookings',
            'recentSystemHistory',
            'activityLogs',
            'failedJobs',
            'healthInfo'
        ));
    }

    public function trend(Request $request): JsonResponse
    {
        $period = (int) $request->integer('period', 30);
        if (! in_array($period, [7, 30, 90], true)) {
            $period = 30;
        }

        $refreshSeconds = (int) $request->integer('refresh', 45);
        if (! in_array($refreshSeconds, [30, 45, 60], true)) {
            $refreshSeconds = 45;
        }

        $endDate = now()->startOfDay();
        $startDate = $endDate->copy()->subDays($period - 1);

        $inquirySeries = $this->buildDailySeries(Inquiry::class, $startDate, $endDate);
        $quotationSeries = $this->buildDailySeries(Quotation::class, $startDate, $endDate);
        $bookingSeries = $this->buildDailySeries(Booking::class, $startDate, $endDate);

        $labels = [];
        $inquiries = [];
        $quotations = [];
        $bookings = [];
        $cursor = $startDate->copy();

        while ($cursor->lte($endDate)) {
            $key = $cursor->format('Y-m-d');
            $labels[] = $cursor->format('d M');
            $inquiries[] = (int) ($inquirySeries[$key] ?? 0);
            $quotations[] = (int) ($quotationSeries[$key] ?? 0);
            $bookings[] = (int) ($bookingSeries[$key] ?? 0);
            $cursor->addDay();
        }

        $bars = [
            Inquiry::query()->count(),
            Quotation::query()->count(),
            Booking::query()->count(),
            Itinerary::query()->count(),
            Vendor::query()->count(),
            Activity::query()->count(),
            InquiryFollowUp::query()->where('is_done', false)->count(),
            InquiryFollowUp::query()->where('is_done', false)->whereDate('due_date', '<', now()->toDateString())->count(),
            Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0,
            Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
        ];

        return response()->json([
            'period' => $period,
            'refresh_seconds' => $refreshSeconds,
            'labels' => $labels,
            'trend' => [
                'inquiries' => $inquiries,
                'quotations' => $quotations,
                'bookings' => $bookings,
            ],
            'bars' => $bars,
            'mix' => [
                'labels' => ['Inquiries', 'Quotations', 'Bookings', 'Itineraries'],
                'values' => [
                    Inquiry::query()->count(),
                    Quotation::query()->count(),
                    Booking::query()->count(),
                    Itinerary::query()->count(),
                ],
            ],
        ]);
    }

    private function buildRecentSystemHistory(): Collection
    {
        $users = User::query()->latest('updated_at')->limit(5)->get(['id', 'name', 'updated_at']);
        $customers = Customer::query()->latest('updated_at')->limit(5)->get(['id', 'name', 'updated_at']);
        $inquiries = Inquiry::query()->latest('updated_at')->limit(8)->get(['id', 'inquiry_number', 'status', 'updated_at']);
        $quotations = Quotation::query()->latest('updated_at')->limit(8)->get(['id', 'quotation_number', 'status', 'updated_at']);
        $bookings = Booking::query()->latest('updated_at')->limit(8)->get(['id', 'booking_number', 'status', 'updated_at']);
        $itineraries = Itinerary::query()->latest('updated_at')->limit(8)->get(['id', 'title', 'updated_at']);

        return collect()
            ->merge($users->map(fn ($item) => [
                'type' => 'User',
                'title' => $item->name,
                'meta' => 'Profile updated',
                'updated_at' => $item->updated_at,
            ]))
            ->merge($customers->map(fn ($item) => [
                'type' => 'Customer',
                'title' => $item->name,
                'meta' => 'Customer data changed',
                'updated_at' => $item->updated_at,
            ]))
            ->merge($inquiries->map(fn ($item) => [
                'type' => 'Inquiry',
                'title' => $item->inquiry_number,
                'meta' => 'Status: '.(string) $item->status,
                'updated_at' => $item->updated_at,
            ]))
            ->merge($quotations->map(fn ($item) => [
                'type' => 'Quotation',
                'title' => $item->quotation_number,
                'meta' => 'Status: '.(string) $item->status,
                'updated_at' => $item->updated_at,
            ]))
            ->merge($bookings->map(fn ($item) => [
                'type' => 'Booking',
                'title' => $item->booking_number,
                'meta' => 'Status: '.(string) $item->status,
                'updated_at' => $item->updated_at,
            ]))
            ->merge($itineraries->map(fn ($item) => [
                'type' => 'Itinerary',
                'title' => $item->title,
                'meta' => 'Schedule changed',
                'updated_at' => $item->updated_at,
            ]))
            ->sortByDesc('updated_at')
            ->take(15)
            ->values();
    }

    /**
     * @param  class-string<\Illuminate\Database\Eloquent\Model>  $modelClass
     * @return array<string, int>
     */
    private function buildDailySeries(string $modelClass, $startDate, $endDate): array
    {
        return $modelClass::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->whereBetween('created_at', [$startDate->copy()->startOfDay(), $endDate->copy()->endOfDay()])
            ->groupBy('day')
            ->pluck('total', 'day')
            ->map(fn ($value) => (int) $value)
            ->toArray();
    }
}
