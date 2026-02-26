<?php

namespace Tests\Feature\Modules;

use Illuminate\Support\Str;

class RolesSmokeTest extends ModuleSmokeTestCase
{
    public function test_roles_get_and_store_smoke(): void
    {
        $this->get(route('roles.index'))->assertOk();
        $this->get(route('roles.create'))->assertOk();

        $this->post(route('roles.store'), [
            'name' => 'Smoke Role ' . Str::upper(Str::random(6)),
            'permissions' => [],
        ])->assertRedirect(route('roles.index'));
    }
}

