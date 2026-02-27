@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $transport = $transport ?? null;

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

<div class="space-y-5">
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

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Location</label>
            <input name="location" value="{{ old('location', $transport->location ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">City</label>
            <input name="city" value="{{ old('city', $transport->city ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Province</label>
            <input name="province" value="{{ old('province', $transport->province ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Phone</label>
            <input name="contact_phone" value="{{ old('contact_phone', $transport->contact_phone ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
    </div>

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
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Gallery Images (1-5)</label>
        <input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Upload 1 sampai 5 gambar. Saat edit, upload ulang akan mengganti gallery lama.</p>
        @error('gallery_images') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('gallery_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        @if (!empty($transport?->gallery_images))
            <div class="mt-2 grid grid-cols-2 gap-2 md:grid-cols-5">
                @foreach ($transport->gallery_images as $image)
                    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                        <img
                            src="{{ asset('storage/' . \App\Support\ImageThumbnailGenerator::thumbnailPathFor($image)) }}"
                            onerror="this.onerror=null;this.src='{{ asset('storage/' . $image) }}';"
                            alt="Transport gallery"
                            class="h-20 w-full object-cover">
                    </div>
                @endforeach
            </div>
        @endif
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
                        <div>
                            <label class="block text-xs text-gray-500">Contract Rate</label>
                            <input name="units[{{ $index }}][contract_rate]" type="number" min="0" step="0.01" value="{{ $unit['contract_rate'] ?? '' }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Publish Rate</label>
                            <input name="units[{{ $index }}][publish_rate]" type="number" min="0" step="0.01" value="{{ $unit['publish_rate'] ?? '' }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Overtime Rate</label>
                            <input name="units[{{ $index }}][overtime_rate]" type="number" min="0" step="0.01" value="{{ $unit['overtime_rate'] ?? '' }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        </div>
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
                        <input type="file" name="units[{{ $index }}][images][]" accept="image/jpeg,image/png,image/webp" multiple class="unit-images-input mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
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
            <div><label class="block text-xs text-gray-500">Contract Rate</label><input name="units[__INDEX__][contract_rate]" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required></div>
            <div><label class="block text-xs text-gray-500">Publish Rate</label><input name="units[__INDEX__][publish_rate]" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></div>
            <div><label class="block text-xs text-gray-500">Overtime Rate</label><input name="units[__INDEX__][overtime_rate]" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></div>
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
            <input type="file" name="units[__INDEX__][images][]" accept="image/jpeg,image/png,image/webp" multiple class="unit-images-input mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
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
</script>
@endpush
