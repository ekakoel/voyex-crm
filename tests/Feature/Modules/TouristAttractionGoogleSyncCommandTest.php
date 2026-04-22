<?php

namespace Tests\Feature\Modules;

use Tests\TestCase;

class TouristAttractionGoogleSyncCommandTest extends TestCase
{
    public function test_command_fails_when_google_places_api_key_is_missing(): void
    {
        config()->set('services.google_maps.places_api_key', null);

        $this->artisan('tourist-attractions:sync-google --destination_id=1 --dry-run')
            ->expectsOutput('GOOGLE_MAPS_PLACES_API_KEY is not configured.')
            ->assertExitCode(1);
    }
}
