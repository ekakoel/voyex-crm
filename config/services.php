<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'fx' => [
        'url' => env('FX_API_URL', 'https://api.frankfurter.app/latest'),
        'base' => env('FX_BASE_CURRENCY', 'USD'),
        'timeout' => (int) env('FX_TIMEOUT', 10),
    ],

    'google_maps' => [
        'places_api_key' => env('GOOGLE_MAPS_PLACES_API_KEY'),
        'places_base_url' => env('GOOGLE_MAPS_PLACES_BASE_URL', 'https://places.googleapis.com/v1'),
        'places_timeout' => (int) env('GOOGLE_MAPS_PLACES_TIMEOUT', 12),
        'places_connect_timeout' => (int) env('GOOGLE_MAPS_PLACES_CONNECT_TIMEOUT', 5),
        'places_retry_times' => (int) env('GOOGLE_MAPS_PLACES_RETRY_TIMES', 2),
        'places_retry_sleep_ms' => (int) env('GOOGLE_MAPS_PLACES_RETRY_SLEEP_MS', 250),
        'places_next_page_delay_ms' => (int) env('GOOGLE_MAPS_PLACES_NEXT_PAGE_DELAY_MS', 1500),
        'places_default_language' => env('GOOGLE_MAPS_PLACES_DEFAULT_LANGUAGE', 'en'),
        'places_default_region' => env('GOOGLE_MAPS_PLACES_DEFAULT_REGION', 'ID'),
        'places_default_max_results' => (int) env('GOOGLE_MAPS_PLACES_DEFAULT_MAX_RESULTS', 60),
    ],

];
