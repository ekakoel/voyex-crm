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

    $priceRows = old('hotel_prices');
    if ($priceRows === null) {
        $priceRows = $hotel?->prices?->map(fn ($price) => [
            'rooms_id' => $price->rooms_id,
            'start_date' => $price->start_date,
            'end_date' => $price->end_date,
            'contract_rate' => $price->contract_rate,
            'markup' => $price->markup,
            'kick_back' => $price->kick_back,
        ])->toArray() ?? [];
    }
    if (empty($priceRows)) {
        $priceRows = [['rooms_id' => '']];
    }



    $roomOptions = $hotel?->rooms ?? collect();
    $statusValue = strtolower((string) old('status', $hotel->status ?? 'active'));
@endphp

<div class="space-y-6 module-page module-page--hotels">
    <div class="app-card p-5 space-y-5">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Hotel Name') }}</label>
                <input name="name" value="{{ old('name', $hotel->name ?? '') }}" class="mt-1 app-input" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Hotel Code') }}</label>
                <input name="code" value="{{ old('code', $hotel->code ?? '') }}" class="mt-1 app-input" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Region') }}</label>
                <input name="region" value="{{ old('region', $hotel->region ?? '') }}" class="mt-1 app-input" required>
            </div>
            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Address') }}</label>
                <input name="address" value="{{ old('address', $hotel->address ?? '') }}" class="mt-1 app-input" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Contact Person') }}</label>
                <input name="contact_person" value="{{ old('contact_person', $hotel->contact_person ?? '') }}" class="mt-1 app-input" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Phone') }}</label>
                <input name="phone" value="{{ old('phone', $hotel->phone ?? '') }}" class="mt-1 app-input" required>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Website') }}</label>
                <input name="web" value="{{ old('web', $hotel->web ?? '') }}" class="mt-1 app-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Map URL') }}</label>
                <input name="map" value="{{ old('map', $hotel->map ?? '') }}" class="mt-1 app-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Check-in Time') }}</label>
                <input name="check_in_time" value="{{ old('check_in_time', $hotel->check_in_time ?? '') }}" class="mt-1 app-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Check-out Time') }}</label>
                <input name="check_out_time" value="{{ old('check_out_time', $hotel->check_out_time ?? '') }}" class="mt-1 app-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Min Stay') }}</label>
                <input name="min_stay" value="{{ old('min_stay', $hotel->min_stay ?? '') }}" class="mt-1 app-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Max Stay') }}</label>
                <input name="max_stay" value="{{ old('max_stay', $hotel->max_stay ?? '') }}" class="mt-1 app-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Airport Distance (km)') }}</label>
                <input name="airport_distance" value="{{ old('airport_distance', $hotel->airport_distance ?? '') }}" class="mt-1 app-input" type="number" min="0">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Airport Duration (minutes)') }}</label>
                <input name="airport_duration" value="{{ old('airport_duration', $hotel->airport_duration ?? '') }}" class="mt-1 app-input" type="number" min="0">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Status') }}</label>
                <select name="status" class="mt-1 app-input" required>
                    @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                        <option value="{{ $value }}" @selected($statusValue === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Cover (Image URL)') }}</label>
                <input name="cover" value="{{ old('cover', $hotel->cover ?? '') }}" class="mt-1 app-input" required>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Benefits') }}</label>
                <input name="benefits" value="{{ old('benefits', $hotel->benefits ?? '') }}" class="mt-1 app-input">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Optional Rate') }}</label>
                <input name="optional_rate" value="{{ old('optional_rate', $hotel->optional_rate ?? '') }}" class="mt-1 app-input">
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Description') }}</label>
                <textarea name="description" rows="3" class="mt-1 app-input">{{ old('description', $hotel->description ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Facility') }}</label>
                <textarea name="facility" rows="3" class="mt-1 app-input">{{ old('facility', $hotel->facility ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Additional Info') }}</label>
                <textarea name="additional_info" rows="3" class="mt-1 app-input">{{ old('additional_info', $hotel->additional_info ?? '') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ ui_phrase('Cancellation Policy') }}</label>
                <textarea name="cancellation_policy" rows="3" class="mt-1 app-input">{{ old('cancellation_policy', $hotel->cancellation_policy ?? '') }}</textarea>
            </div>
        </div>
    </div>


    <div class="app-card p-5 space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Seasonal Prices') }}</h3>
            <button type="button" class="btn-ghost-sm" data-add-row="price">{{ ui_phrase('Add Price') }}</button>
        </div>
        <div id="price-rows" class="space-y-3">
            @foreach ($priceRows as $index => $row)
                <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700 md:grid-cols-12" data-row>
                    <div class="md:col-span-4">
                        <label class="block text-xs text-gray-500">{{ ui_phrase('Room') }}</label>
                        <select name="hotel_prices[{{ $index }}][rooms_id]" class="mt-1 app-input" @disabled($roomOptions->isEmpty())>
                            <option value="">{{ $roomOptions->isEmpty() ? 'Add rooms first' : 'Select room' }}</option>
                            @foreach ($roomOptions as $roomOption)
                                <option value="{{ $roomOption->id }}" @selected((string) ($row['rooms_id'] ?? '') === (string) $roomOption->id)>{{ $roomOption->rooms }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-500">{{ ui_phrase('Start') }}</label>
                        <input type="date" name="hotel_prices[{{ $index }}][start_date]" value="{{ $row['start_date'] ?? '' }}" class="mt-1 app-input">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-500">{{ ui_phrase('End') }}</label>
                        <input type="date" name="hotel_prices[{{ $index }}][end_date]" value="{{ $row['end_date'] ?? '' }}" class="mt-1 app-input">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-500">{{ ui_phrase('Contract Rate (IDR)') }}</label>
                        <input type="number" min="0" name="hotel_prices[{{ $index }}][contract_rate]" value="{{ $row['contract_rate'] ?? '' }}" class="mt-1 app-input">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-xs text-gray-500">{{ ui_phrase('Markup') }}</label>
                        <input type="number" min="0" name="hotel_prices[{{ $index }}][markup]" value="{{ $row['markup'] ?? '' }}" class="mt-1 app-input">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-xs text-gray-500">{{ ui_phrase('Kick Back') }}</label>
                        <input type="number" min="0" name="hotel_prices[{{ $index }}][kick_back]" value="{{ $row['kick_back'] ?? '' }}" class="mt-1 app-input">
                    </div>
                    <div class="md:col-span-12 flex justify-end">
                        <button type="button" class="btn-ghost-sm" data-remove-row>{{ ui_phrase('Remove') }}</button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="flex items-center gap-2">
        <button class="btn-primary">{{ $buttonLabel }}</button>
        <a href="{{ route('hotels.index') }}" class="btn-secondary">{{ ui_phrase('Cancel') }}</a>
    </div>
</div>

@push('scripts')
<script>
    (function () {
        const addRowHandlers = {
            room: () => addRow('room-rows', roomTemplate()),
            price: () => addRow('price-rows', priceTemplate()),
        };

        document.querySelectorAll('[data-add-row]').forEach((btn) => {
            btn.addEventListener('click', () => {
                const key = btn.getAttribute('data-add-row');
                if (addRowHandlers[key]) {
                    addRowHandlers[key]();
                }
            });
        });

        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-row]');
            if (!button) return;
            const row = button.closest('[data-row]');
            if (row) {
                row.remove();
            }
        });

        document.addEventListener('change', (event) => {
            const input = event.target.closest('.room-cover-input');
            if (!input) return;
            const row = input.closest('[data-room-card]') || input.closest('[data-row]') || input.closest('.grid');
            if (!row) return;
            const preview = row.querySelector('.room-cover-preview');
            if (!preview) return;
            const file = (input.files || [])[0];
            if (!file || !String(file.type || '').startsWith('image/')) return;
            const url = URL.createObjectURL(file);
            let img = preview.querySelector('img');
            if (!img) {
                img = document.createElement('img');
                img.className = 'h-full w-full object-cover';
                img.alt = 'Room cover preview';
                preview.appendChild(img);
            }
            img.addEventListener('load', () => {
                img.classList.add('image-loaded');
                preview.classList.add('has-image');
                URL.revokeObjectURL(url);
            }, { once: true });
            img.addEventListener('error', () => {
                preview.classList.remove('has-image');
                img.remove();
            }, { once: true });
            img.src = url;
        });

        function addRow(containerId, html) {
            const container = document.getElementById(containerId);
            if (!container) return;
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html;
            container.appendChild(wrapper.firstElementChild);
        }

        function rowIndex(containerId) {
            const container = document.getElementById(containerId);
            return container ? container.children.length : 0;
        }

        function roomTemplate() {
            const idx = rowIndex('room-rows');
            return `
                <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700 md:grid-cols-12">
                    <div class="md:col-span-4">
                        <label class="block text-xs text-gray-500">Room Name</label>
                        <input name="rooms[${idx}][rooms]" class="mt-1 app-input" required>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs text-gray-500">Room View</label>
                        <select name="rooms[${idx}][room_view_id]" class="mt-1 app-input">
                            <option value="">-</option>
                            @foreach ($roomViews as $view)
                                <option value="{{ $view->id }}">{{ $view->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-500">Capacity Adult</label>
                        <input name="rooms[${idx}][capacity_adult]" class="mt-1 app-input" type="number" min="0">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-500">Capacity Child</label>
                        <input name="rooms[${idx}][capacity_child]" class="mt-1 app-input" type="number" min="0">
                    </div>
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
                        <input type="file" name="rooms[${idx}][cover]" accept="image/*" class="room-cover-input mt-2 w-full rounded-lg border border-gray-300 px-2 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        <input type="hidden" name="rooms[${idx}][existing_cover]" value="">
                        <p class="mt-1 text-[11px] text-gray-500">Image will be cropped to 3:2 and a thumbnail is generated.</p>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs text-gray-500">Beds</label>
                        <input name="rooms[${idx}][beds]" class="mt-1 app-input">
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-xs text-gray-500">Size</label>
                        <input name="rooms[${idx}][size]" class="mt-1 app-input">
                    </div>
                    <div class="md:col-span-12">
                        <label class="block text-xs text-gray-500">Amenities</label>
                        <textarea name="rooms[${idx}][amenities]" rows="2" class="mt-1 app-input"></textarea>
                    </div>
                    <div class="md:col-span-12">
                        <label class="block text-xs text-gray-500">Include</label>
                        <textarea name="rooms[${idx}][include]" rows="2" class="mt-1 app-input"></textarea>
                    </div>
                    <div class="md:col-span-12">
                        <label class="block text-xs text-gray-500">Additional Info</label>
                        <textarea name="rooms[${idx}][additional_info]" rows="2" class="mt-1 app-input"></textarea>
                    </div>
                </div>
            `;
        }


        function priceTemplate() {
            const idx = rowIndex('price-rows');
            return `
                <div class="grid grid-cols-1 gap-3 rounded-lg border border-slate-200 p-3 dark:border-slate-700 md:grid-cols-12" data-row>
                    <div class="md:col-span-4">
                        <label class="block text-xs text-gray-500">Room</label>
                        <select name="hotel_prices[${idx}][rooms_id]" class="mt-1 app-input">
                            <option value="">Select room</option>
                            @foreach ($roomOptions as $roomOption)
                                <option value="{{ $roomOption->id }}">{{ $roomOption->rooms }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-500">Start</label>
                        <input type="date" name="hotel_prices[${idx}][start_date]" class="mt-1 app-input">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-500">End</label>
                        <input type="date" name="hotel_prices[${idx}][end_date]" class="mt-1 app-input">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs text-gray-500">Contract Rate (IDR)</label>
                        <input type="number" min="0" name="hotel_prices[${idx}][contract_rate]" class="mt-1 app-input">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-xs text-gray-500">Markup</label>
                        <input type="number" min="0" name="hotel_prices[${idx}][markup]" class="mt-1 app-input">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-xs text-gray-500">Kick Back</label>
                        <input type="number" min="0" name="hotel_prices[${idx}][kick_back]" class="mt-1 app-input">
                    </div>
                    <div class="md:col-span-12 flex justify-end">
                        <button type="button" class="btn-ghost-sm" data-remove-row>Remove</button>
                    </div>
                </div>
            `;
        }


    })();
</script>
@endpush
