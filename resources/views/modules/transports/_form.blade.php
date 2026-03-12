@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $transport = $transport ?? null;
    $destinations = $destinations ?? collect();

    $transportTypes = ['car', 'van', 'bus', 'boat', 'ferry', 'train', 'helicopter', 'other'];
    $serviceScopes = ['city_transfer', 'airport_transfer', 'intercity', 'charter', 'daily_tour', 'multi_day'];
    $fuelTypes = ['petrol', 'diesel', 'electric', 'hybrid', 'other'];
    $transmissions = ['manual', 'automatic'];

    $oldUnits = old('units');
    if (!is_array($oldUnits)) {
        $oldUnits = isset($transport)
            ? $transport->units->map(fn ($unit) => [
                'name' => $unit->name,
                'vehicle_type' => $unit->vehicle_type,
                'brand_model' => $unit->brand_model,
                'seat_capacity' => $unit->seat_capacity,
                'luggage_capacity' => $unit->luggage_capacity,
                'contract_rate' => $unit->contract_rate,
                'publish_rate' => $unit->publish_rate,
                'overtime_rate' => $unit->overtime_rate,
                'currency' => $unit->currency,
                'fuel_type' => $unit->fuel_type,
                'transmission' => $unit->transmission,
                'air_conditioned' => $unit->air_conditioned ? '1' : '0',
                'with_driver' => $unit->with_driver ? '1' : '0',
                'existing_images' => $unit->images ?? [],
                'benefits' => $unit->benefits,
                'notes' => $unit->notes,
                'is_active' => $unit->is_active ? '1' : '0',
            ])->toArray()
            : [];
    }

    if ($oldUnits === []) {
        $oldUnits = [[
            'name' => '',
            'vehicle_type' => '',
            'brand_model' => '',
            'seat_capacity' => 4,
            'luggage_capacity' => '',
            'contract_rate' => '',
            'publish_rate' => '',
            'overtime_rate' => '',
            'currency' => 'IDR',
            'fuel_type' => 'petrol',
            'transmission' => 'automatic',
            'air_conditioned' => '1',
            'with_driver' => '1',
            'existing_images' => [],
            'benefits' => '',
            'notes' => '',
            'is_active' => '1',
        ]];
    }
@endphp

<div class="space-y-5" data-location-autofill data-location-resolve-url="{{ route('location.resolve-google-map') }}">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Code</label>
            <input name="code" value="{{ old('code', $transport->code ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
            @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Service Name</label>
            <input name="name" value="{{ old('name', $transport->name ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Transport Type</label>
            <select name="transport_type" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                <option value="">Select type</option>
                @foreach ($transportTypes as $type)
                    <option value="{{ $type }}" @selected(old('transport_type', $transport->transport_type ?? '') === $type)>{{ str_replace('_', ' ', ucfirst($type)) }}</option>
                @endforeach
            </select>
            @error('transport_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Provider Name</label>
            <input name="provider_name" value="{{ old('provider_name', $transport->provider_name ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Service Scope</label>
            <select name="service_scope" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                <option value="">Select scope</option>
                @foreach ($serviceScopes as $scope)
                    <option value="{{ $scope }}" @selected(old('service_scope', $transport->service_scope ?? '') === $scope)>{{ str_replace('_', ' ', ucfirst($scope)) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Website</label>
            <input name="website" type="url" value="{{ old('website', $transport->website ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Name</label>
            <input name="contact_name" value="{{ old('contact_name', $transport->contact_name ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-6">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Google Maps URL</label>
            <div class="mt-1 flex items-center gap-2">
                <input name="google_maps_url" data-location-field="google_maps_url" value="{{ old('google_maps_url', $transport->google_maps_url ?? '') }}" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" placeholder="https://maps.google.com/...">
                <button type="button" data-location-autofill-trigger class="shrink-0 rounded-lg border border-indigo-300 px-3 py-2 text-xs font-semibold text-indigo-700">Auto Fill</button>
            </div>
            @error('google_maps_url') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Destination</label>
            <select name="destination_id" data-location-field="destination_id" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                <option value="">Select destination</option>
                @foreach ($destinations as $destination)
                    <option value="{{ $destination->id }}" data-city="{{ $destination->city ?? '' }}" data-province="{{ $destination->province ?? '' }}" @selected((string) old('destination_id', $transport->destination_id ?? '') === (string) $destination->id)>
                        {{ $destination->province ?: $destination->name }}
                    </option>
                @endforeach
            </select>
            @error('destination_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Location</label>
            <input name="location" data-location-field="location" value="{{ old('location', $transport->location ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">City</label>
            <input name="city" data-location-field="city" value="{{ old('city', $transport->city ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Province</label>
            <input name="province" data-location-field="province" value="{{ old('province', $transport->province ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Country</label>
            <input name="country" data-location-field="country" value="{{ old('country', $transport->country ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Phone</label>
            <input name="contact_phone" value="{{ old('contact_phone', $transport->contact_phone ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Timezone</label>
            <input name="timezone" data-location-field="timezone" value="{{ old('timezone', $transport->timezone ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Address</label>
            <input name="address" data-location-field="address" value="{{ old('address', $transport->address ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Latitude</label>
            <input name="latitude" data-location-field="latitude" type="number" step="0.0000001" value="{{ old('latitude', $transport->latitude ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            @error('latitude') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Longitude</label>
            <input name="longitude" data-location-field="longitude" type="number" step="0.0000001" value="{{ old('longitude', $transport->longitude ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            @error('longitude') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <p data-location-status class="hidden text-xs"></p>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Email</label>
        <input name="contact_email" type="email" value="{{ old('contact_email', $transport->contact_email ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Description</label>
            <textarea name="description" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('description', $transport->description ?? '') }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Inclusions</label>
            <textarea name="inclusions" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('inclusions', $transport->inclusions ?? '') }}</textarea>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Exclusions</label>
            <textarea name="exclusions" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('exclusions', $transport->exclusions ?? '') }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Cancellation Policy</label>
            <textarea name="cancellation_policy" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('cancellation_policy', $transport->cancellation_policy ?? '') }}</textarea>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Notes</label>
        <textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('notes', $transport->notes ?? '') }}</textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Gallery Images</label>
        <div id="transport-gallery-preview"
            class="mt-2 grid grid-cols-2 gap-2 md:grid-cols-5"
            data-remove-endpoint-template="{{ isset($transport) ? route('transports.gallery-images.remove', $transport) : '' }}"
            data-csrf-token="{{ csrf_token() }}">
            @if (!empty($transport?->gallery_images))
                @foreach ($transport->gallery_images as $image)
                    <div class="transport-gallery-existing-item relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700" data-image-path="{{ $image }}">
                        <button
                            type="button"
                            class="transport-gallery-remove-btn absolute right-1 top-1 z-10 inline-flex h-6 w-6 items-center justify-center rounded-full bg-rose-600/95 text-xs font-bold text-white shadow hover:bg-rose-700"
                            title="Remove image"
                            aria-label="Remove image">
                            X
                        </button>
                        <div class="w-full overflow-hidden bg-gray-100 dark:bg-gray-800" style="aspect-ratio: 4 / 3;">
                            <img
                                src="{{ asset('storage/' . \App\Support\ImageThumbnailGenerator::thumbnailPathFor($image)) }}"
                                onerror="this.onerror=null;this.src='{{ asset('storage/' . $image) }}';"
                                alt="Transport gallery"
                                class="h-full w-full object-cover">
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
        <input id="transport-gallery-input" type="file" name="gallery_images[]" accept="image/*" multiple class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        <p id="transport-gallery-limit-note" class="mt-1 hidden text-xs text-amber-600"></p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Upload gambar tanpa batas jenis/ukuran. Saat edit, klik X untuk hapus per gambar dan upload baru akan ditambahkan ke gallery. Semua gambar diproses crop rasio 3:2 dan dibuat thumbnail.</p>
        @error('gallery_images') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('gallery_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('removed_gallery_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
        <div class="mb-3 flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Unit Details (Contract)</p>
            <button type="button" id="add-unit-row" class="rounded-lg border border-indigo-300 px-3 py-1 text-xs font-medium text-indigo-700">Add Unit</button>
        </div>

        <div id="unit-rows" class="space-y-3">
            @foreach ($oldUnits as $index => $unit)
                <div class="unit-row rounded-lg border border-gray-200 p-3 dark:border-gray-700" data-unit-index="{{ $index }}">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
                        <div class="md:col-span-2">
                            <label class="block text-xs text-gray-500">Unit Name</label>
                            <input name="units[{{ $index }}][name]" value="{{ $unit['name'] ?? '' }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Vehicle Type</label>
                            <input name="units[{{ $index }}][vehicle_type]" value="{{ $unit['vehicle_type'] ?? '' }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Brand / Model</label>
                            <input name="units[{{ $index }}][brand_model]" value="{{ $unit['brand_model'] ?? '' }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Seats</label>
                            <input name="units[{{ $index }}][seat_capacity]" type="number" min="1" value="{{ $unit['seat_capacity'] ?? 4 }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Luggage</label>
                            <input name="units[{{ $index }}][luggage_capacity]" type="number" min="0" value="{{ $unit['luggage_capacity'] ?? '' }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-6">
                        <x-money-input
                            label="Contract Rate"
                            label-class="block text-xs text-gray-500"
                            name="units[{{ $index }}][contract_rate]"
                            :value="$unit['contract_rate'] ?? ''"
                            min="0"
                            step="0.01"
                            compact
                            required
                        />
                        <x-money-input
                            label="Publish Rate"
                            label-class="block text-xs text-gray-500"
                            name="units[{{ $index }}][publish_rate]"
                            :value="$unit['publish_rate'] ?? ''"
                            min="0"
                            step="0.01"
                            compact
                        />
                        <x-money-input
                            label="Overtime Rate"
                            label-class="block text-xs text-gray-500"
                            name="units[{{ $index }}][overtime_rate]"
                            :value="$unit['overtime_rate'] ?? ''"
                            min="0"
                            step="0.01"
                            compact
                        />
                        <div>
                            <label class="block text-xs text-gray-500">Currency</label>
                            <input name="units[{{ $index }}][currency]" value="{{ $unit['currency'] ?? 'IDR' }}" maxlength="3" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 uppercase text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Fuel Type</label>
                            <select name="units[{{ $index }}][fuel_type]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                @foreach ($fuelTypes as $fuelType)
                                    <option value="{{ $fuelType }}" @selected(($unit['fuel_type'] ?? 'petrol') === $fuelType)>{{ ucfirst($fuelType) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Transmission</label>
                            <select name="units[{{ $index }}][transmission]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                @foreach ($transmissions as $transmission)
                                    <option value="{{ $transmission }}" @selected(($unit['transmission'] ?? 'automatic') === $transmission)>{{ ucfirst($transmission) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="block text-xs text-gray-500">Benefits</label>
                            <textarea name="units[{{ $index }}][benefits]" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ $unit['benefits'] ?? '' }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Notes</label>
                            <textarea name="units[{{ $index }}][notes]" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ $unit['notes'] ?? '' }}</textarea>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="block text-xs text-gray-500">Unit Cover Images (max 2)</label>
                        <input type="file" name="units[{{ $index }}][images][]" accept="image/*" multiple class="unit-images-input mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Upload maksimal 2 gambar. Pada edit, upload baru akan mengganti gambar lama unit.</p>
                        @if (!empty($unit['existing_images']) && is_array($unit['existing_images']))
                            <div class="mt-2 grid grid-cols-2 gap-2 md:grid-cols-4">
                                @foreach ($unit['existing_images'] as $existingImage)
                                    @if (is_string($existingImage) && $existingImage !== '')
                                        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                                            <img
                                                src="{{ asset('storage/' . \App\Support\ImageThumbnailGenerator::thumbnailPathFor($existingImage)) }}"
                                                onerror="this.onerror=null;this.src='{{ asset('storage/' . $existingImage) }}';"
                                                alt="Unit cover"
                                                class="h-20 w-full object-cover">
                                        </div>
                                        <input type="hidden" name="units[{{ $index }}][existing_images][]" value="{{ $existingImage }}">
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-4">
                        <div>
                            <label class="block text-xs text-gray-500">Air Conditioned</label>
                            <select name="units[{{ $index }}][air_conditioned]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                <option value="1" @selected(($unit['air_conditioned'] ?? '1') === '1')>Yes</option>
                                <option value="0" @selected(($unit['air_conditioned'] ?? '1') === '0')>No</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">With Driver</label>
                            <select name="units[{{ $index }}][with_driver]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                <option value="1" @selected(($unit['with_driver'] ?? '1') === '1')>Yes</option>
                                <option value="0" @selected(($unit['with_driver'] ?? '1') === '0')>No</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Unit Status</label>
                            <select name="units[{{ $index }}][is_active]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                <option value="1" @selected(($unit['is_active'] ?? '1') === '1')>Active</option>
                                <option value="0" @selected(($unit['is_active'] ?? '1') === '0')>Inactive</option>
                            </select>
                        </div>
                        <div class="flex items-end justify-end">
                            <button type="button" class="remove-unit-row rounded-lg border border-rose-300 px-3 py-2 text-xs font-medium text-rose-700">Remove Unit</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @error('units') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('units.*.name') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('units.*.contract_rate') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('units.*.images') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('units.*.images.*') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('is_active', $transport->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-200">Active</span>
    </div>

    <div class="flex items-center gap-2">
        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ $buttonLabel }}</button>
        <a href="{{ route('transports.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Cancel</a>
    </div>
</div>

<template id="unit-row-template">
    <div class="unit-row rounded-lg border border-gray-200 p-3 dark:border-gray-700" data-unit-index="__INDEX__">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
            <div class="md:col-span-2"><label class="block text-xs text-gray-500">Unit Name</label><input name="units[__INDEX__][name]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required></div>
            <div><label class="block text-xs text-gray-500">Vehicle Type</label><input name="units[__INDEX__][vehicle_type]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></div>
            <div><label class="block text-xs text-gray-500">Brand / Model</label><input name="units[__INDEX__][brand_model]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></div>
            <div><label class="block text-xs text-gray-500">Seats</label><input name="units[__INDEX__][seat_capacity]" type="number" min="1" value="4" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required></div>
            <div><label class="block text-xs text-gray-500">Luggage</label><input name="units[__INDEX__][luggage_capacity]" type="number" min="0" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></div>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-6">
            <div>
                <label class="block text-xs text-gray-500">Contract Rate</label>
                <div class="relative">
                    <input name="units[__INDEX__][contract_rate]" type="number" min="0" step="0.01" data-money-input="1" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 pr-14 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                    <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ \App\Support\Currency::current() }}</span>
                </div>
            </div>
            <div>
                <label class="block text-xs text-gray-500">Publish Rate</label>
                <div class="relative">
                    <input name="units[__INDEX__][publish_rate]" type="number" min="0" step="0.01" data-money-input="1" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 pr-14 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ \App\Support\Currency::current() }}</span>
                </div>
            </div>
            <div>
                <label class="block text-xs text-gray-500">Overtime Rate</label>
                <div class="relative">
                    <input name="units[__INDEX__][overtime_rate]" type="number" min="0" step="0.01" data-money-input="1" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 pr-14 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ \App\Support\Currency::current() }}</span>
                </div>
            </div>
            <div><label class="block text-xs text-gray-500">Currency</label><input name="units[__INDEX__][currency]" value="IDR" maxlength="3" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 uppercase text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required></div>
            <div><label class="block text-xs text-gray-500">Fuel Type</label><select name="units[__INDEX__][fuel_type]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">@foreach ($fuelTypes as $fuelType)<option value="{{ $fuelType }}">{{ ucfirst($fuelType) }}</option>@endforeach</select></div>
            <div><label class="block text-xs text-gray-500">Transmission</label><select name="units[__INDEX__][transmission]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">@foreach ($transmissions as $transmission)<option value="{{ $transmission }}">{{ ucfirst($transmission) }}</option>@endforeach</select></div>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
            <div><label class="block text-xs text-gray-500">Benefits</label><textarea name="units[__INDEX__][benefits]" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea></div>
            <div><label class="block text-xs text-gray-500">Notes</label><textarea name="units[__INDEX__][notes]" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea></div>
        </div>

        <div class="mt-3">
            <label class="block text-xs text-gray-500">Unit Cover Images (max 2)</label>
            <input type="file" name="units[__INDEX__][images][]" accept="image/*" multiple class="unit-images-input mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            <p class="mt-1 text-[11px] text-gray-500 dark:text-gray-400">Upload maksimal 2 gambar per unit.</p>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-4">
            <div><label class="block text-xs text-gray-500">Air Conditioned</label><select name="units[__INDEX__][air_conditioned]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"><option value="1">Yes</option><option value="0">No</option></select></div>
            <div><label class="block text-xs text-gray-500">With Driver</label><select name="units[__INDEX__][with_driver]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"><option value="1">Yes</option><option value="0">No</option></select></div>
            <div><label class="block text-xs text-gray-500">Unit Status</label><select name="units[__INDEX__][is_active]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"><option value="1">Active</option><option value="0">Inactive</option></select></div>
            <div class="flex items-end justify-end"><button type="button" class="remove-unit-row rounded-lg border border-rose-300 px-3 py-2 text-xs font-medium text-rose-700">Remove Unit</button></div>
        </div>
    </div>
</template>

@push('scripts')
<script>
(() => {
    const rowsContainer = document.getElementById('unit-rows');
    const addButton = document.getElementById('add-unit-row');
    const template = document.getElementById('unit-row-template');
    if (!rowsContainer || !addButton || !template) return;

    const bindRemoveButtons = () => {
        rowsContainer.querySelectorAll('.remove-unit-row').forEach((button) => {
            if (button.dataset.bound === '1') return;
            button.dataset.bound = '1';
            button.addEventListener('click', () => {
                const rows = rowsContainer.querySelectorAll('.unit-row');
                if (rows.length <= 1) {
                    alert('Minimal 1 unit detail wajib diisi.');
                    return;
                }
                button.closest('.unit-row')?.remove();
            });
        });
    };

    const bindImageInputs = () => {
        rowsContainer.querySelectorAll('.unit-images-input').forEach((input) => {
            if (input.dataset.bound === '1') return;
            input.dataset.bound = '1';
            input.addEventListener('change', () => {
                if ((input.files?.length ?? 0) > 2) {
                    alert('Maksimal 2 gambar per unit.');
                    input.value = '';
                }
            });
        });
    };

    addButton.addEventListener('click', () => {
        const nextIndex = rowsContainer.querySelectorAll('.unit-row').length;
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex));
        rowsContainer.insertAdjacentHTML('beforeend', html);
        bindRemoveButtons();
        bindImageInputs();
    });

    bindRemoveButtons();
    bindImageInputs();
})();

(() => {
    const input = document.getElementById('transport-gallery-input');
    const preview = document.getElementById('transport-gallery-preview');
    const limitNote = document.getElementById('transport-gallery-limit-note');
    if (!input || !preview) return;

    const renderNewUploads = () => {
        preview.querySelectorAll('.transport-gallery-new-item').forEach((node) => node.remove());
        if (limitNote) {
            limitNote.classList.add('hidden');
            limitNote.textContent = '';
        }

        const files = Array.from(input.files || []);
        const filesToRender = files;

        filesToRender.forEach((file) => {
            if (!String(file.type || '').startsWith('image/')) return;
            const url = URL.createObjectURL(file);
            const wrapper = document.createElement('div');
            wrapper.className = 'transport-gallery-new-item overflow-hidden rounded-lg border border-indigo-200 bg-indigo-50/30 dark:border-indigo-700/60 dark:bg-indigo-900/10';
            const media = document.createElement('div');
            media.className = 'w-full overflow-hidden bg-gray-100 dark:bg-gray-800';
            media.style.aspectRatio = '4 / 3';
            const image = document.createElement('img');
            image.src = url;
            image.alt = 'Transport gallery preview';
            image.className = 'h-full w-full object-cover';
            image.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
            media.appendChild(image);
            wrapper.appendChild(media);
            const badge = document.createElement('div');
            badge.className = 'border-t border-indigo-200 px-2 py-1 text-[11px] font-medium text-indigo-700 dark:border-indigo-700/60 dark:text-indigo-300';
            badge.textContent = 'New upload';
            wrapper.appendChild(badge);
            preview.appendChild(wrapper);
        });
    };

    input.addEventListener('change', renderNewUploads);

    preview.addEventListener('click', async (event) => {
        const button = event.target.closest('.transport-gallery-remove-btn');
        if (!button) return;
        const wrapper = button.closest('.transport-gallery-existing-item');
        const imagePath = String(wrapper?.dataset.imagePath || '');
        if (!wrapper || imagePath === '') return;

        const endpoint = String(preview.dataset.removeEndpointTemplate || '');
        const csrfToken = String(preview.dataset.csrfToken || '');
        if (endpoint === '' || csrfToken === '') {
            wrapper.remove();
            renderNewUploads();
            return;
        }

        button.disabled = true;
        button.classList.add('opacity-70');
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ image: imagePath }),
            });
            if (!response.ok) {
                throw new Error('Request failed');
            }
            wrapper.remove();
            renderNewUploads();
        } catch (_) {
            button.disabled = false;
            button.classList.remove('opacity-70');
            alert('Gagal menghapus image. Silakan coba lagi.');
        }
    });
})();
</script>
@endpush
