@extends('layouts.master')

@section('content')
    @php
        $destinations = $destinations ?? collect();
    @endphp
    <div class="max-w-5xl space-y-6">
        

        @if (session('success'))
            <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="POST" action="{{ route('company-settings.update') }}" enctype="multipart/form-data" class="space-y-6" data-location-autofill data-location-resolve-url="{{ route('location.resolve-google-map') }}">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Company Name *</label>
                        <input name="company_name" value="{{ old('company_name', $settings->company_name) }}" class="mt-1 dark:border-gray-600 app-input" required>
                        @error('company_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Legal Name</label>
                        <input name="legal_name" value="{{ old('legal_name', $settings->legal_name) }}" class="mt-1 dark:border-gray-600 app-input">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Email</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $settings->contact_email) }}" class="mt-1 dark:border-gray-600 app-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Phone</label>
                        <input name="contact_phone" value="{{ old('contact_phone', $settings->contact_phone) }}" class="mt-1 dark:border-gray-600 app-input">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">WhatsApp</label>
                        <input name="contact_whatsapp" value="{{ old('contact_whatsapp', $settings->contact_whatsapp) }}" class="mt-1 dark:border-gray-600 app-input">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Website</label>
                        <input type="url" name="website" value="{{ old('website', $settings->website) }}" class="mt-1 dark:border-gray-600 app-input">
                    </div>
                    <div class="md:col-span-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Timezone</label>
                            <input name="timezone" value="{{ old('timezone', $settings->timezone) }}" placeholder="Asia/Jakarta" class="mt-1 dark:border-gray-600 app-input">
                        </div>
                    </div>
                </div>

                @include('components.map-standard-section', [
                    'title' => 'Map & Location Standard',
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
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Footer Note</label>
                    <textarea name="footer_note" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('footer_note', $settings->footer_note) }}</textarea>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Favicon</label>
                        <input type="file" name="favicon" accept="image/png,image/x-icon,image/webp,image/jpeg" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        @error('favicon') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        @if ($settings->favicon_path)
                            <div class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <img src="{{ asset('storage/' . $settings->favicon_path) }}" alt="Favicon" class="h-6 w-6 rounded border border-gray-200 dark:border-gray-700">
                                <span>Current favicon</span>
                            </div>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Company Logo</label>
                        <input type="file" name="logo" accept="image/png,image/webp,image/jpeg" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        @error('logo') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        @if ($settings->logo_path)
                            <div class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="Logo" class="h-8 w-8 rounded border border-gray-200 dark:border-gray-700 object-cover">
                                <span>Current logo</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button  class="btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection





