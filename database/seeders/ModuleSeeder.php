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
        // Migrate old key if the project already used service_catalog.
        if (Module::query()->where('key', 'service_catalog')->exists() && ! Module::query()->where('key', 'services')->exists()) {
            Module::query()
                ->where('key', 'service_catalog')
                ->update([
                    'key' => 'services',
                    'name' => 'Services',
                    'description' => 'Manage all services and their submodules.',
                ]);
        }

        $modules = [
            [
                'key' => 'admin_dashboard',
                'name' => 'Admin Dashboard',
                'description' => 'Dashboard access for admin.',
                'is_enabled' => true,
            ],
            [
                'key' => 'sales_dashboard',
                'name' => 'Sales Dashboard',
                'description' => 'Dashboard access for the sales team.',
                'is_enabled' => true,
            ],
            [
                'key' => 'finance_dashboard',
                'name' => 'Finance Dashboard',
                'description' => 'Dashboard access for the finance team.',
                'is_enabled' => true,
            ],
            [
                'key' => 'operations_dashboard',
                'name' => 'Operations Dashboard',
                'description' => 'Dashboard access for the operations team.',
                'is_enabled' => true,
            ],
            [
                'key' => 'director_dashboard',
                'name' => 'Director Dashboard',
                'description' => 'Dashboard access for directors.',
                'is_enabled' => true,
            ],
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
                'key' => 'sales_target',
                'name' => 'Sales Target',
                'description' => 'Monitor sales targets by team.',
                'is_enabled' => true,
            ],
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
                'key' => 'services',
                'name' => 'Services',
                'description' => 'Manage all services and their submodules.',
                'is_enabled' => true,
            ],
            [
                'key' => 'services_accommodations',
                'name' => 'Services - Accommodations',
                'description' => 'Manage accommodation services.',
                'is_enabled' => true,
            ],
            [
                'key' => 'services_transports',
                'name' => 'Services - Transports',
                'description' => 'Manage transport services.',
                'is_enabled' => true,
            ],
            [
                'key' => 'services_guides',
                'name' => 'Services - Guides',
                'description' => 'Manage guide services.',
                'is_enabled' => true,
            ],
            [
                'key' => 'services_attractions',
                'name' => 'Services - Attractions',
                'description' => 'Manage attraction services.',
                'is_enabled' => true,
            ],
            [
                'key' => 'services_travel_activities',
                'name' => 'Services - Travel Activities',
                'description' => 'Manage travel activity services.',
                'is_enabled' => true,
            ],
            [
                'key' => 'quotation_templates',
                'name' => 'Quotation Templates',
                'description' => 'Manage quotation templates.',
                'is_enabled' => true,
            ],
            [
                'key' => 'promotions',
                'name' => 'Promotions',
                'description' => 'Manage discounts and promotions.',
                'is_enabled' => true,
            ],
        ];

        foreach ($modules as $module) {
            Module::query()->updateOrCreate(
                ['key' => $module['key']],
                $module
            );
        }

        Module::query()->where('key', 'service_catalog')->delete();
    }
}
