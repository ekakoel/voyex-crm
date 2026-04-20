<?php

namespace App\Http\View;

use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\Quotation;
use App\Services\ModuleService;
use Illuminate\Database\Eloquent\Builder;
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
        
        $companySettings = CompanySetting::query()->first();
        if ($companySettings && $companySettings->company_name) {
            $companySettings->company_name = $this->cleanHtmlEntities($companySettings->company_name);
        }
        $view->with('companySettings', $companySettings);
        
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
        $view->with('quotationApprovalNotification', $this->buildQuotationApprovalNotification($user));
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
                'title' => 'Airports',
                'route' => 'airports.index',
                'icon'  => 'plane-departure',
                'module' => 'airports',
                'roles' => ['Administrator', 'Super Admin', 'Reservation', 'Manager', 'Marketing', 'Editor'],
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
                        'title' => 'Island Transfers',
                        'route' => 'island-transfers.index',
                        'icon'  => 'ship',
                        'module' => 'island_transfers',
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
                        'permission' => 'services.map.view',
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
                        'permission' => 'superadmin.access_matrix.view',
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

            if ($user && ! $user->isSuperAdmin()) {
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

            if (
                $this->shouldEnforceRoleConstraint($item)
                && ! empty($item['roles'])
                && (! $user || (! $user->isSuperAdmin() && ! $user->hasAnyRole($item['roles'])))
            ) {
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
     * Sidebar is permission-first by default.
     * Static role constraints are only enforced for menu items that are explicitly role-locked
     * or for standalone role-only entries with no permission/module signal.
     *
     * @param array<string, mixed> $item
     */
    private function shouldEnforceRoleConstraint(array $item): bool
    {
        if (($item['enforce_roles'] ?? false) === true) {
            return true;
        }

        $hasPermissionSignal = ! empty($item['permission'])
            || ! empty($item['module'])
            || ! empty($item['any_permissions']);
        $hasChildren = ! empty($item['children']) && is_array($item['children']);

        return ! $hasPermissionSignal && ! $hasChildren;
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

    /**
     * @param mixed $user
     * @return array{visible: bool, role: string|null, count: int}
     */
    private function buildQuotationApprovalNotification($user): array
    {
        $empty = [
            'visible' => false,
            'role' => null,
            'count' => 0,
        ];

        if (! $user || ! Schema::hasTable('quotations') || ! Schema::hasTable('quotation_approvals')) {
            return $empty;
        }

        if (! ModuleService::isEnabledStatic('quotations')) {
            return $empty;
        }

        if (! $user->can('module.quotations.access')) {
            return $empty;
        }

        if (! $user->can('quotations.approve')) {
            return $empty;
        }

        $role = null;
        if ($user->can('dashboard.director.view')) {
            $role = 'director';
        } elseif ($user->can('dashboard.manager.view')) {
            $role = 'manager';
        } elseif ($user->can('dashboard.reservation.view')) {
            $role = 'reservation';
        }

        if (! $role) {
            return $empty;
        }

        $baseQuery = Quotation::query()->where('status', 'pending');
        if (Schema::hasColumn('quotations', 'created_by')) {
            $baseQuery->where(function (Builder $query) use ($user): void {
                $query->whereNull('created_by')
                    ->orWhere('created_by', '!=', (int) $user->id);
            });
        }

        $count = (clone $baseQuery)
            ->whereDoesntHave('approvals', function (Builder $query) use ($user): void {
                $query->where('user_id', (int) $user->id);
            })
            ->when(
                Schema::hasColumn('quotations', 'created_by'),
                fn (Builder $query) => $query->whereRaw(
                    '(SELECT COUNT(*) FROM quotation_approvals qa'
                    .' WHERE qa.quotation_id = quotations.id'
                    .' AND (quotations.created_by IS NULL OR qa.user_id <> quotations.created_by)) < 2'
                ),
                fn (Builder $query) => $query->whereRaw(
                    '(SELECT COUNT(*) FROM quotation_approvals qa'
                    .' WHERE qa.quotation_id = quotations.id) < 2'
                )
            )
            ->count();

        return [
            'visible' => true,
            'role' => $role,
            'count' => max(0, (int) $count),
        ];
    }

    private function cleanHtmlEntities(string $value): string
    {
        $value = trim($value);
        
        // First, decode HTML entities once
        $cleaned = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        // Remove excessive ampersand encoding (common issue with &amp;amp;...)
        // Keep doing this while we find excessive encoding
        while (strpos($cleaned, '&amp;amp;') !== false || strpos($cleaned, '&#38;') !== false) {
            $prev = $cleaned;
            $cleaned = html_entity_decode($cleaned, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // If nothing changed, break to avoid infinite loop
            if ($cleaned === $prev) {
                break;
            }
        }
        
        return trim($cleaned);
    }

}
