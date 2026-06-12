<?php

namespace Tests\Unit\Support;

use App\Support\LocationResolver;
use Tests\TestCase;

class LocationResolverTest extends TestCase
{
    public function test_extract_coordinates_from_google_maps_place_url(): void
    {
        $resolver = new LocationResolver();

        $coordinates = $resolver->extractCoordinatesFromGoogleMapsUrl(
            'https://www.google.com/maps/place/Example/@-8.6500000,115.2167000,17z/data=!3m1!4b1'
        );

        $this->assertSame([-8.65, 115.2167], $coordinates);
    }

    public function test_extract_coordinates_from_google_maps_query_url(): void
    {
        $resolver = new LocationResolver();

        $coordinates = $resolver->extractCoordinatesFromGoogleMapsUrl(
            'https://maps.google.com/?q=-8.409518,115.188919'
        );

        $this->assertSame([-8.409518, 115.188919], $coordinates);
    }

    public function test_extract_coordinates_from_google_maps_data_segment(): void
    {
        $resolver = new LocationResolver();

        $coordinates = $resolver->extractCoordinatesFromGoogleMapsUrl(
            'https://www.google.com/maps/place/Foo/data=!4m6!3m5!1sabc!8m2!3d-8.123456!4d115.654321'
        );

        $this->assertSame([-8.123456, 115.654321], $coordinates);
    }
}
