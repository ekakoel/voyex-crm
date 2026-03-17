@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $accommodation = $accommodation ?? null;
    $destinations = $destinations ?? collect();

    $categories = ['hotel', 'villa', 'resort', 'apartment', 'guest_house', 'hostel', 'boutique_hotel'];
    $mealPlans = ['room_only', 'breakfast', 'half_board', 'full_board', 'all_inclusive'];

    $oldRooms = old('rooms');
    if (!is_array($oldRooms)) {
        $oldRooms = isset($accommodation)
            ? $accommodation->rooms->map(fn ($room) => [
                'name' => $room->name,
                'room_type' => $room->room_type,
                'bed_type' => $room->bed_type,
                'view_type' => $room->view_type,
                'max_occupancy' => $room->max_occupancy,
                'room_size_sqm' => $room->room_size_sqm,
                'contract_rate' => $room->contract_rate,
                'publish_rate' => $room->publish_rate,
                'currency' => $room->currency,
                'meal_plan' => $room->meal_plan,
                'amenities' => $room->amenities,
                'benefits' => $room->benefits,
                'is_refundable' => $room->is_refundable ? '1' : '0',
                'quantity_available' => $room->quantity_available,
                'cancellation_policy' => $room->cancellation_policy,
                'notes' => $room->notes,
                'existing_images' => is_array($room->images) ? $room->images : [],
                'is_active' => $room->is_active ? '1' : '0',
            ])->toArray()
            : [];
    }

    if ($oldRooms === []) {
        $oldRooms = [[
            'name' => '',
            'room_type' => '',
            'bed_type' => '',
            'view_type' => '',
            'max_occupancy' => 2,
            'room_size_sqm' => '',
            'contract_rate' => '',
            'publish_rate' => '',
            'currency' => 'IDR',
            'meal_plan' => 'room_only',
            'amenities' => '',
            'benefits' => '',
            'is_refundable' => '0',
            'quantity_available' => '',
            'cancellation_policy' => '',
            'notes' => '',
            'existing_images' => [],
            'is_active' => '1',
        ]];
    }
@endphp

<div class="space-y-5" data-location-autofill data-location-resolve-url="{{ route('location.resolve-google-map') }}">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Code</label>
            <input name="code" value="{{ old('code', $accommodation->code ?? '') }}" class="mt-1 uppercase dark:border-gray-600 app-input" required>
            @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Accommodation Name</label>
            <input name="name" value="{{ old('name', $accommodation->name ?? '') }}" class="mt-1 dark:border-gray-600 app-input" required>
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Category</label>
            <select name="category" class="mt-1 dark:border-gray-600 app-input" required>
                <option value="">Select category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected(old('category', $accommodation->category ?? '') === $category)>{{ str_replace('_', ' ', ucfirst($category)) }}</option>
                @endforeach
            </select>
            @error('category') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Star Rating</label>
            <input name="star_rating" type="number" min="1" max="5" value="{{ old('star_rating', $accommodation->star_rating ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Check-in</label>
            <input name="check_in_time" type="time" value="{{ old('check_in_time', isset($accommodation->check_in_time) ? substr((string) $accommodation->check_in_time, 0, 5) : '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Check-out</label>
            <input name="check_out_time" type="time" value="{{ old('check_out_time', isset($accommodation->check_out_time) ? substr((string) $accommodation->check_out_time, 0, 5) : '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Website</label>
            <input name="website" type="url" value="{{ old('website', $accommodation->website ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-5">
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Google Maps URL</label>
            <div class="mt-1 space-y-2">
                <input name="google_maps_url" data-location-field="google_maps_url" value="{{ old('google_maps_url', $accommodation->google_maps_url ?? '') }}" class="app-input" placeholder="https://maps.google.com/...">
                <div class="flex justify-end">
                    <button type="button" data-location-autofill-trigger class="btn-outline-sm">Auto Fill</button>
                </div>
            </div>
            @error('google_maps_url') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Destination</label>
            <select name="destination_id" data-location-field="destination_id" class="mt-1 dark:border-gray-600 app-input">
                <option value="">Select destination</option>
                @foreach ($destinations as $destination)
                    <option value="{{ $destination->id }}" data-city="{{ $destination->city ?? '' }}" data-province="{{ $destination->province ?? '' }}" @selected((string) old('destination_id', $accommodation->destination_id ?? '') === (string) $destination->id)>
                        {{ $destination->province ?: $destination->name }}
                    </option>
                @endforeach
            </select>
            @error('destination_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Location</label>
            <input name="location" data-location-field="location" value="{{ old('location', $accommodation->location ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">City</label>
            <input name="city" data-location-field="city" value="{{ old('city', $accommodation->city ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Province</label>
            <input name="province" data-location-field="province" value="{{ old('province', $accommodation->province ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Address</label>
            <input name="address" data-location-field="address" value="{{ old('address', $accommodation->address ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-6">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Country</label>
            <input name="country" data-location-field="country" value="{{ old('country', $accommodation->country ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Timezone</label>
            <input name="timezone" data-location-field="timezone" value="{{ old('timezone', $accommodation->timezone ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Latitude</label>
            <input name="latitude" data-location-field="latitude" type="number" step="0.0000001" value="{{ old('latitude', $accommodation->latitude ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Longitude</label>
            <input name="longitude" data-location-field="longitude" type="number" step="0.0000001" value="{{ old('longitude', $accommodation->longitude ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Name</label>
            <input name="contact_name" value="{{ old('contact_name', $accommodation->contact_name ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Phone</label>
            <input name="contact_phone" value="{{ old('contact_phone', $accommodation->contact_phone ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
        </div>
    </div>
    <p data-location-status class="hidden text-xs"></p>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Email</label>
        <input name="contact_email" type="email" value="{{ old('contact_email', $accommodation->contact_email ?? '') }}" class="mt-1 dark:border-gray-600 app-input">
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Main Facilities</label>
            <textarea name="main_facilities" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('main_facilities', $accommodation->main_facilities ?? '') }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Description</label>
            <textarea name="description" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('description', $accommodation->description ?? '') }}</textarea>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Cancellation Policy</label>
            <textarea name="cancellation_policy" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('cancellation_policy', $accommodation->cancellation_policy ?? '') }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Notes</label>
            <textarea name="notes" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('notes', $accommodation->notes ?? '') }}</textarea>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Gallery Images</label>
        <div id="accommodation-gallery-preview"
            class="mt-2 grid grid-cols-2 gap-2 md:grid-cols-5"
            data-remove-endpoint-template="{{ isset($accommodation) ? route('accommodations.gallery-images.remove', $accommodation) : '' }}"
            data-csrf-token="{{ csrf_token() }}">
            @if (!empty($accommodation?->gallery_images))
                @foreach ($accommodation->gallery_images as $image)
                    <div class="accommodation-gallery-existing-item relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700" data-image-path="{{ $image }}">
                        <button
                            type="button"
                             class="accommodation-gallery-remove-btn absolute right-1 top-1 z-10 inline-flex h-6 w-6 items-center justify-center rounded-full bg-rose-600/95 text-xs font-bold text-white shadow hover:bg-rose-700"
                            title="Remove image"
                            aria-label="Remove image">
                            X
                        </button>
                        <div class="w-full overflow-hidden bg-gray-100 dark:bg-gray-800" style="aspect-ratio: 4 / 3;">
                            <img
                                src="{{ asset('storage/' . \App\Support\ImageThumbnailGenerator::thumbnailPathFor($image)) }}"
                                onerror="this.onerror=null;this.src='{{ asset('storage/' . $image) }}';"
                                alt="Accommodation gallery"
                                class="h-full w-full object-cover">
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
        <input id="accommodation-gallery-input" type="file" name="gallery_images[]" accept="image/*" multiple class="mt-2 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        <p id="accommodation-gallery-limit-note" class="mt-1 hidden text-xs text-amber-600"></p>
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Upload gambar tanpa batas jenis/ukuran. Saat edit, klik X untuk hapus per gambar dan upload baru akan ditambahkan ke gallery. Semua gambar diproses crop rasio 3:2 dan dibuat thumbnail.</p>
        @error('gallery_images') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('gallery_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('removed_gallery_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
        <div class="mb-3 flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Room Details (Contract)</p>
            <button type="button" id="add-room-row"  class="rounded-lg border border-indigo-300 px-3 py-1 text-xs font-medium text-indigo-700">Add Room</button>
        </div>

        <div id="room-rows" class="space-y-3">
            @foreach ($oldRooms as $index => $room)
                @php
                    $existingRoomImages = array_values(array_filter(
                        is_array($room['existing_images'] ?? null) ? $room['existing_images'] : [],
                        fn ($path) => is_string($path) && trim($path) !== ''
                    ));
                @endphp
                <div class="room-row rounded-lg border border-gray-200 p-3 dark:border-gray-700" data-room-index="{{ $index }}">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
                        <div class="md:col-span-2">
                            <label class="block text-xs text-gray-500">Room Name</label>
                            <input name="rooms[{{ $index }}][name]" value="{{ $room['name'] ?? '' }}" class="mt-1 dark:border-gray-600 app-input" required>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Room Type</label>
                            <input name="rooms[{{ $index }}][room_type]" value="{{ $room['room_type'] ?? '' }}" class="mt-1 dark:border-gray-600 app-input">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Bed Type</label>
                            <input name="rooms[{{ $index }}][bed_type]" value="{{ $room['bed_type'] ?? '' }}" class="mt-1 dark:border-gray-600 app-input">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">View</label>
                            <input name="rooms[{{ $index }}][view_type]" value="{{ $room['view_type'] ?? '' }}" class="mt-1 dark:border-gray-600 app-input">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Max Pax</label>
                            <input name="rooms[{{ $index }}][max_occupancy]" type="number" min="1" value="{{ $room['max_occupancy'] ?? 2 }}" class="mt-1 dark:border-gray-600 app-input" required>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-6">
                        <div>
                            <label class="block text-xs text-gray-500">Room Size (sqm)</label>
                            <input name="rooms[{{ $index }}][room_size_sqm]" type="number" min="1" step="0.01" value="{{ $room['room_size_sqm'] ?? '' }}" class="mt-1 dark:border-gray-600 app-input">
                        </div>
                        <x-money-input
                            label="Contract Rate"
                            label-class="block text-xs text-gray-500"
                            name="rooms[{{ $index }}][contract_rate]"
                            :value="$room['contract_rate'] ?? ''"
                            min="0"
                            step="0.01"
                            compact
                            required
                        />
                        <x-money-input
                            label="Publish Rate"
                            label-class="block text-xs text-gray-500"
                            name="rooms[{{ $index }}][publish_rate]"
                            :value="$room['publish_rate'] ?? ''"
                            min="0"
                            step="0.01"
                            compact
                        />
                        <div>
                            <label class="block text-xs text-gray-500">Currency</label>
                            <input name="rooms[{{ $index }}][currency]" value="{{ $room['currency'] ?? 'IDR' }}" maxlength="3" class="mt-1 uppercase dark:border-gray-600 app-input" required>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Meal Plan</label>
                            <select name="rooms[{{ $index }}][meal_plan]" class="mt-1 dark:border-gray-600 app-input">
                                @foreach ($mealPlans as $plan)
                                    <option value="{{ $plan }}" @selected(($room['meal_plan'] ?? 'room_only') === $plan)>{{ str_replace('_', ' ', ucfirst($plan)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Qty Available</label>
                            <input name="rooms[{{ $index }}][quantity_available]" type="number" min="0" value="{{ $room['quantity_available'] ?? '' }}" class="mt-1 dark:border-gray-600 app-input">
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="block text-xs text-gray-500">Amenities</label>
                            <textarea name="rooms[{{ $index }}][amenities]" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ $room['amenities'] ?? '' }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Benefits</label>
                            <textarea name="rooms[{{ $index }}][benefits]" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ $room['benefits'] ?? '' }}</textarea>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div>
                            <label class="block text-xs text-gray-500">Refundable</label>
                            <select name="rooms[{{ $index }}][is_refundable]" class="mt-1 dark:border-gray-600 app-input">
                                <option value="0" @selected(($room['is_refundable'] ?? '0') === '0')>No</option>
                                <option value="1" @selected(($room['is_refundable'] ?? '0') === '1')>Yes</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Room Status</label>
                            <select name="rooms[{{ $index }}][is_active]" class="mt-1 dark:border-gray-600 app-input">
                                <option value="1" @selected(($room['is_active'] ?? '1') === '1')>Active</option>
                                <option value="0" @selected(($room['is_active'] ?? '1') === '0')>Inactive</option>
                            </select>
                        </div>
                        <div class="flex items-end justify-end">
                            <button type="button"  class="remove-room-row rounded-lg border border-rose-300 px-3 py-2 text-xs font-medium text-rose-700">Remove Room</button>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <label class="block text-xs text-gray-500">Cancellation Policy</label>
                            <textarea name="rooms[{{ $index }}][cancellation_policy]" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ $room['cancellation_policy'] ?? '' }}</textarea>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Notes</label>
                            <textarea name="rooms[{{ $index }}][notes]" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ $room['notes'] ?? '' }}</textarea>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="block text-xs text-gray-500">Room Images (max 3)</label>
                        <div class="room-existing-images mt-2 grid grid-cols-3 gap-2 md:grid-cols-6">
                            @foreach ($existingRoomImages as $image)
                                <div class="room-existing-image-item relative overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700" data-image-path="{{ $image }}">
                                    <input type="hidden" name="rooms[{{ $index }}][existing_images][]" value="{{ $image }}" class="room-existing-image-inputapp-inputapp-input">
                                    <button
                                        type="button"
                                         class="room-existing-image-remove absolute right-1 top-1 z-10 inline-flex h-6 w-6 items-center justify-center rounded-full bg-rose-600/95 text-xs font-bold text-white shadow hover:bg-rose-700"
                                        title="Remove image"
                                        aria-label="Remove image">
                                        X
                                    </button>
                                    <div class="w-full overflow-hidden bg-gray-100 dark:bg-gray-800" style="aspect-ratio: 4 / 3;">
                                        <img
                                            src="{{ asset('storage/' . \App\Support\ImageThumbnailGenerator::thumbnailPathFor($image)) }}"
                                            onerror="this.onerror=null;this.src='{{ asset('storage/' . $image) }}';"
                                            alt="Room image"
                                            class="h-full w-full object-cover">
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="room-new-images-preview mt-2 grid grid-cols-3 gap-2 md:grid-cols-6"></div>
                        <input type="file" name="rooms[{{ $index }}][images][]" accept="image/*" multiple class="room-images-input mt-2 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        <p class="room-images-note mt-1 text-[11px] text-gray-500 dark:text-gray-400">Max 3 gambar per room. Gambar akan diproses crop rasio 3:2 dan dibuat thumbnail.</p>
                        @error("rooms.$index.images") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        @error("rooms.$index.images.*") <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                </div>
            @endforeach
        </div>

        @error('rooms') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('rooms.*.name') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('rooms.*.contract_rate') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('rooms.*.images') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('rooms.*.images.*') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('is_active', $accommodation->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-200">Active</span>
    </div>

    <div class="flex items-center gap-2">
        <button  class="btn-primary">{{ $buttonLabel }}</button>
        <a href="{{ route('accommodations.index') }}"  class="btn-secondary">Cancel</a>
    </div>
</div>

<template id="room-row-template">
    <div class="room-row rounded-lg border border-gray-200 p-3 dark:border-gray-700" data-room-index="__INDEX__">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-500">Room Name</label>
                <input name="rooms[__INDEX__][name]" class="mt-1 dark:border-gray-600 app-input" required>
            </div>
            <div><label class="block text-gray-500 app-input">Room Type</label><input name="rooms[__INDEX__][room_type]" class="block text-gray-500 app-input"></div>
            <div><label class="block text-gray-500 app-input">Bed Type</label><input name="rooms[__INDEX__][bed_type]" class="block text-gray-500 app-input"></div>
            <div><label class="block text-gray-500 app-input">View</label><input name="rooms[__INDEX__][view_type]" class="block text-gray-500 app-input"></div>
            <div><label class="block text-gray-500 app-input">Max Pax</label><input name="rooms[__INDEX__][max_occupancy]" type="number" min="1" value="2" class="block text-gray-500 app-input" required></div>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-6">
            <div><label class="block text-gray-500 app-input">Room Size (sqm)</label><input name="rooms[__INDEX__][room_size_sqm]" type="number" min="1" step="0.01" class="block text-gray-500 app-input"></div>
            <div>
                <label class="block text-xs text-gray-500">Contract Rate</label>
                <div class="relative">
                    <input name="rooms[__INDEX__][contract_rate]" type="number" min="0" step="0.01" data-money-input="1" class="mt-1 pr-14 dark:border-gray-600 app-input" required>
                    <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ \App\Support\Currency::current() }}</span>
                </div>
            </div>
            <div>
                <label class="block text-xs text-gray-500">Publish Rate</label>
                <div class="relative">
                    <input name="rooms[__INDEX__][publish_rate]" type="number" min="0" step="0.01" data-money-input="1" class="mt-1 pr-14 dark:border-gray-600 app-input">
                    <span class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 rounded-md border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] font-semibold text-gray-600 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">{{ \App\Support\Currency::current() }}</span>
                </div>
            </div>
            <div><label class="block text-gray-500 app-input">Currency</label><input name="rooms[__INDEX__][currency]" value="IDR" maxlength="3" class="block text-gray-500 app-input" required></div>
            <div><label class="block text-gray-500 app-input">Meal Plan</label><select name="rooms[__INDEX__][meal_plan]" class="block text-gray-500 app-input">@foreach ($mealPlans as $plan)<option value="{{ $plan }}">{{ str_replace('_', ' ', ucfirst($plan)) }}</option>@endforeach</select></div>
            <div><label class="block text-gray-500 app-input">Qty Available</label><input name="rooms[__INDEX__][quantity_available]" type="number" min="0" class="block text-gray-500 app-input"></div>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
            <div><label class="block text-xs text-gray-500">Amenities</label><textarea name="rooms[__INDEX__][amenities]" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea></div>
            <div><label class="block text-xs text-gray-500">Benefits</label><textarea name="rooms[__INDEX__][benefits]" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea></div>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
            <div><label class="block text-gray-500 app-input">Refundable</label><select name="rooms[__INDEX__][is_refundable]" class="block text-gray-500 app-input"><option value="0">No</option><option value="1">Yes</option></select></div>
            <div><label class="block text-gray-500 app-input">Room Status</label><select name="rooms[__INDEX__][is_active]" class="block text-gray-500 app-input"><option value="1">Active</option><option value="0">Inactive</option></select></div>
            <div class="flex items-end justify-end"><button type="button"  class="remove-room-row rounded-lg border border-rose-300 px-3 py-2 text-xs font-medium text-rose-700">Remove Room</button></div>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
            <div><label class="block text-xs text-gray-500">Cancellation Policy</label><textarea name="rooms[__INDEX__][cancellation_policy]" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea></div>
            <div><label class="block text-xs text-gray-500">Notes</label><textarea name="rooms[__INDEX__][notes]" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea></div>
        </div>

        <div class="mt-3">
            <label class="block text-xs text-gray-500">Room Images (max 3)</label>
            <div class="room-existing-images mt-2 grid grid-cols-3 gap-2 md:grid-cols-6"></div>
            <div class="room-new-images-preview mt-2 grid grid-cols-3 gap-2 md:grid-cols-6"></div>
            <input type="file" name="rooms[__INDEX__][images][]" accept="image/*" multiple class="room-images-input mt-2 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            <p class="room-images-note mt-1 text-[11px] text-gray-500 dark:text-gray-400">Max 3 gambar per room. Gambar akan diproses crop rasio 3:2 dan dibuat thumbnail.</p>
        </div>
    </div>
</template>

@push('scripts')
<script>
(() => {
    const rowsContainer = document.getElementById('room-rows');
    const addButton = document.getElementById('add-room-row');
    const template = document.getElementById('room-row-template');
    if (!rowsContainer || !addButton || !template) return;

    const bindRoomImageUpload = (row) => {
        if (!row || row.dataset.roomImageBound === '1') return;
        row.dataset.roomImageBound = '1';

        const input = row.querySelector('.room-images-input');
        const preview = row.querySelector('.room-new-images-preview');
        const note = row.querySelector('.room-images-note');
        if (!input || !preview || !note) return;

        const renderNewUploads = () => {
            preview.querySelectorAll('.room-new-image-item').forEach((node) => node.remove());
            const existingCount = row.querySelectorAll('.room-existing-image-input').length;
            const files = Array.from(input.files || []).filter((file) => String(file.type || '').startsWith('image/'));
            const availableSlots = Math.max(0, 3 - existingCount);
            const filesToRender = files.slice(0, availableSlots);
            const dataTransfer = new DataTransfer();
            filesToRender.forEach((file) => dataTransfer.items.add(file));
            input.files = dataTransfer.files;

            filesToRender.forEach((file) => {
                const url = URL.createObjectURL(file);
                const wrapper = document.createElement('div');
                wrapper.className = 'room-new-image-item overflow-hidden rounded-lg border border-indigo-200 bg-indigo-50/30 dark:border-indigo-700/60 dark:bg-indigo-900/10';
                const media = document.createElement('div');
                media.className = 'w-full overflow-hidden bg-gray-100 dark:bg-gray-800';
                media.style.aspectRatio = '4 / 3';
                const image = document.createElement('img');
                image.src = url;
                image.alt = 'Room image preview';
                image.className = 'h-full w-full object-cover';
                image.addEventListener('load', () => URL.revokeObjectURL(url), { once: true });
                media.appendChild(image);
                wrapper.appendChild(media);
                preview.appendChild(wrapper);
            });

            const totalSelected = existingCount + filesToRender.length;
            note.textContent = totalSelected >= 3
                ? 'Maksimum 3 gambar tercapai untuk room ini.'
                : 'Max 3 gambar per room. Gambar akan diproses crop rasio 3:2 dan dibuat thumbnail.';
        };

        input.addEventListener('change', renderNewUploads);
        row.addEventListener('click', (event) => {
            const removeButton = event.target.closest('.room-existing-image-remove');
            if (!removeButton) return;
            const item = removeButton.closest('.room-existing-image-item');
            if (!item) return;
            const hiddenInput = item.querySelector('.room-existing-image-input');
            if (hiddenInput) hiddenInput.remove();
            item.remove();
            renderNewUploads();
        });

        renderNewUploads();
    };

    const bindRemoveButtons = () => {
        rowsContainer.querySelectorAll('.remove-room-row').forEach((button) => {
            if (button.dataset.bound === '1') return;
            button.dataset.bound = '1';
            button.addEventListener('click', () => {
                const rows = rowsContainer.querySelectorAll('.room-row');
                if (rows.length <= 1) {
                    alert('Minimal 1 room detail wajib diisi.');
                    return;
                }
                button.closest('.room-row')?.remove();
            });
        });
    };

    const bindRows = () => {
        bindRemoveButtons();
        rowsContainer.querySelectorAll('.room-row').forEach((row) => bindRoomImageUpload(row));
    };

    addButton.addEventListener('click', () => {
        const nextIndex = rowsContainer.querySelectorAll('.room-row').length;
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex));
        rowsContainer.insertAdjacentHTML('beforeend', html);
        bindRows();
    });

    bindRows();
})();

(() => {
    const input = document.getElementById('accommodation-gallery-input');
    const preview = document.getElementById('accommodation-gallery-preview');
    const limitNote = document.getElementById('accommodation-gallery-limit-note');
    if (!input || !preview) return;

    const renderNewUploads = () => {
        preview.querySelectorAll('.accommodation-gallery-new-item').forEach((node) => node.remove());
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
            wrapper.className = 'accommodation-gallery-new-item overflow-hidden rounded-lg border border-indigo-200 bg-indigo-50/30 dark:border-indigo-700/60 dark:bg-indigo-900/10';
            const media = document.createElement('div');
            media.className = 'w-full overflow-hidden bg-gray-100 dark:bg-gray-800';
            media.style.aspectRatio = '4 / 3';
            const image = document.createElement('img');
            image.src = url;
            image.alt = 'Accommodation gallery preview';
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
        const button = event.target.closest('.accommodation-gallery-remove-btn');
        if (!button) return;
        const wrapper = button.closest('.accommodation-gallery-existing-item');
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



