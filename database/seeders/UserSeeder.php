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
                'name' => 'Administrator',
                'email' => 'admin@example.com',
                'role' => 'Administrator',
            ],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'role' => 'Super Admin',
            ],
            [
                'name' => 'Manager',
                'email' => 'manager@example.com',
                'role' => 'Manager',
            ],
            [
                'name' => 'Marketing',
                'email' => 'marketing@example.com',
                'role' => 'Marketing',
            ],
            [
                'name' => 'Director',
                'email' => 'director@example.com',
                'role' => 'Director',
            ],
            [
                'name' => 'Finance',
                'email' => 'finance@example.com',
                'role' => 'Finance',
            ],
            [
                'name' => 'Reservation',
                'email' => 'reservation@example.com',
                'role' => 'Reservation',
            ],
            [
                'name' => 'Editor',
                'email' => 'editor@example.com',
                'role' => 'Editor',
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
                $user->syncRoles([$role->name]);
            }
        }
    }
}
