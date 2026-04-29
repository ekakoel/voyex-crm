<?php

namespace App\Http\Controllers\Administrator;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Airport;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Destination;
use App\Models\FoodBeverage;
use App\Models\Inquiry;
use App\Models\Module;
use App\Models\Quotation;
use App\Models\TouristAttraction;
use App\Models\Transport;
use App\Models\User;
use App\Models\Vendor;
use App\Services\ModuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function index()
    {
        $permissions = $this->permissionMatrix();

        return view('administrator.dashboard', array_merge($permissions, [
            'widgetEndpoints' => [
                'system-management' => route('dashboard.administrator.widget', ['section' => 'system-management']),
                'operational-overview' => route('dashboard.administrator.widget', ['section' => 'operational-overview']),
                'master-data-catalog' => route('dashboard.administrator.widget', ['section' => 'master-data-catalog']),
                'pending-quotations' => route('dashboard.administrator.widget', ['section' => 'pending-quotations']),
                'recent-users' => route('dashboard.administrator.widget', ['section' => 'recent-users']),
            ],
        ]));
    }

    public function widget(Request $request, string $section): JsonResponse
    {
        $permissions = $this->permissionMatrix();
        $userId = (int) ($request->user()?->id ?? 0);

        $payload = match ($section) {
            'system-management' => [
                'view' => 'administrator.dashboard.partials.system-management',
                'data' => [
                    'systemKpis' => $this->systemKpis($permissions, $userId),
                ],
            ],
            'operational-overview' => [
                'view' => 'administrator.dashboard.partials.operational-overview',
                'data' => [
                    'operationalKpis' => $this->operationalKpis($permissions, $userId),
                ],
            ],
            'master-data-catalog' => [
                'view' => 'administrator.dashboard.partials.master-data-catalog',
                'data' => [
                    'masterDataKpis' => $this->masterDataKpis($permissions, $userId),
                ],
            ],
            'pending-quotations' => [
                'view' => 'administrator.dashboard.partials.pending-quotations',
                'data' => [
                    'pendingQuotations' => $this->pendingQuotations($permissions, $userId),
                    'canQuotations' => (bool) ($permissions['canQuotations'] ?? false),
                ],
            ],
            'recent-users' => [
                'view' => 'administrator.dashboard.partials.recent-users',
                'data' => [
                    'recentUsers' => $this->recentUsers($permissions, $userId),
                    'canUsers' => (bool) ($permissions['canUsers'] ?? false),
                ],
            ],
            default => null,
        };

        if ($payload === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Unknown dashboard widget section.',
            ], 404);
        }

        $html = view($payload['view'], $payload['data'])->render();

        return response()->json([
            'ok' => true,
            'section' => $section,
            'html' => (string) new HtmlString($html),
        ]);
    }

    private function permissionMatrix(): array
    {
        $user = auth()->user();
        $bookingsModuleEnabled = ModuleService::isEnabledStatic('bookings');

        return [
            'canUsers' => (bool) $user?->can('module.user_manager.access'),
            'canRoles' => (bool) $user?->can('module.role_manager.access'),
            'canServices' => (bool) $user?->can('module.service_manager.access'),
            'canCustomers' => (bool) $user?->can('module.customer_management.access'),
            'canInquiries' => (bool) $user?->can('module.inquiries.access'),
            'canQuotations' => (bool) $user?->can('module.quotations.access'),
            'canBookings' => $bookingsModuleEnabled && (bool) $user?->can('module.bookings.access'),
            'canVendors' => (bool) $user?->can('module.vendor_management.access'),
            'canDestinations' => (bool) $user?->can('module.destinations.access'),
            'canActivities' => (bool) $user?->can('module.activities.access'),
            'canTransports' => (bool) $user?->can('module.transports.access'),
            'canAttractions' => (bool) $user?->can('module.tourist_attractions.access'),
            'canFoodBeverages' => (bool) $user?->can('module.food_beverages.access'),
            'canAirports' => (bool) $user?->can('module.airports.access'),
        ];
    }

    private function systemKpis(array $permissions, int $userId): array
    {
        return Cache::remember("administrator-dashboard:{$userId}:system-kpis", now()->addSeconds(60), function () use ($permissions): array {
            $canUsers = (bool) ($permissions['canUsers'] ?? false);
            $canRoles = (bool) ($permissions['canRoles'] ?? false);
            $canServices = (bool) ($permissions['canServices'] ?? false);
            $userBaseQuery = User::query()->withoutSuperAdmin();
            $roleBaseQuery = Role::query()->where('name', '!=', 'Super Admin');
            $systemManagementStats = [
                'users' => $canUsers ? (clone $userBaseQuery)->count() : 0,
                'roles' => $canRoles ? (clone $roleBaseQuery)->count() : 0,
                'permissions' => ($canUsers || $canRoles) ? Permission::query()->count() : 0,
                'modules_enabled' => $canServices ? Module::query()->where('is_enabled', true)->count() : 0,
                'modules_disabled' => $canServices ? Module::query()->where('is_enabled', false)->count() : 0,
            ];

            $items = [];
            if ($canUsers) {
                $items[] = ['label' => ui_phrase('users'), 'value' => $systemManagementStats['users'] ?? 0, 'icon' => 'users', 'color' => 'sky', 'route' => 'users.index'];
            }
            if ($canRoles) {
                $items[] = ['label' => ui_phrase('roles'), 'value' => $systemManagementStats['roles'] ?? 0, 'icon' => 'user-shield', 'color' => 'violet', 'route' => 'roles.index'];
            }
            if ($canServices) {
                $items[] = ['label' => ui_phrase('Modules On'), 'value' => $systemManagementStats['modules_enabled'] ?? 0, 'icon' => 'toggle-on', 'color' => 'emerald', 'route' => 'services.index'];
                $items[] = ['label' => ui_phrase('Modules Off'), 'value' => $systemManagementStats['modules_disabled'] ?? 0, 'icon' => 'toggle-off', 'color' => 'rose', 'route' => 'services.index'];
            }

            return $items;
        });
    }

    private function operationalKpis(array $permissions, int $userId): array
    {
        return Cache::remember("administrator-dashboard:{$userId}:operational-kpis", now()->addSeconds(60), function () use ($permissions): array {
            $canCustomers = (bool) ($permissions['canCustomers'] ?? false);
            $canInquiries = (bool) ($permissions['canInquiries'] ?? false);
            $canQuotations = (bool) ($permissions['canQuotations'] ?? false);
            $canBookings = (bool) ($permissions['canBookings'] ?? false);

            $operationalStats = [
                'customers' => $canCustomers ? Customer::query()->count() : 0,
                'inquiries' => $canInquiries ? Inquiry::query()->count() : 0,
                'quotations' => $canQuotations ? Quotation::query()->count() : 0,
                'bookings' => $canBookings ? Booking::query()->count() : 0,
            ];

            $items = [];
            if ($canCustomers) {
                $items[] = ['label' => ui_phrase('customers'), 'value' => $operationalStats['customers'] ?? 0, 'icon' => 'address-book', 'color' => 'indigo'];
            }
            if ($canInquiries) {
                $items[] = ['label' => ui_phrase('inquiries'), 'value' => $operationalStats['inquiries'] ?? 0, 'icon' => 'circle-question', 'color' => 'amber'];
            }
            if ($canQuotations) {
                $items[] = ['label' => ui_phrase('quotations'), 'value' => $operationalStats['quotations'] ?? 0, 'icon' => 'file-lines', 'color' => 'teal'];
            }
            if ($canBookings) {
                $items[] = ['label' => ui_phrase('bookings'), 'value' => $operationalStats['bookings'] ?? 0, 'icon' => 'calendar-check', 'color' => 'cyan'];
            }

            return $items;
        });
    }

    private function masterDataKpis(array $permissions, int $userId): array
    {
        return Cache::remember("administrator-dashboard:{$userId}:master-kpis", now()->addSeconds(60), function () use ($permissions): array {
            $canVendors = (bool) ($permissions['canVendors'] ?? false);
            $canDestinations = (bool) ($permissions['canDestinations'] ?? false);
            $canActivities = (bool) ($permissions['canActivities'] ?? false);
            $canTransports = (bool) ($permissions['canTransports'] ?? false);
            $canAttractions = (bool) ($permissions['canAttractions'] ?? false);
            $canFoodBeverages = (bool) ($permissions['canFoodBeverages'] ?? false);
            $canAirports = (bool) ($permissions['canAirports'] ?? false);

            $masterDataStats = [
                'vendors' => $canVendors ? Vendor::query()->count() : 0,
                'destinations' => $canDestinations ? Destination::query()->count() : 0,
                'activities' => $canActivities ? Activity::query()->count() : 0,
                'transports' => $canTransports ? Transport::query()->count() : 0,
                'tourist_attractions' => $canAttractions ? TouristAttraction::query()->count() : 0,
                'food_beverages' => $canFoodBeverages ? FoodBeverage::query()->count() : 0,
                'airports' => $canAirports ? Airport::query()->count() : 0,
            ];

            $items = [];
            if ($canVendors) {
                $items[] = ['label' => ui_phrase('vendors'), 'value' => $masterDataStats['vendors'] ?? 0, 'icon' => 'handshake', 'route' => 'vendors.index'];
            }
            if ($canDestinations) {
                $items[] = ['label' => ui_phrase('destinations'), 'value' => $masterDataStats['destinations'] ?? 0, 'icon' => 'map-location-dot', 'route' => 'destinations.index'];
            }
            if ($canActivities) {
                $items[] = ['label' => ui_phrase('activities'), 'value' => $masterDataStats['activities'] ?? 0, 'icon' => 'person-hiking', 'route' => 'activities.index'];
            }
            if ($canTransports) {
                $items[] = ['label' => ui_phrase('transports'), 'value' => $masterDataStats['transports'] ?? 0, 'icon' => 'bus', 'route' => 'transports.index'];
            }
            if ($canAttractions) {
                $items[] = ['label' => ui_phrase('attractions'), 'value' => $masterDataStats['tourist_attractions'] ?? 0, 'icon' => 'landmark', 'route' => 'tourist-attractions.index'];
            }
            if ($canFoodBeverages) {
                $items[] = ['label' => ui_phrase('food beverages'), 'value' => $masterDataStats['food_beverages'] ?? 0, 'icon' => 'utensils', 'route' => 'food-beverages.index'];
            }
            if ($canAirports) {
                $items[] = ['label' => ui_phrase('airports'), 'value' => $masterDataStats['airports'] ?? 0, 'icon' => 'plane-departure', 'route' => 'airports.index'];
            }

            return $items;
        });
    }

    private function pendingQuotations(array $permissions, int $userId)
    {
        $canQuotations = (bool) ($permissions['canQuotations'] ?? false);

        return Cache::remember("administrator-dashboard:{$userId}:pending-quotations", now()->addSeconds(60), function () use ($canQuotations) {
            return $canQuotations
                ? Quotation::query()
                    ->where('status', 'pending')
                    ->latest()
                    ->limit(5)
                    ->with('inquiry:id,customer_id', 'inquiry.customer:id,name')
                    ->get()
                : collect();
        });
    }

    private function recentUsers(array $permissions, int $userId)
    {
        $canUsers = (bool) ($permissions['canUsers'] ?? false);

        return Cache::remember("administrator-dashboard:{$userId}:recent-users", now()->addSeconds(60), function () use ($canUsers) {
            return $canUsers
                ? User::query()
                    ->withoutSuperAdmin()
                    ->with('roles:id,name')
                    ->latest('updated_at')
                    ->limit(5)
                    ->get()
                : collect();
        });
    }
}
