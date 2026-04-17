<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @php
            $appTitle = config('app.name', 'VOYEX CRM');
            $faviconUrl = null;
            $faviconMime = 'image/x-icon';

            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('company_settings')) {
                    $settings = \App\Models\CompanySetting::query()->select(['company_name', 'favicon_path', 'updated_at'])->first();
                    if (! empty($settings?->company_name)) {
                        $appTitle = trim((string) $settings->company_name);
                    }
                    if (! empty($settings?->favicon_path)) {
                        $faviconUrl = \App\Support\ImageThumbnailGenerator::resolveOriginalPublicUrl($settings->favicon_path);
                        if ($faviconUrl && ! empty($settings?->updated_at)) {
                            $faviconUrl .= '?v=' . $settings->updated_at->timestamp;
                        }
                        $ext = strtolower((string) pathinfo((string) $settings->favicon_path, PATHINFO_EXTENSION));
                        $faviconMime = match ($ext) {
                            'png' => 'image/png',
                            'ico' => 'image/x-icon',
                            'webp' => 'image/webp',
                            'jpg', 'jpeg' => 'image/jpeg',
                            default => 'image/x-icon',
                        };
                    }
                }
            } catch (\Throwable $e) {
                // Keep fallback branding when DB is not ready.
            }
        @endphp

        <title>{{ $appTitle }}</title>
        @if ($faviconUrl)
            <link rel="icon" type="{{ $faviconMime }}" href="{{ $faviconUrl }}">
            <link rel="shortcut icon" type="{{ $faviconMime }}" href="{{ $faviconUrl }}">
            <link rel="apple-touch-icon" href="{{ $faviconUrl }}">
        @endif

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="page-spinner" data-page-spinner aria-hidden="true">
            <div class="page-spinner__inner">
                <div class="page-spinner__ring" aria-hidden="true"></div>
                <div class="page-spinner__text">Loading...</div>
            </div>
        </div>
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
            <div>
                <a href="/">
                    <x-application-logo class="w-20 h-20 fill-current text-gray-500" />
                </a>
            </div>

            <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
