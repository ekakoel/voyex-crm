<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaultPassword = Hash::make('password');

        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'role' => 'Admin',
            ],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'role' => 'Super Admin',
            ],
            [
                'name' => 'Sales Manager',
                'email' => 'sales.manager@example.com',
                'role' => 'Sales Manager',
            ],
            [
                'name' => 'Sales Agent',
                'email' => 'sales.agent@example.com',
                'role' => 'Sales Agent',
            ],
            [
                'name' => 'Director',
                'email' => 'Director@example.com',
                'role' => 'Director',
            ],
            [
                'name' => 'Finance',
                'email' => 'finance@example.com',
                'role' => 'Finance',
            ],
            [
                'name' => 'Operations',
                'email' => 'operations@example.com',
                'role' => 'Operations',
            ],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $defaultPassword,
                ]
            );

            $role = Role::where('name', $data['role'])->first();
            if ($role) {
                $user->assignRole($role);
            }
        }
    }
}
