<?php

namespace Database\Seeders;

use App\Models\FeatureAccess;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeatureAccessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
        // Admin ------------------------------------------------
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'dashboard',
                'module'  => 'dashboard',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Modules',
                'route' => 'admin.services.index',
                'icon'  => 'cubes',
                'module'  => 'service_manager',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Promotions',
                'route' => 'admin.promotions.index',
                'icon'  => 'percent',
                'module'  => 'promotions',
                'roles' => 'Admin',
            ],
            [
                'title' => 'User Manager',
                'route' => 'admin.users.index',
                'icon'  => 'users',
                'module'  => 'user_manager',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Role & Permissions',
                'route' => 'admin.roles.index',
                'icon'  => 'user_gear',
                'module'  => 'role_manager',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Vendors',
                'route' => 'admin.vendors.index',
                'icon'  => 'vendors',
                'module'  => 'vendor_management',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Inquiries',
                'route' => 'sales.inquiries.index',
                'icon'  => 'file-text',
                'module' => 'inquiries',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Quotations',
                'route' => 'admin.quotations.index',
                'icon'  => 'file-circle-plus',
                'module'  => 'quotation',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Quotations Templates',
                'route' => 'admin.quotation-templates.index',
                'icon'  => 'file-lines',
                'module'  => 'quotation_templates',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Customers',
                'route' => 'admin.customers.index',
                'icon'  => 'user-tag',
                'module'  => 'customer_management',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Bookings',
                'route' => 'admin.bookings.index',
                'icon'  => 'tags',
                'module'  => 'bookings',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Sales Target',
                'route' => 'admin.salestargets.index',
                'icon'  => 'chart_simple',
                'module'  => 'sales_target',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Services',
                'route' => '#',
                'icon'  => 'gears',
                'module'  => 'services',
                'roles' => 'Admin',
                'children' => [
                    [
                        'title' => 'Accommodations',
                        'route' => 'admin.services.items.accommodations.index',
                        'icon'  => 'bed',
                        'module' => 'services_accommodations',
                        'roles' => 'Admin',
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
        // Director ---------------------------------------------
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'dashboard',
                'module'  => 'dashboard',
                'roles' => 'Director',
            ],
        // Finance ----------------------------------------------
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'dashboard',
                'module'  => 'dashboard',
                'roles' => 'Finance',
            ],
        // Operations -------------------------------------------
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'dashboard',
                'module'  => 'dashboard',
                'roles' => 'Operations',
            ],
        // Sales Manager ----------------------------------------
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'dashboard',
                'module'  => 'dashboard',
                'roles' => 'Sales Manager',
            ],
        // Sales Agent ------------------------------------------
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'dashboard',
                'module'  => 'dashboard',
                'roles' => 'Sales Agent',
            ],
            
        ];

        foreach ($features as $feature) {
            FeatureAccess::updateOrCreate(
                ['route' => $feature['route']],
                $feature
            );
        }
    }
}
