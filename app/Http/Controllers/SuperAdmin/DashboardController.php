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
                'label' => 'Follow-ups Overdue',
                'value' => (int) ($operationalAlerts['followups_overdue'] ?? 0),
                'severity' => ($operationalAlerts['followups_overdue'] ?? 0) > 0 ? 'critical' : 'ok',
                'hint' => 'Needs immediate team follow-up.',
            ],
            [
                'label' => 'Failed Jobs',
                'value' => (int) ($healthInfo['failed_jobs'] ?? 0),
                'severity' => ($healthInfo['failed_jobs'] ?? 0) > 0 ? 'critical' : 'ok',
                'hint' => 'Check queue worker and logs.',
            ],
            [
                'label' => 'Quotations Expiring (7D)',
                'value' => (int) ($operationalAlerts['quotations_expiring_7d'] ?? 0),
                'severity' => ($operationalAlerts['quotations_expiring_7d'] ?? 0) > 0 ? 'warning' : 'ok',
                'hint' => 'Potential conversion risk window.',
            ],
            [
                'label' => 'Upcoming Bookings (7D)',
                'value' => (int) ($operationalAlerts['upcoming_bookings_7d'] ?? 0),
                'severity' => 'info',
                'hint' => 'Operational readiness checkpoint.',
            ],
        ];

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
            ['label' => 'Inquiries', 'value' => $inquiries, 'conversion' => null],
            ['label' => 'Itineraries', 'value' => $itineraries, 'conversion' => $inqToItinerary],
            ['label' => 'Quotations', 'value' => $quotations, 'conversion' => $itineraryToQuote],
            ['label' => 'Bookings', 'value' => $bookings, 'conversion' => $quoteToBooking],
            ['label' => 'Invoices', 'value' => $invoices, 'conversion' => $bookingToInvoice],
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
            'CRM & Sales' => [
                ['key' => 'customer_management', 'icon' => 'address-book', 'route' => 'customers.index'],
                ['key' => 'inquiries', 'icon' => 'circle-question', 'route' => 'inquiries.index'],
                ['key' => 'itineraries', 'icon' => 'route', 'route' => 'itineraries.index'],
                ['key' => 'quotations', 'icon' => 'file-lines', 'route' => 'quotations.index'],
                ['key' => 'bookings', 'icon' => 'calendar-check', 'route' => 'bookings.index'],
                ['key' => 'invoices', 'icon' => 'file-invoice-dollar', 'route' => 'invoices.index'],
            ],
            'Product & Reservation' => [
                ['key' => 'destinations', 'icon' => 'map-location-dot', 'route' => 'destinations.index'],
                ['key' => 'vendor_management', 'icon' => 'handshake', 'route' => 'vendors.index'],
                ['key' => 'activities', 'icon' => 'person-hiking', 'route' => 'activities.index'],
                ['key' => 'food_beverages', 'icon' => 'utensils', 'route' => 'food-beverages.index'],
                ['key' => 'hotels', 'icon' => 'bed', 'route' => 'hotels.index'],
                ['key' => 'airports', 'icon' => 'plane-departure', 'route' => 'airports.index'],
                ['key' => 'transports', 'icon' => 'bus', 'route' => 'transports.index'],
                ['key' => 'tourist_attractions', 'icon' => 'landmark', 'route' => 'tourist-attractions.index'],
            ],
            'System Administration' => [
                ['key' => 'service_manager', 'icon' => 'cubes', 'route' => 'services.index'],
                ['key' => 'role_manager', 'icon' => 'user-shield', 'route' => 'roles.index'],
                ['key' => 'user_manager', 'icon' => 'user-gear', 'route' => 'users.index'],
                ['key' => 'currencies', 'icon' => 'coins', 'route' => 'currencies.index'],
            ],
        ];

        $metrics = [
            'customer_management' => ['label' => 'Customers', 'value' => (int) ($systemCounts['customers'] ?? 0)],
            'inquiries' => ['label' => 'Pending Follow-ups', 'value' => (int) ($operationalAlerts['pending_followups'] ?? 0)],
            'itineraries' => ['label' => 'Itineraries', 'value' => (int) ($systemCounts['itineraries'] ?? 0)],
            'quotations' => ['label' => 'Expiring (7D)', 'value' => (int) ($operationalAlerts['quotations_expiring_7d'] ?? 0)],
            'bookings' => ['label' => 'Upcoming (7D)', 'value' => (int) ($operationalAlerts['upcoming_bookings_7d'] ?? 0)],
            'invoices' => ['label' => 'Invoices', 'value' => (int) ($systemCounts['invoices'] ?? 0)],
            'vendor_management' => ['label' => 'Vendors', 'value' => (int) ($systemCounts['vendors'] ?? 0)],
            'destinations' => ['label' => 'Destinations', 'value' => (int) ($systemCounts['destinations'] ?? 0)],
            'activities' => ['label' => 'Activities', 'value' => (int) ($systemCounts['activities'] ?? 0)],
            'food_beverages' => ['label' => 'Food & Beverage', 'value' => (int) ($systemCounts['food_beverages'] ?? 0)],
            'hotels' => ['label' => 'Hotels', 'value' => (int) ($systemCounts['hotels'] ?? 0)],
            'airports' => ['label' => 'Airports', 'value' => (int) ($systemCounts['airports'] ?? 0)],
            'transports' => ['label' => 'Transports', 'value' => (int) ($systemCounts['transports'] ?? 0)],
            'tourist_attractions' => ['label' => 'Attractions', 'value' => (int) ($systemCounts['tourist_attractions'] ?? 0)],
            'service_manager' => ['label' => 'Disabled Modules', 'value' => (int) ($moduleStats['disabled'] ?? 0)],
            'role_manager' => ['label' => 'Roles', 'value' => (int) ($rolesAndPermissions['roles'] ?? 0)],
            'user_manager' => ['label' => 'Users', 'value' => (int) ($systemCounts['users'] ?? 0)],
            'currencies' => ['label' => 'Currencies', 'value' => (int) ($systemCounts['currencies'] ?? 0)],
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
                    'health' => $health,
                    'metric' => $metrics[$item['key']] ?? ['label' => 'Data Volume', 'value' => (int) ($row['volume'] ?? 0)],
                ];
            }

            if ($modules !== []) {
                $groups[] = [
                    'name' => $groupName,
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
                    'health' => ! ($row['permission_exists'] ?? false)
                        ? 'critical'
                        : (! ($row['is_enabled'] ?? false) ? 'inactive' : 'healthy'),
                    'metric' => ['label' => 'Data Volume', 'value' => (int) ($row['volume'] ?? 0)],
                ];
            })
            ->values()
            ->all();

        if ($otherModules !== []) {
            $groups[] = [
                'name' => 'Other Modules',
                'modules' => $otherModules,
            ];
        }

        return $groups;
    }
}


