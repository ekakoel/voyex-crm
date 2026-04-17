@props([
    'user' => null,
    'fallback' => '-',
    'maskedLabel' => 'System',
    'viewer' => auth()->user(),
])

@php
    $name = '';

    if ($user instanceof \App\Models\User) {
        $name = $user->displayNameFor($viewer, $maskedLabel);
    } elseif ($user) {
        $name = (string) data_get($user, 'name', '');
    }

    $name = trim($name) !== '' ? $name : $fallback;
@endphp

{{ $name }}
