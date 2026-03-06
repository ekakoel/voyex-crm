<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Support\LocationResolver;
use Illuminate\Http\Request;

class LocationResolverController extends Controller
{
    public function resolve(Request $request, LocationResolver $resolver)
    {
        $validated = $request->validate([
            'google_maps_url' => ['required', 'url', 'max:5000'],
        ]);

        $payload = [
            'google_maps_url' => $validated['google_maps_url'],
            'location' => null,
            'city' => null,
            'province' => null,
            'country' => null,
            'address' => null,
            'latitude' => null,
            'longitude' => null,
            'timezone' => null,
            'destination_id' => null,
        ];

        $resolver->enrichFromGoogleMapsUrl($payload);

        $province = trim((string) ($payload['province'] ?? ''));
        if ($province !== '') {
            $destination = Destination::query()
                ->whereRaw('LOWER(province) = ?', [mb_strtolower($province)])
                ->first();
            if ($destination) {
                $payload['destination_id'] = (int) $destination->id;
            }
        }

        return response()->json([
            'data' => $payload,
        ]);
    }
}
