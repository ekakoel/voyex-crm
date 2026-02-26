<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class InquirySeeder extends Seeder
{
    public function run(): void
    {
        $creator = User::query()->where('email', 'admin@example.com')->first()
            ?? User::query()->first();

        if (! $creator) {
            return;
        }

        $customers = [
            [
                'name' => 'Budi Santoso',
                'company_name' => null,
                'code' => 'CUST-TEST-001',
                'email' => 'budi.santoso@example.test',
                'phone' => '+62 811-2000-1001',
                'address' => 'Jl. Sunset Road No. 10, Kuta, Bali',
                'country' => 'Indonesia',
                'customer_type' => 'individual',
            ],
            [
                'name' => 'PT Nusantara Holiday',
                'company_name' => 'PT Nusantara Holiday',
                'code' => 'CUST-TEST-002',
                'email' => 'ops@nusantaraholiday.test',
                'phone' => '+62 811-2000-1002',
                'address' => 'Jl. Gatot Subroto No. 88, Denpasar, Bali',
                'country' => 'Indonesia',
                'customer_type' => 'company',
            ],
            [
                'name' => 'Sarah Wijaya',
                'company_name' => null,
                'code' => 'CUST-TEST-003',
                'email' => 'sarah.wijaya@example.test',
                'phone' => '+62 811-2000-1003',
                'address' => 'Jl. Teuku Umar No. 5, Denpasar, Bali',
                'country' => 'Indonesia',
                'customer_type' => 'individual',
            ],
        ];

        $customerModels = [];
        foreach ($customers as $customerData) {
            $customerModels[] = Customer::query()->updateOrCreate(
                ['code' => $customerData['code']],
                $customerData + ['created_by' => $creator->id]
            );
        }

        $inquiries = [
            [
                'customer_index' => 0,
                'source' => 'Website',
                'status' => 'new',
                'priority' => 'high',
                'deadline' => Carbon::now()->addDays(5)->toDateString(),
                'assigned_to' => $creator->id,
                'notes' => 'Couple trip Bali 4D3N, fokus Ubud + Nusa Penida.',
                'reminder_enabled' => true,
            ],
            [
                'customer_index' => 1,
                'source' => 'Referral',
                'status' => 'follow_up',
                'priority' => 'normal',
                'deadline' => Carbon::now()->addDays(10)->toDateString(),
                'assigned_to' => $creator->id,
                'notes' => 'Permintaan paket corporate outing 25 pax.',
                'reminder_enabled' => true,
            ],
            [
                'customer_index' => 2,
                'source' => 'Instagram',
                'status' => 'quoted',
                'priority' => 'normal',
                'deadline' => Carbon::now()->addDays(7)->toDateString(),
                'assigned_to' => $creator->id,
                'notes' => 'Family trip dengan child-friendly activities.',
                'reminder_enabled' => true,
            ],
        ];

        foreach ($inquiries as $payload) {
            $customer = $customerModels[$payload['customer_index']] ?? null;
            if (! $customer) {
                continue;
            }

            Inquiry::query()->create([
                'customer_id' => $customer->id,
                'source' => $payload['source'],
                'status' => $payload['status'],
                'priority' => $payload['priority'],
                'deadline' => $payload['deadline'],
                'assigned_to' => $payload['assigned_to'],
                'notes' => $payload['notes'],
                'reminder_enabled' => $payload['reminder_enabled'],
            ]);
        }
    }
}
