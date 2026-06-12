@include('components.location-map-picker', [
    'mapTitle' => $mapTitle ?? 'Hotel Location Map',
    'mapHeightClass' => $mapHeightClass ?? 'h-[320px]',
    'latValue' => $latValue ?? null,
    'lngValue' => $lngValue ?? null,
    'interactive' => $interactive ?? true,
    'iconHtml' => '<span class="location-map-pin__inner"><i class="fa-solid fa-hotel" aria-hidden="true"></i></span>',
    'successHint' => 'Hotel location displayed successfully on the map.',
    'movedHint' => 'Marker moved. Coordinates updated successfully.',
])
