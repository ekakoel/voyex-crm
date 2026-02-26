<?php

namespace Database\Seeders;

use App\Models\FeatureAccess;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeatureAccessSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
        // Admin ------------------------------------------------
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'dashboard',
                'module'  => null,
                'roles' => 'Admin',
            ],
            [
                'title' => 'Modules',
                'route' => 'services.index',
                'icon'  => 'cubes',
                'module'  => 'service_manager',
                'roles' => 'Admin',
            ],
            [
                'title' => 'User Manager',
                'route' => 'users.index',
                'icon'  => 'users',
                'module'  => 'user_manager',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Vendors',
                'route' => 'vendors.index',
                'icon'  => 'vendors',
                'module'  => 'vendor_management',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Itineraries',
                'route' => 'itineraries.index',
                'icon'  => 'route',
                'module'  => 'itineraries',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Tourist Attractions',
                'route' => 'tourist-attractions.index',
                'icon'  => 'landmark',
                'module'  => 'tourist_attractions',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Inquiries',
                'route' => 'inquiries.index',
                'icon'  => 'file-text',
                'module' => 'inquiries',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Quotations',
                'route' => 'quotations.index',
                'icon'  => 'file-circle-plus',
                'module'  => 'quotations',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Quotations Templates',
                'route' => 'quotation-templates.index',
                'icon'  => 'file-lines',
                'module'  => 'quotation_templates',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Customers',
                'route' => 'customers.index',
                'icon'  => 'user-tag',
                'module'  => 'customer_management',
                'roles' => 'Admin',
            ],
            [
                'title' => 'Bookings',
                'route' => 'bookings.index',
                'icon'  => 'tags',
                'module'  => 'bookings',
                'roles' => 'Admin',
            ],
        // Director ---------------------------------------------
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'dashboard',
                'module'  => null,
                'roles' => 'Director',
            ],
        // Finance ----------------------------------------------
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'dashboard',
                'module'  => null,
                'roles' => 'Finance',
            ],
        // Operations -------------------------------------------
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'dashboard',
                'module'  => null,
                'roles' => 'Operations',
            ],
        // Sales Manager ----------------------------------------
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'dashboard',
                'module'  => null,
                'roles' => 'Sales Manager',
            ],
        // Sales Agent ------------------------------------------
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'dashboard',
                'module'  => null,
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

