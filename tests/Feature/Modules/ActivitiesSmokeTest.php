<?php

namespace Tests\Feature\Modules;

use App\Models\Activity;
use App\Models\Vendor;
use Illuminate\Support\Str;

class ActivitiesSmokeTest extends ModuleSmokeTestCase
{
    public function test_activities_get_and_store_smoke(): void
    {
        $vendor = Vendor::query()->create([
            'name' => 'Smoke Vendor Activity ' . Str::upper(Str::random(4)),
            'location' => 'Bali',
            'latitude' => -8.409518,
            'longitude' => 115.188919,
            'city' => 'Denpasar',
            'province' => 'Bali',
            'is_active' => true,
        ]);

        $this->get(route('activities.index'))->assertOk();
        $this->get(route('activities.create'))->assertOk();

        $name = 'Smoke Activity ' . Str::upper(Str::random(5));
        $this->post(route('activities.store'), [
            'vendor_id' => $vendor->id,
            'name' => $name,
            'activity_type' => 'Adventure',
            'duration_minutes' => 120,
            'currency' => 'IDR',
            'contract_price' => 500000,
            'agent_price' => 650000,
            'capacity_min' => 1,
            'capacity_max' => 10,
            'is_active' => 1,
        ])->assertRedirect(route('activities.index'));

        $activity = Activity::query()->where('name', $name)->latest()->firstOrFail();
        $this->get(route('activities.edit', $activity))->assertOk();
    }
}

