<?php

namespace Database\Seeders;

use App\Models\FeatureAccess;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class FeatureAccessSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('feature_accesses')) {
            return;
        }

        $features = [
        // Administrator ---------------------------------------
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'dashboard',
                'module'  => null,
                'roles' => 'Administrator',
            ],
            [
                'title' => 'Modules',
                'route' => 'services.index',
                'icon'  => 'cubes',
                'module'  => 'service_manager',
                'roles' => 'Administrator',
            ],
            [
                'title' => 'User Manager',
                'route' => 'users.index',
                'icon'  => 'users',
                'module'  => 'user_manager',
                'roles' => 'Administrator',
            ],
            [
                'title' => 'Vendors',
                'route' => 'vendors.index',
                'icon'  => 'vendors',
                'module'  => 'vendor_management',
                'roles' => 'Administrator',
            ],
            [
                'title' => 'Itineraries',
                'route' => 'itineraries.index',
                'icon'  => 'route',
                'module'  => 'itineraries',
                'roles' => 'Administrator',
            ],
            [
                'title' => 'Tourist Attractions',
                'route' => 'tourist-attractions.index',
                'icon'  => 'landmark',
                'module'  => 'tourist_attractions',
                'roles' => 'Administrator',
            ],
            [
                'title' => 'Inquiries',
                'route' => 'inquiries.index',
                'icon'  => 'file-text',
                'module' => 'inquiries',
                'roles' => 'Administrator',
            ],
            [
                'title' => 'Quotations',
                'route' => 'quotations.index',
                'icon'  => 'file-circle-plus',
                'module'  => 'quotations',
                'roles' => 'Administrator',
            ],
            [
                'title' => 'Customers',
                'route' => 'customers.index',
                'icon'  => 'user-tag',
                'module'  => 'customer_management',
                'roles' => 'Administrator',
            ],
            [
                'title' => 'Bookings',
                'route' => 'bookings.index',
                'icon'  => 'tags',
                'module'  => 'bookings',
                'roles' => 'Administrator',
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
        // Reservation ------------------------------------------
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'dashboard',
                'module'  => null,
                'roles' => 'Reservation',
            ],
        // Manager ----------------------------------------------
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'dashboard',
                'module'  => null,
                'roles' => 'Manager',
            ],
        // Marketing --------------------------------------------
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'dashboard',
                'module'  => null,
                'roles' => 'Marketing',
            ],
        // Editor -----------------------------------------------
            [
                'title' => 'Dashboard',
                'route' => 'dashboard',
                'icon'  => 'dashboard',
                'module'  => null,
                'roles' => 'Editor',
            ],
            
        ];

        foreach ($features as $feature) {
            $feature['module'] = $feature['module'] ?? '';

            FeatureAccess::updateOrCreate(
                [
                    'route' => $feature['route'],
                    'roles' => $feature['roles'],
                ],
                $feature
            );
        }
    }
}
