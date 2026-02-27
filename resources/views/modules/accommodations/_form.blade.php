@php
    $buttonLabel = $buttonLabel ?? 'Save';
    $accommodation = $accommodation ?? null;

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
            'is_active' => '1',
        ]];
    }
@endphp

<div class="space-y-5">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Code</label>
            <input name="code" value="{{ old('code', $accommodation->code ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
            @error('code') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div class="md:col-span-2">
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Accommodation Name</label>
            <input name="name" value="{{ old('name', $accommodation->name ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Category</label>
            <select name="category" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
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
            <input name="star_rating" type="number" min="1" max="5" value="{{ old('star_rating', $accommodation->star_rating ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Check-in</label>
            <input name="check_in_time" type="time" value="{{ old('check_in_time', isset($accommodation->check_in_time) ? substr((string) $accommodation->check_in_time, 0, 5) : '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Check-out</label>
            <input name="check_out_time" type="time" value="{{ old('check_out_time', isset($accommodation->check_out_time) ? substr((string) $accommodation->check_out_time, 0, 5) : '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Website</label>
            <input name="website" type="url" value="{{ old('website', $accommodation->website ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Location</label>
            <input name="location" value="{{ old('location', $accommodation->location ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">City</label>
            <input name="city" value="{{ old('city', $accommodation->city ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Province</label>
            <input name="province" value="{{ old('province', $accommodation->province ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Address</label>
            <input name="address" value="{{ old('address', $accommodation->address ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Latitude</label>
            <input name="latitude" type="number" step="0.0000001" value="{{ old('latitude', $accommodation->latitude ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Longitude</label>
            <input name="longitude" type="number" step="0.0000001" value="{{ old('longitude', $accommodation->longitude ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Name</label>
            <input name="contact_name" value="{{ old('contact_name', $accommodation->contact_name ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Phone</label>
            <input name="contact_phone" value="{{ old('contact_phone', $accommodation->contact_phone ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Email</label>
        <input name="contact_email" type="email" value="{{ old('contact_email', $accommodation->contact_email ?? '') }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
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
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Gallery Images (1-5)</label>
        <input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Upload 1 sampai 5 gambar. Saat edit, upload ulang akan mengganti gallery lama.</p>
        @error('gallery_images') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('gallery_images.*') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        @if (!empty($accommodation?->gallery_images))
            <div class="mt-2 grid grid-cols-2 gap-2 md:grid-cols-5">
                @foreach ($accommodation->gallery_images as $image)
                    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                        <img
                            src="{{ asset('storage/' . \App\Support\ImageThumbnailGenerator::thumbnailPathFor($image)) }}"
                            onerror="this.onerror=null;this.src='{{ asset('storage/' . $image) }}';"
                            alt="Accommodation gallery"
                            class="h-20 w-full object-cover">
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
        <div class="mb-3 flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">Room Details (Contract)</p>
            <button type="button" id="add-room-row" class="rounded-lg border border-indigo-300 px-3 py-1 text-xs font-medium text-indigo-700">Add Room</button>
        </div>

        <div id="room-rows" class="space-y-3">
            @foreach ($oldRooms as $index => $room)
                <div class="room-row rounded-lg border border-gray-200 p-3 dark:border-gray-700" data-room-index="{{ $index }}">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
                        <div class="md:col-span-2">
                            <label class="block text-xs text-gray-500">Room Name</label>
                            <input name="rooms[{{ $index }}][name]" value="{{ $room['name'] ?? '' }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Room Type</label>
                            <input name="rooms[{{ $index }}][room_type]" value="{{ $room['room_type'] ?? '' }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Bed Type</label>
                            <input name="rooms[{{ $index }}][bed_type]" value="{{ $room['bed_type'] ?? '' }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">View</label>
                            <input name="rooms[{{ $index }}][view_type]" value="{{ $room['view_type'] ?? '' }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Max Pax</label>
                            <input name="rooms[{{ $index }}][max_occupancy]" type="number" min="1" value="{{ $room['max_occupancy'] ?? 2 }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-6">
                        <div>
                            <label class="block text-xs text-gray-500">Room Size (sqm)</label>
                            <input name="rooms[{{ $index }}][room_size_sqm]" type="number" min="1" step="0.01" value="{{ $room['room_size_sqm'] ?? '' }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Contract Rate</label>
                            <input name="rooms[{{ $index }}][contract_rate]" type="number" min="0" step="0.01" value="{{ $room['contract_rate'] ?? '' }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Publish Rate</label>
                            <input name="rooms[{{ $index }}][publish_rate]" type="number" min="0" step="0.01" value="{{ $room['publish_rate'] ?? '' }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Currency</label>
                            <input name="rooms[{{ $index }}][currency]" value="{{ $room['currency'] ?? 'IDR' }}" maxlength="3" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 uppercase text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Meal Plan</label>
                            <select name="rooms[{{ $index }}][meal_plan]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                @foreach ($mealPlans as $plan)
                                    <option value="{{ $plan }}" @selected(($room['meal_plan'] ?? 'room_only') === $plan)>{{ str_replace('_', ' ', ucfirst($plan)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Qty Available</label>
                            <input name="rooms[{{ $index }}][quantity_available]" type="number" min="0" value="{{ $room['quantity_available'] ?? '' }}" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
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
                            <select name="rooms[{{ $index }}][is_refundable]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                <option value="0" @selected(($room['is_refundable'] ?? '0') === '0')>No</option>
                                <option value="1" @selected(($room['is_refundable'] ?? '0') === '1')>Yes</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500">Room Status</label>
                            <select name="rooms[{{ $index }}][is_active]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                                <option value="1" @selected(($room['is_active'] ?? '1') === '1')>Active</option>
                                <option value="0" @selected(($room['is_active'] ?? '1') === '0')>Inactive</option>
                            </select>
                        </div>
                        <div class="flex items-end justify-end">
                            <button type="button" class="remove-room-row rounded-lg border border-rose-300 px-3 py-2 text-xs font-medium text-rose-700">Remove Room</button>
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
                </div>
            @endforeach
        </div>

        @error('rooms') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('rooms.*.name') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
        @error('rooms.*.contract_rate') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-2">
        <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('is_active', $accommodation->is_active ?? true))>
        <span class="text-sm text-gray-700 dark:text-gray-200">Active</span>
    </div>

    <div class="flex items-center gap-2">
        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">{{ $buttonLabel }}</button>
        <a href="{{ route('accommodations.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Cancel</a>
    </div>
</div>

<template id="room-row-template">
    <div class="room-row rounded-lg border border-gray-200 p-3 dark:border-gray-700" data-room-index="__INDEX__">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
            <div class="md:col-span-2">
                <label class="block text-xs text-gray-500">Room Name</label>
                <input name="rooms[__INDEX__][name]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
            </div>
            <div><label class="block text-xs text-gray-500">Room Type</label><input name="rooms[__INDEX__][room_type]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></div>
            <div><label class="block text-xs text-gray-500">Bed Type</label><input name="rooms[__INDEX__][bed_type]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></div>
            <div><label class="block text-xs text-gray-500">View</label><input name="rooms[__INDEX__][view_type]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></div>
            <div><label class="block text-xs text-gray-500">Max Pax</label><input name="rooms[__INDEX__][max_occupancy]" type="number" min="1" value="2" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required></div>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-6">
            <div><label class="block text-xs text-gray-500">Room Size (sqm)</label><input name="rooms[__INDEX__][room_size_sqm]" type="number" min="1" step="0.01" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></div>
            <div><label class="block text-xs text-gray-500">Contract Rate</label><input name="rooms[__INDEX__][contract_rate]" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required></div>
            <div><label class="block text-xs text-gray-500">Publish Rate</label><input name="rooms[__INDEX__][publish_rate]" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></div>
            <div><label class="block text-xs text-gray-500">Currency</label><input name="rooms[__INDEX__][currency]" value="IDR" maxlength="3" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 uppercase text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required></div>
            <div><label class="block text-xs text-gray-500">Meal Plan</label><select name="rooms[__INDEX__][meal_plan]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">@foreach ($mealPlans as $plan)<option value="{{ $plan }}">{{ str_replace('_', ' ', ucfirst($plan)) }}</option>@endforeach</select></div>
            <div><label class="block text-xs text-gray-500">Qty Available</label><input name="rooms[__INDEX__][quantity_available]" type="number" min="0" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></div>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
            <div><label class="block text-xs text-gray-500">Amenities</label><textarea name="rooms[__INDEX__][amenities]" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea></div>
            <div><label class="block text-xs text-gray-500">Benefits</label><textarea name="rooms[__INDEX__][benefits]" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea></div>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
            <div><label class="block text-xs text-gray-500">Refundable</label><select name="rooms[__INDEX__][is_refundable]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"><option value="0">No</option><option value="1">Yes</option></select></div>
            <div><label class="block text-xs text-gray-500">Room Status</label><select name="rooms[__INDEX__][is_active]" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"><option value="1">Active</option><option value="0">Inactive</option></select></div>
            <div class="flex items-end justify-end"><button type="button" class="remove-room-row rounded-lg border border-rose-300 px-3 py-2 text-xs font-medium text-rose-700">Remove Room</button></div>
        </div>

        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
            <div><label class="block text-xs text-gray-500">Cancellation Policy</label><textarea name="rooms[__INDEX__][cancellation_policy]" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea></div>
            <div><label class="block text-xs text-gray-500">Notes</label><textarea name="rooms[__INDEX__][notes]" rows="2" class="mt-1 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"></textarea></div>
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

    addButton.addEventListener('click', () => {
        const nextIndex = rowsContainer.querySelectorAll('.room-row').length;
        const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex));
        rowsContainer.insertAdjacentHTML('beforeend', html);
        bindRemoveButtons();
    });

    bindRemoveButtons();
})();
</script>
@endpush

