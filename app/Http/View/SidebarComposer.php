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
                'roles' => ['Admin', 'Director', 'Sales Manager', 'Sales Agent', 'Finance', 'Operations'],
            ],
            
           
            [
                'title' => 'Bookings',
                'route' => 'operations.bookings.index',
                'icon'  => 'calendar',
                'module' => 'bookings',
                'roles' => ['Operations', 'Finance', 'Sales Manager'],
            ],
            // ======================================================================================
            // --- Menu Director ---
            // ======================================================================================
            // ======================================================================================
            // --- Menu Sales Manager ---
            // ======================================================================================
            // --- Menu Sales ---
            [
                'title' => 'Customers',
                'route' => 'sales.customers.index',
                'icon'  => 'users',
                'module' => 'customer_management',
                'roles' => ['Sales Manager', 'Sales Agent'],
            ],
            [
                'title' => 'Inquiries',
                'route' => 'sales.inquiries.index',
                'icon'  => 'file-text',
                'module' => 'inquiries',
                'roles' => ['Sales Manager', 'Sales Agent'],
            ],
            [
                'title' => 'Quotations',
                'route' => 'sales.quotations.index',
                'icon'  => 'file-plus',
                'module' => 'quotations',
                'roles' => ['Sales Manager', 'Sales Agent'],
            ],
            // ======================================================================================
            // --- Menu Sales Agent ---
            // ======================================================================================
            // ======================================================================================
            // --- Menu Finance ---
            // ======================================================================================
            // ======================================================================================
            // --- Menu Operations ---
            // ======================================================================================
            // ======================================================================================
            // --- Menu Admin ---
            // ======================================================================================
            [
                'title' => 'Quotations',
                'route' => 'admin.quotations.index',
                'icon'  => 'file-circle-plus',
                'module' => 'quotations',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Quotation Templates',
                'route' => 'admin.quotation-templates.index',
                'icon'  => 'file-lines',
                'module' => 'quotation_templates',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Modules',
                'route' => 'admin.services.index',
                'icon'  => 'cubes',
                'module' => 'service_manager',
                'roles' => ['Admin'],
            ],
            
            [
                'title' => 'Promotions',
                'route' => 'admin.promotions.index',
                'icon'  => 'percent',
                'module' => 'promotions',
                'roles' => ['Admin', 'Sales Manager', 'Director'],
            ],
            [
                'title' => 'User Manager',
                'route' => 'admin.users.index',
                'icon'  => 'users',
                'module' => 'user_manager',
                'roles' => ['Admin'],
            ],
            [
                'title' => 'Role & Permissions',
                'route' => 'admin.roles.index',
                'icon'  => 'user_gear',
                'module' => 'role_manager',
                'roles' => ['Admin'],
            ],
            [
                'title' => 'Sales Target',
                'route' => 'admin.salestargets.index',
                'icon'  => 'target',
                'module' => 'sales_target',
                'roles' => ['Admin', 'Sales Manager'],
            ],
            [
                'title' => 'Vendors',
                'route' => 'admin.vendors.index',
                'icon'  => 'vendors',
                'module' => 'vendor_management',
                'roles' => ['Admin', 'Operations', 'Sales Manager', 'Sales Agent'],
            ],
            [
                'title' => 'Services',
                'icon'  => 'gears',
                'module' => 'services',
                'route' => '#', // Placeholder for parent menu
                'roles' => ['Admin', 'Operations', 'Sales Manager', 'Sales Agent'],
                'children' => [
                    [
                        'title' => 'Accommodations',
                        'route' => 'admin.services.items.accommodations.index',
                        'icon'  => 'bed',
                        'module' => 'services_accommodations',
                        'roles' => ['Admin', 'Operations', 'Sales Manager', 'Sales Agent'],
                    ],
                    [
                        'title' => 'Transports',
                        'route' => 'admin.services.items.transports.index',
                        'icon'  => 'bus',
                        'module' => 'services_transports',
                        'roles' => ['Admin', 'Operations', 'Sales Manager', 'Sales Agent'],
                    ],
                    [
                        'title' => 'Guides',
                        'route' => 'admin.services.items.guides.index',
                        'icon'  => 'user-tie',
                        'module' => 'services_guides',
                        'roles' => ['Admin', 'Operations', 'Sales Manager', 'Sales Agent'],
                    ],
                    [
                        'title' => 'Attractions',
                        'route' => 'admin.services.items.attractions.index',
                        'icon'  => 'landmark',
                        'module' => 'services_attractions',
                        'roles' => ['Admin', 'Operations', 'Sales Manager', 'Sales Agent'],
                    ],
                    [
                        'title' => 'Travel Activities',
                        'route' => 'admin.services.items.travel-activities.index',
                        'icon'  => 'person-hiking',
                        'module' => 'services_travel_activities',
                        'roles' => ['Admin', 'Operations', 'Sales Manager', 'Sales Agent'],
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
            if (! empty($item['module']) && ! ModuleService::isEnabledStatic($item['module'])) {
                continue;
            }

            if (! empty($item['roles']) && (! $user || ! $user->hasAnyRole($item['roles']))) {
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

        return $accessible;
    }

}
