<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
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
            [
                'name' => 'PT Garuda Event',
                'company_name' => 'PT Garuda Event',
                'code' => 'CUST-TEST-004',
                'email' => 'contact@garudaevent.test',
                'phone' => '+62 811-2000-1004',
                'address' => 'Jl. Imam Bonjol No. 22, Denpasar, Bali',
                'country' => 'Indonesia',
                'customer_type' => 'company',
            ],
            [
                'name' => 'Andi Pratama',
                'company_name' => null,
                'code' => 'CUST-TEST-005',
                'email' => 'andi.pratama@example.test',
                'phone' => '+62 811-2000-1005',
                'address' => 'Jl. Raya Ubud No. 15, Gianyar, Bali',
                'country' => 'Indonesia',
                'customer_type' => 'individual',
            ],
        ];

        foreach ($customers as $payload) {
            Customer::query()->updateOrCreate(
                ['code' => $payload['code']],
                $payload + ['created_by' => $creator->id]
            );
        }
    }
}
