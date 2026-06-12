@include('components.location-map-picker', [
    'mapTitle' => ui_phrase('settings location on map'),
    'mapHeightClass' => 'h-[320px]',
    'interactive' => true,
    'iconHtml' => '<span class="location-map-pin__inner"><i class="fa-solid fa-building" aria-hidden="true"></i></span>',
    'invalidHint' => ui_phrase('settings map hint invalid'),
    'successHint' => ui_phrase('settings map hint success'),
    'movedHint' => ui_phrase('settings map hint marker moved'),
])
