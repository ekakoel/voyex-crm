@include('components.location-map-picker', [
    'mapTitle' => $mapTitle ?? ui_phrase('Location on Map (open map)'),
    'mapHeightClass' => $mapHeightClass ?? 'h-[360px]',
    'latValue' => $latValue ?? null,
    'lngValue' => $lngValue ?? null,
    'interactive' => $interactive ?? true,
    'iconHtml' => '<span class="location-map-pin__inner"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i></span>',
    'successHint' => ui_phrase('Destination location displayed successfully on the map.'),
    'movedHint' => ui_phrase('Marker moved. Coordinates updated successfully.'),
])
