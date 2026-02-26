<?php

namespace Tests\Feature\Modules;

use Illuminate\Support\Str;

class UsersSmokeTest extends ModuleSmokeTestCase
{
    public function test_users_get_and_store_smoke(): void
    {
        $this->get(route('users.index'))->assertOk();
        $this->get(route('users.create'))->assertOk();

        $this->post(route('users.store'), [
            'name' => 'Smoke User',
            'email' => 'smoke-user-' . Str::random(8) . '@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => ['Super Admin'],
        ])->assertRedirect(route('users.index'));
    }
}

