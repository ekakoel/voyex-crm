@include('components.location-map-picker', [
    'mapTitle' => $mapTitle ?? 'Airport Location Map',
    'mapHeightClass' => $mapHeightClass ?? 'h-[360px]',
    'latValue' => $latValue ?? null,
    'lngValue' => $lngValue ?? null,
    'interactive' => $interactive ?? true,
    'iconHtml' => '<span class="location-map-pin__inner" style="background:#0369a1;"><i class="fa-solid fa-plane-departure" aria-hidden="true"></i></span>',
    'successHint' => 'Airport location displayed successfully on the map.',
    'movedHint' => 'Marker moved. Coordinates updated successfully.',
])
