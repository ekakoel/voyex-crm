<?php

namespace Tests\Feature\Modules;

use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

class UsersSmokeTest extends ModuleSmokeTestCase
{
    public function test_users_get_and_store_smoke(): void
    {
        $this->get(route('users.index'))->assertOk();
        $this->get(route('users.create'))->assertOk();

        $role = Role::query()->firstOrCreate([
            'name' => 'Manager',
            'guard_name' => 'web',
        ]);

        $this->post(route('users.store'), [
            'name' => 'Smoke User',
            'email' => 'smoke-user-' . Str::random(8) . '@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => [$role->name],
        ])->assertRedirect(route('users.index'));
    }
}
