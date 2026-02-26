<?php

namespace App\Http\View;

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
    }

    /**
     * Defines the entire sidebar menu structure.
     *
     * @return array
     */
    private function getMenuItems(): array
    {
        return [
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'dashboard',
                'roles' => ['Admin','Super Admin', 'Director', 'Sales Manager', 'Sales Agent', 'Finance', 'Operations'],
            ],
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
                'title' => 'User Manager',
                'route' => 'users.index',
                'icon'  => 'user-gear',
                'module' => 'user_manager',
                'roles' => ['Admin', 'Super Admin'],
            ],
            [
                'type' => 'separator',
            ],
            [
                'title' => 'Customers',
                'route' => 'customers.index',
                'icon'  => 'users',
                'module' => 'customer_management',
            ],
            [
                'title' => 'Inquiries',
                'route' => 'inquiries.index',
                'icon'  => 'file-text',
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
                'title' => 'Quotation Templates',
                'route' => 'quotation-templates.index',
                'icon'  => 'file-lines',
                'module' => 'quotation_templates',
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
            [
                'type' => 'separator',
            ],
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
                'title' => 'Tourist Attractions',
                'route' => 'tourist-attractions.index',
                'icon'  => 'landmark',
                'module' => 'tourist_attractions',
                'roles' => ['Admin', 'Super Admin'],
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

            if ($isSeparator) {
                if ($normalized === []) {
                    continue;
                }

                $last = end($normalized);
                if (($last['type'] ?? null) === 'separator') {
                    continue;
                }
            }

            $normalized[] = $item;
        }

        $last = end($normalized);
        if (($last['type'] ?? null) === 'separator') {
            array_pop($normalized);
        }

        return $normalized;
    }

}


