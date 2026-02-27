<?php

namespace App\Http\View;

use App\Models\CompanySetting;
use App\Services\ModuleService;
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
                    'dashboard.admin.view',
                    'dashboard.sales.view',
                    'dashboard.finance.view',
                    'dashboard.operations.view',
                    'dashboard.director.view',
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
                'title' => 'Sales Pipeline',
                'route' => '#',
                'icon'  => 'chart-line',
                'children' => [
                    [
                        'title' => 'Customers / Agents',
                        'route' => 'customers.index',
                        'icon'  => 'users',
                        'module' => 'customer_management',
                    ],
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
                        'roles' => ['Admin', 'Super Admin'],
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
                        'roles' => ['Admin', 'Super Admin', 'Operations', 'Sales Manager'],
                    ],
                    [
                        'title' => 'Invoices',
                        'route' => 'invoices.index',
                        'icon'  => 'file-invoice-dollar',
                        'module' => 'invoices',
                        'roles' => ['Admin', 'Super Admin', 'Finance', 'Director'],
                    ],
                ],
            ],
            [
                'type' => 'separator',
            ],
            [
                'type' => 'label',
                'title' => 'Product & Operations',
            ],
            [
                'title' => 'Service Catalog',
                'route' => '#',
                'icon'  => 'briefcase',
                'children' => [
                    [
                        'title' => 'Vendors',
                        'route' => 'vendors.index',
                        'icon'  => 'handshake',
                        'module' => 'vendor_management',
                        'roles' => ['Admin', 'Super Admin', 'Operations', 'Sales Manager', 'Sales Agent'],
                    ],
                    [
                        'title' => 'Activities',
                        'route' => 'activities.index',
                        'icon'  => 'person-hiking',
                        'module' => 'activities',
                        'roles' => ['Admin', 'Super Admin', 'Operations', 'Sales Manager', 'Sales Agent'],
                    ],
                    [
                        'title' => 'Accommodations',
                        'route' => 'accommodations.index',
                        'icon'  => 'hotel',
                        'module' => 'accommodations',
                        'roles' => ['Admin', 'Super Admin', 'Operations', 'Sales Manager', 'Sales Agent'],
                    ],
                    [
                        'title' => 'Transports',
                        'route' => 'transports.index',
                        'icon'  => 'bus',
                        'module' => 'transports',
                        'roles' => ['Admin', 'Super Admin', 'Operations', 'Sales Manager', 'Sales Agent'],
                    ],
                    [
                        'title' => 'Tourist Attractions',
                        'route' => 'tourist-attractions.index',
                        'icon'  => 'landmark',
                        'module' => 'tourist_attractions',
                        'roles' => ['Admin', 'Super Admin'],
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
                'title' => 'System Admin',
                'route' => '#',
                'icon'  => 'shield-halved',
                'roles' => ['Admin', 'Super Admin'],
                'children' => [
                    [
                        'title' => 'Modules',
                        'route' => 'services.index',
                        'icon'  => 'cubes',
                        'module' => 'service_manager',
                        'roles' => ['Admin', 'Super Admin'],
                    ],
                    [
                        'title' => 'Role & Permissions',
                        'route' => 'roles.index',
                        'icon'  => 'user-shield',
                        'module' => 'role_manager',
                        'roles' => ['Admin', 'Super Admin'],
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
                        'roles' => ['Admin', 'Super Admin'],
                    ],
                    [
                        'title' => 'Quotation Templates',
                        'route' => 'quotation-templates.index',
                        'icon'  => 'file-lines',
                        'module' => 'quotation_templates',
                        'roles' => ['Admin', 'Super Admin'],
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
