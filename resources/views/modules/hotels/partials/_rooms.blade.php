@php
    $hotel = $hotel ?? null;
    $roomViews = $roomViews ?? collect();
    $buttonLabel = $buttonLabel ?? 'Save';

    $roomRows = old('rooms');
    if ($roomRows === null) {
        $roomRows = $hotel?->rooms?->map(fn ($room) => [
            'room_view_id' => $room->room_view_id,
            'cover' => $room->cover,
            'rooms' => $room->rooms,
            'capacity_adult' => $room->capacity_adult,
            'capacity_child' => $room->capacity_child,
            'view' => $room->view,
            'beds' => $room->beds,
            'size' => $room->size,
            'amenities' => $room->amenities,
            'include' => $room->include,
            'additional_info' => $room->additional_info,
        ])->toArray() ?? [];
    }
    if (empty($roomRows)) {
        $roomRows = [['rooms' => '']];
    }
@endphp

<div class="space-y-6 hotel-form">
    <div class="app-card p-5 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Hotel Rooms</h3>
            <button type="button" class="btn-ghost-sm" data-add-row="room">Add Room</button>
        </div>
        <div id="room-rows" class="space-y-4">
            @foreach ($roomRows as $index => $row)
                @php
                    $roomLabel = trim((string) ($row['rooms'] ?? ''));
                    $roomTitle = $roomLabel !== '' ? $roomLabel : 'Room ' . ($index + 1);
                @endphp
                <div class="app-card p-4" data-room-card>
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">{{ $index + 1 }}</span>
                            <span class="text-sm font-semibold text-gray-800 dark:text-gray-100" data-room-title>{{ $roomTitle }}</span>
                        </div>
                        <button type="button" class="btn-ghost-sm" data-remove-room>Remove</button>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-12">
                        <div class="md:col-span-4">
                            @php
                                $coverValue = (string) ($row['existing_cover'] ?? $row['cover'] ?? '');
                                $coverStoredPath = trim(str_replace('\\', '/', $coverValue), '/');
                                if (\Illuminate\Support\Str::startsWith($coverStoredPath, 'storage/')) {
                                    $coverStoredPath = \Illuminate\Support\Str::after($coverStoredPath, 'storage/');
                                }
                                if (
                                    $coverStoredPath !== '' &&
                                    ! \Illuminate\Support\Str::startsWith($coverStoredPath, ['http://', 'https://', 'hotels/rooms/']) &&
                                    ! \Illuminate\Support\Str::contains($coverStoredPath, '/')
                                ) {
                                    $coverStoredPath = 'hotels/rooms/' . $coverStoredPath;
                                }
                                $coverIsExternal = \Illuminate\Support\Str::startsWith($coverStoredPath, ['http://', 'https://']);
                                $coverThumb = $coverStoredPath !== '' && ! $coverIsExternal
                                    ? \App\Support\ImageThumbnailGenerator::resolvePublicUrl($coverStoredPath)
                                    : $coverStoredPath;
                                $coverFull = $coverStoredPath !== '' && ! $coverIsExternal
                                    ? \App\Support\ImageThumbnailGenerator::resolveOriginalPublicUrl($coverStoredPath)
                                    : $coverStoredPath;
                            @endphp
                            <label class="block text-xs text-gray-500">Cover (Upload Image)</label>
                            <div class="room-cover-preview image-preview mt-2 flex w-full items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/40">
                            <div class="image-preview-placeholder">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 7h3l2-2h6l2 2h3a1 1 0 0 1 1 1v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a1 1 0 0 1 1-1z"></path>
                                    <circle cx="12" cy="13" r="4"></circle>
                                </svg>
                                <span>Select image to preview</span>
                            </div>
                                @if ($coverStoredPath !== '')
                                    <img src="{{ $coverThumb }}" onload="this.classList.add('image-loaded');var p=this.closest('.image-preview');if(p){p.classList.add('has-image');}" onerror="if(this.dataset.fallbackApplied){var p=this.closest('.image-preview');if(p){p.classList.remove('has-image');}this.remove();}else{this.dataset.fallbackApplied='1';this.src='{{ $coverFull }}';}" alt="Room cover preview" class="h-full w-full object-cover">
                                @endif
                            </div>
                            <input type="file" name="rooms[{{ $index }}][cover]" accept="image/*" class="room-cover-input mt-2 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                            <input type="hidden" name="rooms[{{ $index }}][existing_cover]" value="{{ $coverStoredPath }}">
                        </div>
                        <div class="md:col-span-8">
                            <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-12">
                                <div class="md:col-span-6">
                                    <label class="block text-xs text-gray-500">Room Name</label>
                                    <input name="rooms[{{ $index }}][rooms]" value="{{ $row['rooms'] ?? '' }}" class="mt-1 app-input" required data-room-name>
                                </div>
                                <div class="md:col-span-6">
                                    <label class="block text-xs text-gray-500">Room View</label>
                                    <select name="rooms[{{ $index }}][room_view_id]" class="mt-1 app-input">
                                        <option value="">-</option>
                                        @foreach ($roomViews as $view)
                                            <option value="{{ $view->id }}" @selected((string) ($row['room_view_id'] ?? '') === (string) $view->id)>{{ $view->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="md:col-span-6">
                                    <label class="block text-xs text-gray-500">Capacity Adult</label>
                                    <input name="rooms[{{ $index }}][capacity_adult]" value="{{ $row['capacity_adult'] ?? '' }}" class="mt-1 app-input" type="number" min="0">
                                </div>
                                <div class="md:col-span-6">
                                    <label class="block text-xs text-gray-500">Capacity Child</label>
                                    <input name="rooms[{{ $index }}][capacity_child]" value="{{ $row['capacity_child'] ?? '' }}" class="mt-1 app-input" type="number" min="0">
                                </div>
                                <div class="md:col-span-6">
                                    <label class="block text-xs text-gray-500">Beds</label>
                                    <input name="rooms[{{ $index }}][beds]" value="{{ $row['beds'] ?? '' }}" class="mt-1 app-input">
                                </div>
                                <div class="md:col-span-6">
                                    <label class="block text-xs text-gray-500">Size</label>
                                    <input name="rooms[{{ $index }}][size]" value="{{ $row['size'] ?? '' }}" class="mt-1 app-input">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-12">
                        <div class="md:col-span-6">
                            <label class="block text-xs text-gray-500">Amenities</label>
                            <textarea name="rooms[{{ $index }}][amenities]" rows="2" class="mt-1 app-input">{{ $row['amenities'] ?? '' }}</textarea>
                        </div>
                        <div class="md:col-span-6">
                            <label class="block text-xs text-gray-500">Include</label>
                            <textarea name="rooms[{{ $index }}][include]" rows="2" class="mt-1 app-input">{{ $row['include'] ?? '' }}</textarea>
                        </div>
                        <div class="md:col-span-6">
                            <label class="block text-xs text-gray-500">Additional Info</label>
                            <textarea name="rooms[{{ $index }}][additional_info]" rows="2" class="mt-1 app-input">{{ $row['additional_info'] ?? '' }}</textarea>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <template id="room-row-template">
            <div class="app-card p-4" data-room-card>
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">__NUMBER__</span>
                        <span class="text-sm font-semibold text-gray-800 dark:text-gray-100" data-room-title>Room __NUMBER__</span>
                    </div>
                    <button type="button" class="btn-ghost-sm" data-remove-room>Remove</button>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-12">
                    <div class="md:col-span-4">
                        <label class="block text-xs text-gray-500">Cover (Upload Image)</label>
                        <div class="room-cover-preview image-preview mt-2 flex w-full items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/40">
                            <div class="image-preview-placeholder">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 7h3l2-2h6l2 2h3a1 1 0 0 1 1 1v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a1 1 0 0 1 1-1z"></path>
                                    <circle cx="12" cy="13" r="4"></circle>
                                </svg>
                                <span>Select image to preview</span>
                            </div>
                        </div>
                        <input type="file" name="rooms[__INDEX__][cover]" accept="image/*" class="room-cover-input mt-2 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        <input type="hidden" name="rooms[__INDEX__][existing_cover]" value="">
                        <p class="mt-1 text-[11px] text-gray-500">Image will be cropped to 3:2 and a thumbnail is generated.</p>
                    </div>
                    <div class="md:col-span-8">
                        <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-12">
                            <div class="md:col-span-6">
                                <label class="block text-xs text-gray-500">Room Name</label>
                                <input name="rooms[__INDEX__][rooms]" class="mt-1 app-input" required data-room-name>
                            </div>
                            <div class="md:col-span-6">
                                <label class="block text-xs text-gray-500">Room View</label>
                                <select name="rooms[__INDEX__][room_view_id]" class="mt-1 app-input">
                                    <option value="">-</option>
                                    @foreach ($roomViews as $view)
                                        <option value="{{ $view->id }}">{{ $view->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="md:col-span-6">
                                <label class="block text-xs text-gray-500">Capacity Adult</label>
                                <input name="rooms[__INDEX__][capacity_adult]" class="mt-1 app-input" type="number" min="0">
                            </div>
                            <div class="md:col-span-6">
                                <label class="block text-xs text-gray-500">Capacity Child</label>
                                <input name="rooms[__INDEX__][capacity_child]" class="mt-1 app-input" type="number" min="0">
                            </div>
                            <div class="md:col-span-6">
                                <label class="block text-xs text-gray-500">Beds</label>
                                <input name="rooms[__INDEX__][beds]" class="mt-1 app-input">
                            </div>
                            <div class="md:col-span-6">
                                <label class="block text-xs text-gray-500">Size</label>
                                <input name="rooms[__INDEX__][size]" class="mt-1 app-input">
                            </div>
                        </div>
                    </div>
                    <div class="md:col-span-6">
                        <label class="block text-xs text-gray-500">Amenities</label>
                        <textarea name="rooms[__INDEX__][amenities]" rows="2" class="mt-1 app-input"></textarea>
                    </div>
                    <div class="md:col-span-6">
                        <label class="block text-xs text-gray-500">Include</label>
                        <textarea name="rooms[__INDEX__][include]" rows="2" class="mt-1 app-input"></textarea>
                    </div>
                    <div class="md:col-span-6">
                        <label class="block text-xs text-gray-500">Additional Info</label>
                        <textarea name="rooms[__INDEX__][additional_info]" rows="2" class="mt-1 app-input"></textarea>
                    </div>
                </div>
            </div>
        </template>
    </div>
</div>




