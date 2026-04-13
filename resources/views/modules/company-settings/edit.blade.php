@extends('layouts.master')

@section('page_title', __('ui.modules.company_settings.page_title'))
@section('page_subtitle', __('ui.modules.company_settings.page_subtitle'))

@section('content')
    @php
        $destinations = $destinations ?? collect();
    @endphp
    <div class="space-y-6 module-page module-page--company-settings">
        <div class="module-grid-8-4">
            <div class="module-grid-main">
        

        @if (session('success'))
            <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="module-form-wrap">
            <form method="POST" action="{{ route('company-settings.update') }}" enctype="multipart/form-data" class="space-y-6" data-location-autofill data-location-resolve-url="{{ route('location.resolve-google-map') }}">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.company_settings.company_name') }} *</label>
                        <input name="company_name" value="{{ old('company_name', $settings->company_name) }}" class="mt-1 dark:border-gray-600 app-input" required>
                        @error('company_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.company_settings.tagline') }}</label>
                        <input name="tagline" value="{{ old('tagline', $settings->tagline) }}" class="mt-1 dark:border-gray-600 app-input" placeholder="{{ __('ui.modules.company_settings.tagline_placeholder') }}">
                        @error('tagline') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.company_settings.legal_name') }}</label>
                        <input name="legal_name" value="{{ old('legal_name', $settings->legal_name) }}" class="mt-1 dark:border-gray-600 app-input">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.company_settings.contact_email') }}</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $settings->contact_email) }}" class="mt-1 dark:border-gray-600 app-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.company_settings.contact_phone') }}</label>
                        <input name="contact_phone" value="{{ old('contact_phone', $settings->contact_phone) }}" class="mt-1 dark:border-gray-600 app-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.company_settings.whatsapp') }}</label>
                        <input name="contact_whatsapp" value="{{ old('contact_whatsapp', $settings->contact_whatsapp) }}" class="mt-1 dark:border-gray-600 app-input">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.company_settings.website') }}</label>
                        <input type="url" name="website" value="{{ old('website', $settings->website) }}" class="mt-1 dark:border-gray-600 app-input">
                    </div>
                    <div class="md:col-span-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.company_settings.timezone') }}</label>
                            <input name="timezone" value="{{ old('timezone', $settings->timezone) }}" placeholder="{{ __('ui.modules.company_settings.timezone_placeholder') }}" class="mt-1 dark:border-gray-600 app-input">
                        </div>
                    </div>
                </div>

                @include('components.map-standard-section', [
                    'title' => __('ui.modules.company_settings.map_location_standard'),
                    'mapPartial' => 'modules.company-settings.partials._location-map',
                    'mapFieldName' => 'google_maps_url',
                    'mapFieldErrorKey' => 'google_maps_url',
                    'mapValue' => old('google_maps_url', $settings->google_maps_url ?? ''),
                    'latitudeValue' => old('latitude', $settings->latitude ?? ''),
                    'longitudeValue' => old('longitude', $settings->longitude ?? ''),
                    'addressValue' => old('address', $settings->address ?? ''),
                    'cityValue' => old('city', $settings->city ?? ''),
                    'provinceValue' => old('province', $settings->province ?? ''),
                    'countryValue' => old('country', $settings->country ?? ''),
                    'destinationValue' => old('destination_id', $settings->destination_id ?? ''),
                    'destinations' => $destinations,
                ])

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.company_settings.footer_note') }}</label>
                    <textarea name="footer_note" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('footer_note', $settings->footer_note) }}</textarea>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('ui.modules.company_settings.footer_note_hint') }}</p>
                </div>

                <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                    <p class="text-sm font-semibold text-slate-700 dark:text-slate-200">{{ __('ui.modules.company_settings.auth_theme_title') }}</p>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">{{ __('ui.modules.company_settings.auth_theme_hint') }}</p>

                    <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.company_settings.primary_color') }}</label>
                            <input type="color" name="auth_primary_color" value="{{ old('auth_primary_color', $settings->auth_primary_color ?? '#2563eb') }}" class="mt-1 h-11 w-full rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-600 dark:bg-gray-900">
                            @error('auth_primary_color') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.company_settings.primary_hover_color') }}</label>
                            <input type="color" name="auth_primary_hover_color" value="{{ old('auth_primary_hover_color', $settings->auth_primary_hover_color ?? '#1e40af') }}" class="mt-1 h-11 w-full rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-600 dark:bg-gray-900">
                            @error('auth_primary_hover_color') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.company_settings.background_from') }}</label>
                            <input type="color" name="auth_background_from_color" value="{{ old('auth_background_from_color', $settings->auth_background_from_color ?? '#f5f7fb') }}" class="mt-1 h-11 w-full rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-600 dark:bg-gray-900">
                            @error('auth_background_from_color') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.company_settings.background_to') }}</label>
                            <input type="color" name="auth_background_to_color" value="{{ old('auth_background_to_color', $settings->auth_background_to_color ?? '#eaf1ff') }}" class="mt-1 h-11 w-full rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-600 dark:bg-gray-900">
                            @error('auth_background_to_color') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.company_settings.card_background') }}</label>
                            <input type="color" name="auth_card_background_color" value="{{ old('auth_card_background_color', $settings->auth_card_background_color ?? '#ffffff') }}" class="mt-1 h-11 w-full rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-600 dark:bg-gray-900">
                            @error('auth_card_background_color') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.company_settings.card_border') }}</label>
                            <input type="color" name="auth_card_border_color" value="{{ old('auth_card_border_color', $settings->auth_card_border_color ?? '#d7d7d7') }}" class="mt-1 h-11 w-full rounded-lg border border-gray-300 bg-white px-2 py-1 dark:border-gray-600 dark:bg-gray-900">
                            @error('auth_card_border_color') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.company_settings.favicon') }}</label>
                        <input type="file" name="favicon" accept="image/png,image/x-icon,image/webp,image/jpeg" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        @error('favicon') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        @if ($settings->favicon_path)
                            @php($faviconPreviewUrl = \App\Support\ImageThumbnailGenerator::resolvePublicUrl($settings->favicon_path) ?? \App\Support\ImageThumbnailGenerator::resolveOriginalPublicUrl($settings->favicon_path))
                            @if ($faviconPreviewUrl)
                                <div class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <img src="{{ $faviconPreviewUrl }}" alt="{{ __('ui.modules.company_settings.favicon') }}" class="h-6 w-6 rounded border border-gray-200 dark:border-gray-700">
                                    <span>{{ __('ui.modules.company_settings.current_favicon') }}</span>
                                </div>
                            @endif
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ __('ui.modules.company_settings.company_logo') }}</label>
                        <input type="file" name="logo" accept="image/png,image/webp,image/jpeg" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        @error('logo') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        @if ($settings->logo_path)
                            @php($logoPreviewUrl = \App\Support\ImageThumbnailGenerator::resolvePublicUrl($settings->logo_path) ?? \App\Support\ImageThumbnailGenerator::resolveOriginalPublicUrl($settings->logo_path))
                            @if ($logoPreviewUrl)
                                <div class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                    <img src="{{ $logoPreviewUrl }}" alt="{{ __('ui.modules.company_settings.company_logo') }}" class="h-8 w-8 rounded border border-gray-200 dark:border-gray-700 object-cover">
                                    <span>{{ __('ui.modules.company_settings.current_logo') }}</span>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button  class="btn-primary">{{ __('ui.modules.company_settings.save_changes') }}</button>
                </div>
            </form>
        </div>
            </div>
            <aside class="module-grid-side">
                <div class="module-card p-5 text-sm text-slate-600 dark:text-slate-300">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('ui.common.info') }}</p>
                    <p class="mt-2">{{ __('ui.modules.company_settings.info_text') }}</p>
                </div>
            </aside>
        </div>
    </div>
@endsection
