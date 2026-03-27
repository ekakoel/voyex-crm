<?php

namespace App\Http\View;

use App\Models\CompanySetting;
use App\Models\Currency;
use App\Services\ModuleService;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class SidebarComposer
{
    /**
     * Bind data to the view.
     *
     * @param  \Illuminate\View\View  $view
     * @return void
     */
    public function compose(View $view)
    {
        $menuItems = $this->getMenuItems();
        $user = Auth::user();
        $view->with('menuItems', $this->filterMenuItems($menuItems, $user));
        $view->with('companySettings', CompanySetting::query()->first());
        $view->with('currentCurrency', \App\Support\Currency::current());
        $currencyOptions = collect();
        if (Schema::hasTable('currencies')) {
            $currencyOptions = Currency::query()
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('code')
                ->get(['id', 'code', 'name', 'symbol', 'rate_to_idr', 'decimal_places']);
        }
        $view->with('currencyOptions', $currencyOptions);
    }

    /**
     * Defines the entire sidebar menu structure.
     *
     * @return array
     */
    private function getMenuItems(): array
    {
        return [
            // [
            //     'type' => 'label',
            //     'title' => 'Core',
            // ],
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'dashboard',
                'any_permissions' => [
                    'dashboard.administrator.view',
                    'dashboard.manager.view',
                    'dashboard.marketing.view',
                    'dashboard.reservation.view',
                    'dashboard.finance.view',
                    'dashboard.director.view',
                    'dashboard.editor.view',
                ],
            ],
            [
                'type' => 'separator',
            ],
            [
                'type' => 'label',
                'title' => 'CRM & Sales',
            ],
            [
                'title' => 'Customers',
                'route' => '#',
                'icon'  => 'chalkboard-user',
                'children' => [
                    [
                        'title' => 'Customers / Agents',
                        'route' => 'customers.index',
                        'icon'  => 'users',
                        'module' => 'customer_management',
                    ],
                ],
            ],
            [
                'title' => 'Reservations',
                'route' => '#',
                'icon'  => 'cart-flatbed-suitcase',
                'children' => [
                    [
                        'title' => 'Inquiries',
                        'route' => 'inquiries.index',
                        'icon'  => 'file-lines',
                        'module' => 'inquiries',
                    ],
                    [
                        'title' => 'Itineraries',
                        'route' => 'itineraries.index',
                        'icon'  => 'route',
                        'module' => 'itineraries',
                    ],
                    [
                        'title' => 'Quotations',
                        'route' => 'quotations.index',
                        'icon'  => 'file-circle-plus',
                        'module' => 'quotations',
                    ],
                    [
                        'title' => 'Bookings',
                        'route' => 'bookings.index',
                        'icon'  => 'tags',
                        'module' => 'bookings',
                        'roles' => ['Administrator', 'Super Admin', 'Reservation', 'Manager'],
                    ],
                    [
                        'title' => 'Invoices',
                        'route' => 'invoices.index',
                        'icon'  => 'file-invoice-dollar',
                        'module' => 'invoices',
                        'roles' => ['Administrator', 'Super Admin', 'Finance', 'Director'],
                    ],
                ],
            ],
            [
                'type' => 'separator',
            ],
            [
                'type' => 'label',
                'title' => 'Product & Reservation',
            ],
            [
                'title' => 'Destinations',
                'route' => 'destinations.index',
                'icon'  => 'map-location-dot',
                'module' => 'destinations',
                'roles' => ['Administrator', 'Super Admin', 'Reservation', 'Manager', 'Marketing', 'Editor'],
            ],
            [
                'title' => 'Vendors / Providers',
                'route' => 'vendors.index',
                'icon'  => 'handshake',
                'module' => 'vendor_management',
                'roles' => ['Administrator', 'Super Admin', 'Reservation', 'Manager', 'Marketing', 'Editor'],
            ],
            [
                'title' => 'Service Items',
                'route' => '#',
                'icon'  => 'briefcase',
                'children' => [
                    [
                        'title' => 'Activities',
                        'route' => 'activities.index',
                        'icon'  => 'person-hiking',
                        'module' => 'activities',
                        'roles' => ['Administrator', 'Super Admin', 'Reservation', 'Manager', 'Marketing', 'Editor'],
                    ],
                    [
                        'title' => 'F&B',
                        'route' => 'food-beverages.index',
                        'icon'  => 'utensils',
                        'module' => 'food_beverages',
                        'roles' => ['Administrator', 'Super Admin', 'Reservation', 'Manager', 'Marketing', 'Editor'],
                    ],
                    [
                        'title' => 'Hotels',
                        'route' => 'hotels.index',
                        'icon'  => 'bed',
                        'module' => 'hotels',
                        'roles' => ['Administrator', 'Super Admin', 'Reservation', 'Manager', 'Marketing', 'Editor'],
                    ],
                    [
                        'title' => 'Airports',
                        'route' => 'airports.index',
                        'icon'  => 'plane-departure',
                        'module' => 'airports',
                        'roles' => ['Administrator', 'Super Admin', 'Reservation', 'Manager', 'Marketing', 'Editor'],
                    ],
                    [
                        'title' => 'Transports',
                        'route' => 'transports.index',
                        'icon'  => 'bus',
                        'module' => 'transports',
                        'roles' => ['Administrator', 'Super Admin', 'Reservation', 'Manager', 'Marketing', 'Editor'],
                    ],
                    [
                        'title' => 'Tourist Attractions',
                        'route' => 'tourist-attractions.index',
                        'icon'  => 'landmark',
                        'module' => 'tourist_attractions',
                        'roles' => ['Administrator', 'Super Admin', 'Editor'],
                    ],
                ],
            ],
            [
                'type' => 'separator',
            ],
            [
                'type' => 'label',
                'title' => 'Administration',
            ],
            [
                'title' => 'Company Settings',
                'route' => 'company-settings.edit',
                'icon'  => 'building',
                'permission' => 'company_settings.manage',
            ],
            [
                'title' => 'Currencies',
                'route' => 'currencies.index',
                'icon'  => 'coins',
                'module' => 'currencies',
                'roles' => ['Administrator', 'Super Admin', 'Director'],
            ],
            [
                'title' => 'System Admin',
                'route' => '#',
                'icon'  => 'shield-halved',
                'roles' => ['Administrator', 'Super Admin'],
                'children' => [
                    [
                        'title' => 'Modules',
                        'route' => 'services.index',
                        'icon'  => 'cubes',
                        'module' => 'service_manager',
                        'roles' => ['Administrator', 'Super Admin'],
                    ],
                    [
                        'title' => 'Service Map',
                        'route' => 'services.map',
                        'icon'  => 'earth-asia',
                        'module' => 'service_manager',
                        'roles' => ['Administrator', 'Super Admin'],
                    ],
                    [
                        'title' => 'Role & Permissions',
                        'route' => 'roles.index',
                        'icon'  => 'user-shield',
                        'module' => 'role_manager',
                        'roles' => ['Administrator', 'Super Admin'],
                    ],
                    [
                        'title' => 'Access Matrix',
                        'route' => 'superadmin.access-matrix',
                        'icon'  => 'table-cells',
                        'roles' => ['Super Admin'],
                    ],
                    [
                        'title' => 'User Manager',
                        'route' => 'users.index',
                        'icon'  => 'user-gear',
                        'module' => 'user_manager',
                        'roles' => ['Administrator', 'Super Admin'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Filter menu recursively by module toggle and role access.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @param  mixed  $user
     * @return array<int, array<string, mixed>>
     */
    private function filterMenuItems(array $items, $user): array
    {
        $accessible = [];

        foreach ($items as $item) {
            if (($item['type'] ?? null) === 'separator') {
                $accessible[] = $item;
                continue;
            }
            if (($item['type'] ?? null) === 'label') {
                $accessible[] = $item;
                continue;
            }

            if ($user && ! $user->hasRole('Super Admin')) {
                $requiredPermission = $item['permission'] ?? (! empty($item['module']) ? "module.{$item['module']}.access" : null);
                if ($requiredPermission && ! $user->can($requiredPermission)) {
                    continue;
                }

                if (! empty($item['any_permissions']) && is_array($item['any_permissions'])) {
                    $hasAnyPermission = false;
                    foreach ($item['any_permissions'] as $permissionName) {
                        if ($user->can((string) $permissionName)) {
                            $hasAnyPermission = true;
                            break;
                        }
                    }

                    if (! $hasAnyPermission) {
                        continue;
                    }
                }
            }

            if (! empty($item['module']) && ! ModuleService::isEnabledStatic($item['module'])) {
                continue;
            }

            if (! empty($item['roles']) && (! $user || (! $user->hasRole('Super Admin') && ! $user->hasAnyRole($item['roles'])))) {
                continue;
            }


            if (! empty($item['children']) && is_array($item['children'])) {
                $item['children'] = $this->filterMenuItems($item['children'], $user);

                if (empty($item['children'])) {
                    // If after filtering, no children left, only skip if it's just a container
                    if (empty($item['route']) || $item['route'] === '#') {
                        continue;
                    }
                    // If menu has its own route, keep it even if children are empty
                }
                // If children still exist, or if menu has its own route, add it
            }

            $accessible[] = $item;
        }

        return $this->normalizeSeparators($accessible);
    }

    /**
     * Clean separators so they don't appear at the top/bottom or consecutively.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function normalizeSeparators(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $isSeparator = ($item['type'] ?? null) === 'separator';
            $isLabel = ($item['type'] ?? null) === 'label';

            if ($isSeparator) {
                if ($normalized === []) {
                    continue;
                }

                $last = end($normalized);
                if (($last['type'] ?? null) === 'separator') {
                    continue;
                }
            }

            if ($isLabel) {
                $last = end($normalized);
                if (($last['type'] ?? null) === 'label') {
                    continue;
                }
            }

            $normalized[] = $item;
        }

        while ($normalized !== []) {
            $last = end($normalized);
            if (($last['type'] ?? null) === 'separator' || ($last['type'] ?? null) === 'label') {
                array_pop($normalized);
                continue;
            }
            break;
        }

        while ($normalized !== []) {
            $first = $normalized[0];
            if (($first['type'] ?? null) === 'separator') {
                array_shift($normalized);
                continue;
            }
            break;
        }

        return $normalized;
    }

}
