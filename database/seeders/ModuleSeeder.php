<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            [
                'key' => 'service_manager',
                'name' => 'Module Management',
                'description' => 'Manage module enable/disable status.',
                'is_enabled' => true,
            ],
            [
                'key' => 'customer_management',
                'name' => 'Customer Management',
                'description' => 'Manage customer data from all channels.',
                'is_enabled' => true,
            ],
            [
                'key' => 'inquiries',
                'name' => 'Inquiries',
                'description' => 'Track all customer inquiries and requests.',
                'is_enabled' => true,
            ],
            [
                'key' => 'quotations',
                'name' => 'Quotations',
                'description' => 'Manage quotation creation and tracking.',
                'is_enabled' => true,
            ],
            [
                'key' => 'bookings',
                'name' => 'Bookings',
                'description' => 'View and manage ongoing booking processes.',
                'is_enabled' => true,
            ],
            [
                'key' => 'invoices',
                'name' => 'Invoices',
                'description' => 'Manage invoice data generated from finalized and director-approved bookings.',
                'is_enabled' => true,
            ],
            // ...existing code...
            [
                'key' => 'user_manager',
                'name' => 'User Manager',
                'description' => 'Manage employee data and access roles.',
                'is_enabled' => true,
            ],
            [
                'key' => 'role_manager',
                'name' => 'Role Manager',
                'description' => 'Manage roles and per-module permissions.',
                'is_enabled' => true,
            ],
            [
                'key' => 'vendor_management',
                'name' => 'Vendor Management',
                'description' => 'Manage service vendor data.',
                'is_enabled' => true,
            ],
            [
                'key' => 'activities',
                'name' => 'Activities',
                'description' => 'Manage vendor activities, rates, and operational details.',
                'is_enabled' => true,
            ],
            [
                'key' => 'itineraries',
                'name' => 'Itineraries',
                'description' => 'Manage itinerary data.',
                'is_enabled' => true,
            ],
            [
                'key' => 'tourist_attractions',
                'name' => 'Tourist Attractions',
                'description' => 'Manage tourist attraction data for itineraries.',
                'is_enabled' => true,
            ],
            [
                'key' => 'quotation_templates',
                'name' => 'Quotation Templates',
                'description' => 'Manage quotation templates.',
                'is_enabled' => true,
            ],
            // ...existing code...
        ];

        foreach ($modules as $module) {
            Module::query()->updateOrCreate(
                ['key' => $module['key']],
                $module
            );
        }

        Module::query()->whereIn('key', [
            'admin_dashboard',
            'sales_dashboard',
            'finance_dashboard',
            'operations_dashboard',
            'director_dashboard',
            'services',
            'services_accommodations',
            'services_transports',
            'services_guides',
            'services_attractions',
            'services_travel_activities',
        ])->delete();

        Module::query()->where('key', 'service_catalog')->delete();
    }
}
