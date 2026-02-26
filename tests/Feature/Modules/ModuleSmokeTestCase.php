<?php

namespace Tests\Feature\Modules;

use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

abstract class ModuleSmokeTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticateAsSuperAdmin();
    }

    protected function authenticateAsSuperAdmin(): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => 'web',
        ]);

        $user = User::query()->first();
        if (! $user) {
            $user = User::query()->create([
                'name' => 'Smoke Admin',
                'email' => 'smoke-admin@example.com',
                'password' => bcrypt('password123'),
            ]);
        }

        if (! $user->hasRole($role->name)) {
            $user->assignRole($role);
        }

        $this->actingAs($user);
    }

    protected function createCustomer(array $overrides = []): Customer
    {
        return Customer::query()->create(array_merge([
            'code' => 'SMK-CUST-' . strtoupper(Str::random(8)),
            'name' => 'Smoke Customer',
            'email' => 'smoke-customer-' . Str::random(8) . '@example.com',
            'phone' => '081234567890',
            'address' => 'Smoke Street',
            'country' => 'Indonesia',
            'customer_type' => 'individual',
            'created_by' => auth()->id(),
        ], $overrides));
    }

    protected function createInquiry(Customer $customer, array $overrides = []): Inquiry
    {
        return Inquiry::query()->create(array_merge([
            'customer_id' => $customer->id,
            'source' => 'website',
            'status' => 'new',
            'priority' => 'normal',
            'notes' => 'Smoke inquiry',
            'reminder_enabled' => true,
        ], $overrides));
    }

    protected function createQuotation(Inquiry $inquiry, array $overrides = []): Quotation
    {
        return Quotation::query()->create(array_merge([
            'quotation_number' => 'QT-SMK-' . now()->format('YmdHis') . '-' . random_int(100, 999),
            'inquiry_id' => $inquiry->id,
            'status' => 'draft',
            'validity_date' => now()->addDays(7)->format('Y-m-d'),
            'sub_total' => 100000,
            'discount_type' => null,
            'discount_value' => 0,
            'final_amount' => 100000,
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ], $overrides));
    }
}

