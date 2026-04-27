<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Currency;
use App\Models\Destination;
use App\Models\FoodBeverage;
use App\Models\Hotel;
use App\Models\Inquiry;
use App\Models\InquiryFollowUp;
use App\Models\Invoice;
use App\Models\Itinerary;
use App\Models\Module;
use App\Models\Quotation;
use App\Models\TouristAttraction;
use App\Models\Transport;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ModuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index()
    {
        $payload = Cache::remember('superadmin:dashboard:index:v2', now()->addSeconds(60), function (): array {
            $today = now();
            $bookingsModuleEnabled = ModuleService::isEnabledStatic('bookings');

            $systemCounts = [
                'users' => User::query()->count(),
                'customers' => Customer::query()->count(),
                'inquiries' => Inquiry::query()->count(),
                'quotations' => Quotation::query()->count(),
                'bookings' => $bookingsModuleEnabled ? Booking::query()->count() : 0,
                'invoices' => Invoice::query()->count(),
                'itineraries' => Itinerary::query()->count(),
                'vendors' => Vendor::query()->count(),
                'activities' => Activity::query()->count(),
                'destinations' => Destination::query()->count(),
                'airports' => Airport::query()->count(),
                'tourist_attractions' => TouristAttraction::query()->count(),
                'food_beverages' => FoodBeverage::query()->count(),
                'hotels' => Hotel::query()->count(),
                'transports' => Transport::query()->count(),
                'currencies' => Currency::query()->count(),
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

            $bookingByStatus = $bookingsModuleEnabled
                ? Booking::query()
                    ->select('status', DB::raw('COUNT(*) as total'))
                    ->groupBy('status')
                    ->pluck('total', 'status')
                    ->toArray()
                : [];

            $recentSystemHistory = $this->buildRecentSystemHistory($bookingsModuleEnabled);

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
                'upcoming_bookings_7d' => $bookingsModuleEnabled
                    ? Booking::query()
                        ->whereDate('travel_date', '>=', $today->toDateString())
                        ->whereDate('travel_date', '<=', $today->copy()->addDays(7)->toDateString())
                        ->count()
                    : 0,
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

            $upcomingBookings = $bookingsModuleEnabled
                ? Booking::query()
                    ->orderBy('travel_date')
                    ->limit(8)
                    ->get(['id', 'booking_number', 'status', 'travel_date'])
                : collect();

            $activityLogs = collect();
            if (Schema::hasTable('activity_logs')) {
                $activityLogs = DB::table('activity_logs')
                    ->select(['id', 'module', 'action', 'subject_type', 'user_id', 'created_at'])
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

            $actionCenter = [
                [
                    'label_key' => 'ui.superadmin.action_center.followups_overdue.label',
                    'value' => (int) ($operationalAlerts['followups_overdue'] ?? 0),
                    'severity' => ($operationalAlerts['followups_overdue'] ?? 0) > 0 ? 'critical' : 'ok',
                    'hint_key' => 'ui.superadmin.action_center.followups_overdue.hint',
                ],
                [
                    'label_key' => 'ui.superadmin.action_center.failed_jobs.label',
                    'value' => (int) ($healthInfo['failed_jobs'] ?? 0),
                    'severity' => ($healthInfo['failed_jobs'] ?? 0) > 0 ? 'critical' : 'ok',
                    'hint_key' => 'ui.superadmin.action_center.failed_jobs.hint',
                ],
                [
                    'label_key' => 'ui.superadmin.action_center.quotations_expiring_7d.label',
                    'value' => (int) ($operationalAlerts['quotations_expiring_7d'] ?? 0),
                    'severity' => ($operationalAlerts['quotations_expiring_7d'] ?? 0) > 0 ? 'warning' : 'ok',
                    'hint_key' => 'ui.superadmin.action_center.quotations_expiring_7d.hint',
                ],
            ];
            if ($bookingsModuleEnabled) {
                $actionCenter[] = [
                    'label_key' => 'ui.superadmin.action_center.upcoming_bookings_7d.label',
                    'value' => (int) ($operationalAlerts['upcoming_bookings_7d'] ?? 0),
                    'severity' => 'info',
                    'hint_key' => 'ui.superadmin.action_center.upcoming_bookings_7d.hint',
                ];
            }

            $funnel = $this->buildBusinessFunnel($systemCounts);
            $moduleHealthMatrix = $this->buildModuleHealthMatrix($systemCounts);
            $moduleGroups = $this->buildModuleGroups(
                $moduleHealthMatrix,
                $systemCounts,
                $operationalAlerts,
                $moduleStats,
                $rolesAndPermissions,
                $healthInfo
            );

            return compact(
                'systemCounts',
                'moduleStats',
                'rolesAndPermissions',
                'inquiryByStatus',
                'quotationByStatus',
                'bookingByStatus',
                'bookingsModuleEnabled',
                'operationalAlerts',
                'upcomingFollowUps',
                'expiringQuotations',
                'upcomingBookings',
                'recentSystemHistory',
                'activityLogs',
                'failedJobs',
                'healthInfo',
                'actionCenter',
                'funnel',
                'moduleHealthMatrix',
                'moduleGroups'
            );
        });

        extract($payload, EXTR_SKIP);

        return view('superadmin.dashboard', compact(
            'systemCounts',
            'moduleStats',
            'rolesAndPermissions',
            'inquiryByStatus',
            'quotationByStatus',
            'bookingByStatus',
            'bookingsModuleEnabled',
            'operationalAlerts',
            'upcomingFollowUps',
            'expiringQuotations',
            'upcomingBookings',
            'recentSystemHistory',
            'activityLogs',
            'failedJobs',
            'healthInfo',
            'actionCenter',
            'funnel',
            'moduleHealthMatrix',
            'moduleGroups'
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

        $payload = Cache::remember("superadmin:dashboard:trend:{$period}", now()->addSeconds(45), function () use ($period): array {
            $bookingsModuleEnabled = ModuleService::isEnabledStatic('bookings');
            $endDate = now()->startOfDay();
            $startDate = $endDate->copy()->subDays($period - 1);

            $inquirySeries = $this->buildDailySeries(Inquiry::class, $startDate, $endDate);
            $quotationSeries = $this->buildDailySeries(Quotation::class, $startDate, $endDate);
            $bookingSeries = $bookingsModuleEnabled ? $this->buildDailySeries(Booking::class, $startDate, $endDate) : [];

            $labels = [];
            $inquiries = [];
            $quotations = [];
            $bookings = [];
            $cursor = $startDate->copy();

            while ($cursor->lte($endDate)) {
                $key = $cursor->format('Y-m-d');
                $labels[] = $cursor->format('Y-m-d');
                $inquiries[] = (int) ($inquirySeries[$key] ?? 0);
                $quotations[] = (int) ($quotationSeries[$key] ?? 0);
                $bookings[] = (int) ($bookingSeries[$key] ?? 0);
                $cursor->addDay();
            }

            $bars = [
                Inquiry::query()->count(),
                Quotation::query()->count(),
                $bookingsModuleEnabled ? Booking::query()->count() : 0,
                Itinerary::query()->count(),
                Vendor::query()->count(),
                Activity::query()->count(),
                InquiryFollowUp::query()->where('is_done', false)->count(),
                InquiryFollowUp::query()->where('is_done', false)->whereDate('due_date', '<', now()->toDateString())->count(),
                Schema::hasTable('jobs') ? DB::table('jobs')->count() : 0,
                Schema::hasTable('failed_jobs') ? DB::table('failed_jobs')->count() : 0,
            ];

            return [
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
                        $bookingsModuleEnabled ? Booking::query()->count() : 0,
                        Itinerary::query()->count(),
                    ],
                ],
            ];
        });

        return response()->json([
            'period' => $period,
            'refresh_seconds' => $refreshSeconds,
            'labels' => $payload['labels'],
            'trend' => $payload['trend'],
            'bars' => $payload['bars'],
            'mix' => $payload['mix'],
        ]);
    }

    private function buildRecentSystemHistory(bool $bookingsModuleEnabled): Collection
    {
        $users = User::query()->latest('updated_at')->limit(5)->get(['id', 'name', 'updated_at']);
        $customers = Customer::query()->latest('updated_at')->limit(5)->get(['id', 'name', 'updated_at']);
        $inquiries = Inquiry::query()->latest('updated_at')->limit(8)->get(['id', 'inquiry_number', 'status', 'updated_at']);
        $quotations = Quotation::query()->latest('updated_at')->limit(8)->get(['id', 'quotation_number', 'status', 'updated_at']);
        $bookings = $bookingsModuleEnabled
            ? Booking::query()->latest('updated_at')->limit(8)->get(['id', 'booking_number', 'status', 'updated_at'])
            : collect();
        $itineraries = Itinerary::query()->latest('updated_at')->limit(8)->get(['id', 'title', 'updated_at']);

        return collect()
            ->merge($users->map(fn ($item) => [
                'type_key' => 'ui.superadmin.history.types.user',
                'title' => $item->name,
                'meta_key' => 'ui.superadmin.history.meta.profile_updated',
                'meta_params' => [],
                'updated_at' => $item->updated_at,
            ]))
            ->merge($customers->map(fn ($item) => [
                'type_key' => 'ui.superadmin.history.types.customer',
                'title' => $item->name,
                'meta_key' => 'ui.superadmin.history.meta.customer_data_changed',
                'meta_params' => [],
                'updated_at' => $item->updated_at,
            ]))
            ->merge($inquiries->map(fn ($item) => [
                'type_key' => 'ui.superadmin.history.types.inquiry',
                'title' => $item->inquiry_number,
                'meta_key' => 'ui.superadmin.history.meta.status',
                'meta_params' => ['status' => (string) $item->status],
                'updated_at' => $item->updated_at,
            ]))
            ->merge($quotations->map(fn ($item) => [
                'type_key' => 'ui.superadmin.history.types.quotation',
                'title' => $item->quotation_number,
                'meta_key' => 'ui.superadmin.history.meta.status',
                'meta_params' => ['status' => (string) $item->status],
                'updated_at' => $item->updated_at,
            ]))
            ->merge($bookings->map(fn ($item) => [
                'type_key' => 'ui.superadmin.history.types.booking',
                'title' => $item->booking_number,
                'meta_key' => 'ui.superadmin.history.meta.status',
                'meta_params' => ['status' => (string) $item->status],
                'updated_at' => $item->updated_at,
            ]))
            ->merge($itineraries->map(fn ($item) => [
                'type_key' => 'ui.superadmin.history.types.itinerary',
                'title' => $item->title,
                'meta_key' => 'ui.superadmin.history.meta.schedule_changed',
                'meta_params' => [],
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

    /**
     * @param  array<string, int>  $systemCounts
     * @return array<int, array<string, mixed>>
     */
    private function buildBusinessFunnel(array $systemCounts): array
    {
        $inquiries = max((int) ($systemCounts['inquiries'] ?? 0), 0);
        $itineraries = max((int) ($systemCounts['itineraries'] ?? 0), 0); // Tambahkan ini
        $quotations = max((int) ($systemCounts['quotations'] ?? 0), 0);
        $bookings = max((int) ($systemCounts['bookings'] ?? 0), 0);
        $invoices = max((int) ($systemCounts['invoices'] ?? 0), 0);

        // Hitung ulang konversi
        $inqToItinerary = $inquiries > 0 ? round(($itineraries / $inquiries) * 100, 1) : 0.0;
        $itineraryToQuote = $itineraries > 0 ? round(($quotations / $itineraries) * 100, 1) : 0.0;
        $quoteToBooking = $quotations > 0 ? round(($bookings / $quotations) * 100, 1) : 0.0;
        $bookingToInvoice = $bookings > 0 ? round(($invoices / $bookings) * 100, 1) : 0.0;

        // Tambahkan Itinerary ke dalam array
        return [
            ['label_key' => 'ui.superadmin.funnel.labels.inquiries', 'value' => $inquiries, 'conversion' => null],
            ['label_key' => 'ui.superadmin.funnel.labels.itineraries', 'value' => $itineraries, 'conversion' => $inqToItinerary],
            ['label_key' => 'ui.superadmin.funnel.labels.quotations', 'value' => $quotations, 'conversion' => $itineraryToQuote],
            ['label_key' => 'ui.superadmin.funnel.labels.bookings', 'value' => $bookings, 'conversion' => $quoteToBooking],
            ['label_key' => 'ui.superadmin.funnel.labels.invoices', 'value' => $invoices, 'conversion' => $bookingToInvoice],
        ];
    }

    /**
     * @param  array<string, int>  $systemCounts
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function buildModuleHealthMatrix(array $systemCounts): Collection
    {
        $volumeByModule = [
            'customer_management' => (int) ($systemCounts['customers'] ?? 0),
            'inquiries' => (int) ($systemCounts['inquiries'] ?? 0),
            'itineraries' => (int) ($systemCounts['itineraries'] ?? 0),
            'quotations' => (int) ($systemCounts['quotations'] ?? 0),
            'bookings' => (int) ($systemCounts['bookings'] ?? 0),
            'invoices' => (int) ($systemCounts['invoices'] ?? 0),
            'vendor_management' => (int) ($systemCounts['vendors'] ?? 0),
            'destinations' => (int) ($systemCounts['destinations'] ?? 0),
            'activities' => (int) ($systemCounts['activities'] ?? 0),
            'airports' => (int) ($systemCounts['airports'] ?? 0),
            'tourist_attractions' => (int) ($systemCounts['tourist_attractions'] ?? 0),
            'food_beverages' => (int) ($systemCounts['food_beverages'] ?? 0),
            'hotels' => (int) ($systemCounts['hotels'] ?? 0),
            'transports' => (int) ($systemCounts['transports'] ?? 0),
            'currencies' => (int) ($systemCounts['currencies'] ?? 0),
        ];

        $roleCoverageByPermission = DB::table('permissions')
            ->join('role_has_permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->select('permissions.name', DB::raw('COUNT(DISTINCT role_has_permissions.role_id) as role_count'))
            ->groupBy('permissions.name')
            ->pluck('role_count', 'permissions.name');
        $permissionNames = Permission::query()->pluck('name')->flip();

        return Module::query()
            ->orderBy('name')
            ->get(['id', 'key', 'name', 'is_enabled', 'updated_at'])
            ->map(function (Module $module) use ($volumeByModule, $roleCoverageByPermission, $permissionNames): array {
                $permissionName = "module.{$module->key}.access";

                return [
                    'key' => $module->key,
                    'name' => $module->name,
                    'is_enabled' => (bool) $module->is_enabled,
                    'permission' => $permissionName,
                    'permission_exists' => $permissionNames->has($permissionName),
                    'role_coverage' => (int) ($roleCoverageByPermission[$permissionName] ?? 0),
                    'volume' => (int) ($volumeByModule[$module->key] ?? 0),
                    'updated_at' => $module->updated_at,
                ];
            });
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $moduleHealthMatrix
     * @param  array<string, int>  $systemCounts
     * @param  array<string, int>  $operationalAlerts
     * @param  array<string, int>  $moduleStats
     * @param  array<string, int>  $rolesAndPermissions
     * @param  array<string, mixed>  $healthInfo
     * @return array<int, array<string, mixed>>
     */
    private function buildModuleGroups(
        Collection $moduleHealthMatrix,
        array $systemCounts,
        array $operationalAlerts,
        array $moduleStats,
        array $rolesAndPermissions,
        array $healthInfo
    ): array {
        $definitions = [
            'ui.superadmin.group.crm_sales' => [
                ['key' => 'customer_management', 'icon' => 'address-book', 'route' => 'customers.index', 'name_key' => 'ui.superadmin.module_names.customer_management'],
                ['key' => 'inquiries', 'icon' => 'circle-question', 'route' => 'inquiries.index', 'name_key' => 'ui.superadmin.module_names.inquiries'],
                ['key' => 'itineraries', 'icon' => 'route', 'route' => 'itineraries.index', 'name_key' => 'ui.superadmin.module_names.itineraries'],
                ['key' => 'quotations', 'icon' => 'file-lines', 'route' => 'quotations.index', 'name_key' => 'ui.superadmin.module_names.quotations'],
                ['key' => 'bookings', 'icon' => 'calendar-check', 'route' => 'bookings.index', 'name_key' => 'ui.superadmin.module_names.bookings'],
                ['key' => 'invoices', 'icon' => 'file-invoice-dollar', 'route' => 'invoices.index', 'name_key' => 'ui.superadmin.module_names.invoices'],
            ],
            'ui.superadmin.group.product_reservation' => [
                ['key' => 'destinations', 'icon' => 'map-location-dot', 'route' => 'destinations.index', 'name_key' => 'ui.superadmin.module_names.destinations'],
                ['key' => 'vendor_management', 'icon' => 'handshake', 'route' => 'vendors.index', 'name_key' => 'ui.superadmin.module_names.vendor_management'],
                ['key' => 'activities', 'icon' => 'person-hiking', 'route' => 'activities.index', 'name_key' => 'ui.superadmin.module_names.activities'],
                ['key' => 'food_beverages', 'icon' => 'utensils', 'route' => 'food-beverages.index', 'name_key' => 'ui.superadmin.module_names.food_beverages'],
                ['key' => 'hotels', 'icon' => 'bed', 'route' => 'hotels.index', 'name_key' => 'ui.superadmin.module_names.hotels'],
                ['key' => 'airports', 'icon' => 'plane-departure', 'route' => 'airports.index', 'name_key' => 'ui.superadmin.module_names.airports'],
                ['key' => 'transports', 'icon' => 'bus', 'route' => 'transports.index', 'name_key' => 'ui.superadmin.module_names.transports'],
                ['key' => 'tourist_attractions', 'icon' => 'landmark', 'route' => 'tourist-attractions.index', 'name_key' => 'ui.superadmin.module_names.tourist_attractions'],
            ],
            'ui.superadmin.group.system_administration' => [
                ['key' => 'service_manager', 'icon' => 'cubes', 'route' => 'services.index', 'name_key' => 'ui.superadmin.module_names.service_manager'],
                ['key' => 'role_manager', 'icon' => 'user-shield', 'route' => 'roles.index', 'name_key' => 'ui.superadmin.module_names.role_manager'],
                ['key' => 'user_manager', 'icon' => 'user-gear', 'route' => 'users.index', 'name_key' => 'ui.superadmin.module_names.user_manager'],
                ['key' => 'currencies', 'icon' => 'coins', 'route' => 'currencies.index', 'name_key' => 'ui.superadmin.module_names.currencies'],
            ],
        ];

        $metrics = [
            'customer_management' => ['label_key' => 'ui.superadmin.metric.customers', 'value' => (int) ($systemCounts['customers'] ?? 0)],
            'inquiries' => ['label_key' => 'ui.superadmin.metric.pending_followups', 'value' => (int) ($operationalAlerts['pending_followups'] ?? 0)],
            'itineraries' => ['label_key' => 'ui.superadmin.metric.itineraries', 'value' => (int) ($systemCounts['itineraries'] ?? 0)],
            'quotations' => ['label_key' => 'ui.superadmin.metric.expiring_7d', 'value' => (int) ($operationalAlerts['quotations_expiring_7d'] ?? 0)],
            'bookings' => ['label_key' => 'ui.superadmin.metric.upcoming_7d', 'value' => (int) ($operationalAlerts['upcoming_bookings_7d'] ?? 0)],
            'invoices' => ['label_key' => 'ui.superadmin.metric.invoices', 'value' => (int) ($systemCounts['invoices'] ?? 0)],
            'vendor_management' => ['label_key' => 'ui.superadmin.metric.vendors', 'value' => (int) ($systemCounts['vendors'] ?? 0)],
            'destinations' => ['label_key' => 'ui.superadmin.metric.destinations', 'value' => (int) ($systemCounts['destinations'] ?? 0)],
            'activities' => ['label_key' => 'ui.superadmin.metric.activities', 'value' => (int) ($systemCounts['activities'] ?? 0)],
            'food_beverages' => ['label_key' => 'ui.superadmin.metric.food_beverage', 'value' => (int) ($systemCounts['food_beverages'] ?? 0)],
            'hotels' => ['label_key' => 'ui.superadmin.metric.hotels', 'value' => (int) ($systemCounts['hotels'] ?? 0)],
            'airports' => ['label_key' => 'ui.superadmin.metric.airports', 'value' => (int) ($systemCounts['airports'] ?? 0)],
            'transports' => ['label_key' => 'ui.superadmin.metric.transports', 'value' => (int) ($systemCounts['transports'] ?? 0)],
            'tourist_attractions' => ['label_key' => 'ui.superadmin.metric.attractions', 'value' => (int) ($systemCounts['tourist_attractions'] ?? 0)],
            'service_manager' => ['label_key' => 'ui.superadmin.metric.disabled_modules', 'value' => (int) ($moduleStats['disabled'] ?? 0)],
            'role_manager' => ['label_key' => 'ui.superadmin.metric.roles', 'value' => (int) ($rolesAndPermissions['roles'] ?? 0)],
            'user_manager' => ['label_key' => 'ui.superadmin.metric.users', 'value' => (int) ($systemCounts['users'] ?? 0)],
            'currencies' => ['label_key' => 'ui.superadmin.metric.currencies', 'value' => (int) ($systemCounts['currencies'] ?? 0)],
        ];

        $matrixByKey = $moduleHealthMatrix->keyBy('key');
        $used = [];
        $groups = [];

        foreach ($definitions as $groupName => $items) {
            $modules = [];

            foreach ($items as $item) {
                $row = $matrixByKey->get($item['key']);
                if (! $row) {
                    continue;
                }

                $used[] = $item['key'];
                $health = 'healthy';
                if (! ($row['permission_exists'] ?? false)) {
                    $health = 'critical';
                } elseif (! ($row['is_enabled'] ?? false)) {
                    $health = 'inactive';
                } elseif ((int) ($row['role_coverage'] ?? 0) === 0) {
                    $health = 'warning';
                }

                $modules[] = [
                    ...$row,
                    'icon' => $item['icon'],
                    'route' => $item['route'],
                    'name_key' => $item['name_key'] ?? null,
                    'health' => $health,
                    'metric' => $metrics[$item['key']] ?? ['label_key' => 'ui.superadmin.metric.data_volume', 'value' => (int) ($row['volume'] ?? 0)],
                ];
            }

            if ($modules !== []) {
                $groups[] = [
                    'name_key' => $groupName,
                    'modules' => $modules,
                ];
            }
        }

        $otherModules = $moduleHealthMatrix
            ->filter(fn (array $row): bool => ! in_array((string) $row['key'], $used, true))
            ->map(function (array $row): array {
                return [
                    ...$row,
                    'icon' => 'puzzle-piece',
                    'route' => 'services.index',
                    'name_key' => null,
                    'health' => ! ($row['permission_exists'] ?? false)
                        ? 'critical'
                        : (! ($row['is_enabled'] ?? false) ? 'inactive' : 'healthy'),
                    'metric' => ['label_key' => 'ui.superadmin.metric.data_volume', 'value' => (int) ($row['volume'] ?? 0)],
                ];
            })
            ->values()
            ->all();

        if ($otherModules !== []) {
            $groups[] = [
                'name_key' => 'ui.superadmin.group.other_modules',
                'modules' => $otherModules,
            ];
        }

        return $groups;
    }
}
