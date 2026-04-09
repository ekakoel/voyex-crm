<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Hotel;
use App\Models\HotelPrice;
use App\Models\HotelRoom;
use App\Models\RoomView;
use App\Support\ImageThumbnailGenerator;
use App\Support\LocationResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HotelController extends Controller
{
    public function index(Request $request)
    {
        $query = Hotel::query()
            ->withTrashed()
            ->with('destination:id,name,province')
            ->withCount([
                'rooms',
                'prices as prices_count' => function ($countQuery) {
                    $countQuery->whereNotNull('end_date')
                        ->whereDate('end_date', '>=', now()->toDateString());
                },
            ])
            ->orderBy('name');

        if ($request->filled('q')) {
            $term = (string) $request->string('q');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('code', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%")
                    ->orWhere('province', 'like', "%{$term}%")
                    ->orWhere('country', 'like', "%{$term}%")
                    ->orWhere('region', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%")
                    ->orWhere('contact_person', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhereHas('destination', function ($dq) use ($term) {
                        $dq->where('name', 'like', "%{$term}%")
                            ->orWhere('province', 'like', "%{$term}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('destination_id')) {
            $query->where('destination_id', (int) $request->input('destination_id'));
        }

        $perPage = (int) $request->input('per_page', 10);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 10;

        $hotels = $query->paginate($perPage)->withQueryString();

        $statsCards = [
            [
                'key' => 'total',
                'label' => 'Total Hotels',
                'value' => Hotel::query()->count(),
                'caption' => 'All records',
            ],
            [
                'key' => 'active',
                'label' => 'Active Hotels',
                'value' => Hotel::query()->whereNull('deleted_at')->where('status', 'active')->count(),
                'caption' => 'Currently active',
            ],
            [
                'key' => 'rooms',
                'label' => 'Rooms',
                'value' => HotelRoom::query()->count(),
                'caption' => 'All room types',
            ],
        ];

        if ($this->wantsAjaxFragment($request)) {
            return response()->json([
                'html' => view('modules.hotels.partials._index-results', compact('hotels', 'statsCards'))->render(),
                'url' => route('hotels.index', $request->query()),
            ]);
        }

        $destinations = Destination::query()
            ->orderBy('province')
            ->orderBy('name')
            ->get(['id', 'name', 'province']);

        return view('modules.hotels.index', compact('hotels', 'statsCards', 'destinations'));
    }

    public function create()
    {
        $destinations = Destination::query()
            ->orderBy('province')
            ->orderBy('name')
            ->get(['id', 'name', 'province']);

        return view('modules.hotels.create', compact('destinations'));
    }

    public function show(Hotel $hotel)
    {
        $hotel->load([
            'destination:id,name,province',
            'rooms' => function ($query) {
                $query->orderBy('rooms')->with('roomView:id,name');
            },
            'prices' => function ($query) {
                $query->with('room:id,rooms')
                    ->whereNotNull('end_date')
                    ->whereDate('end_date', '>=', now()->toDateString())
                    ->orderByDesc('end_date')
                    ->orderByDesc('start_date');
            },
        ]);

        return view('modules.hotels.show', compact('hotel'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateHotel($request, null);
        $coverResult = $this->resolveHotelCover($request, null);
        if ($coverResult['cover'] === '') {
            throw ValidationException::withMessages([
                'cover_file' => 'Cover image is required.',
            ]);
        }

        $validated['cover'] = $coverResult['cover'];
        unset($validated['cover_file'], $validated['existing_cover']);
        $validated['author_id'] = auth()->id();
        $validated['code'] = $this->generateHotelCode();

        $hotel = Hotel::query()->create($validated);

        return redirect()->route('hotels.edit', [$hotel, 'step' => 'rooms'])
            ->with('success', 'Hotel created. Continue by adding rooms.');
    }

    public function edit(Request $request, Hotel $hotel)
    {
        $step = (string) $request->input('step', 'info');
        $viewData = $this->buildEditorViewData($hotel, $step);

        if (($viewData['redirectStep'] ?? null) !== null) {
            $redirectStep = $viewData['redirectStep'];
            unset($viewData['redirectStep']);

            if ($this->wantsAjaxFragment($request)) {
                $viewData = $this->buildEditorViewData($hotel, $redirectStep);

                return response()->json([
                    'html' => view('modules.hotels.partials._editor', $viewData)->render(),
                    'step' => $viewData['step'],
                    'url' => route('hotels.edit', [$hotel, 'step' => $viewData['step']]),
                    'warning' => 'Please add at least one room before setting prices.',
                ]);
            }

            return redirect()->route('hotels.edit', [$hotel, 'step' => $redirectStep])
                ->with('warning', 'Please add at least one room before setting prices.');
        }

        if ($this->wantsAjaxFragment($request)) {
            return response()->json([
                'html' => view('modules.hotels.partials._editor', $viewData)->render(),
                'step' => $viewData['step'],
                'url' => route('hotels.edit', [$hotel, 'step' => $viewData['step']]),
            ]);
        }

        return view('modules.hotels.edit', $viewData);
    }

    public function update(Request $request, Hotel $hotel)
    {
        return $this->updateInfo($request, $hotel);
    }

    public function updateInfo(Request $request, Hotel $hotel)
    {
        $validated = $this->validateHotel($request, $hotel);
        $coverResult = $this->resolveHotelCover($request, $hotel->cover);

        if ($coverResult['cover'] === '') {
            throw ValidationException::withMessages([
                'cover_file' => 'Cover image is required.',
            ]);
        }

        $validated['cover'] = $coverResult['cover'];
        unset($validated['cover_file'], $validated['existing_cover']);
        $validated['author_id'] = $hotel->author_id ?: auth()->id();

        $hotel->update($validated);

        if ($coverResult['delete'] !== '') {
            $this->deleteHotelCovers([$coverResult['delete']]);
        }

        $nextStep = $request->boolean('stay') ? 'info' : 'rooms';
        $message = $request->boolean('stay') ? 'Hotel info updated.' : 'Hotel info updated. Continue with rooms.';

        if ($this->wantsAjaxFragment($request)) {
            return $this->ajaxEditorResponse($request, $hotel, $nextStep, $message);
        }

        return redirect()->route('hotels.edit', [$hotel, 'step' => $nextStep])
            ->with('success', $message);
    }

    public function updateRooms(Request $request, Hotel $hotel)
    {
        $request->validate([
            'rooms' => ['required', 'array', 'min:1'],
            'rooms.*.cover' => ['nullable', 'image'],
            'rooms.*.existing_cover' => ['nullable', 'string'],
        ]);

        $existingCovers = $this->normalizeRoomCovers($hotel->rooms->pluck('cover')->all());
        $retainedCovers = [];
        $roomsPayload = $this->normalizeRooms($request, $request->input('rooms', []), $retainedCovers, (string) ($hotel->status ?? 'active'));

        DB::transaction(function () use ($hotel, $roomsPayload) {
            $hotel->prices()->delete();
            $hotel->rooms()->delete();
            if ($roomsPayload !== []) {
                $hotel->rooms()->createMany($roomsPayload);
            }
        });

        $retainedStored = $this->normalizeRoomCovers($retainedCovers);
        $coversToDelete = array_values(array_diff($existingCovers, $retainedStored));
        if ($coversToDelete !== []) {
            $this->deleteRoomCovers($coversToDelete);
        }

        $nextStep = $request->boolean('stay') ? 'rooms' : 'prices';
        $message = $request->boolean('stay') ? 'Rooms updated.' : 'Rooms updated. Continue with prices.';

        if ($this->wantsAjaxFragment($request)) {
            return $this->ajaxEditorResponse($request, $hotel, $nextStep, $message);
        }

        return redirect()->route('hotels.edit', [$hotel, 'step' => $nextStep])
            ->with('success', $message);
    }

    public function updatePrices(Request $request, Hotel $hotel)
    {
        $roomIds = $hotel->rooms()->pluck('id')->all();
        $pricesPayload = $this->normalizePrices($request->input('hotel_prices', []), $roomIds);

        DB::transaction(function () use ($hotel, $pricesPayload) {
            $hotel->prices()->delete();
            if ($pricesPayload !== []) {
                $hotel->prices()->createMany($pricesPayload);
            }
        });

        $message = $request->boolean('stay') ? 'Prices updated.' : 'Prices updated. Hotel setup completed.';

        if ($this->wantsAjaxFragment($request)) {
            return $this->ajaxEditorResponse($request, $hotel, 'prices', $message);
        }

        if ($request->boolean('stay')) {
            return redirect()->route('hotels.edit', [$hotel, 'step' => 'prices'])
                ->with('success', $message);
        }

        return redirect()->route('hotels.index')
            ->with('success', $message);
    }

    public function destroy(Hotel $hotel)
    {
        $hotel->update(['status' => 'inactive']);
        $hotel->delete();

        return redirect()->route('hotels.index')->with('success', 'Hotel deactivated successfully.');
    }

    public function toggleStatus($hotel)
    {
        $hotel = Hotel::withTrashed()->findOrFail($hotel);
        if ($hotel->trashed()) {
            $hotel->restore();
            $hotel->update(['status' => 'active']);

            return redirect()->route('hotels.index')->with('success', 'Hotel activated successfully.');
        }

        $hotel->update(['status' => 'inactive']);
        $hotel->delete();

        return redirect()->route('hotels.index')->with('success', 'Hotel deactivated successfully.');
    }

    private function wantsAjaxFragment(Request $request): bool
    {
        return $request->ajax()
            || $request->expectsJson()
            || $request->header('X-Hotels-Ajax') === '1';
    }

    private function ajaxEditorResponse(Request $request, Hotel $hotel, string $step, string $message)
    {
        $viewData = $this->buildEditorViewData($hotel, $step);

        return response()->json([
            'html' => view('modules.hotels.partials._editor', $viewData)->render(),
            'message' => $message,
            'step' => $viewData['step'],
            'url' => route('hotels.edit', [$hotel, 'step' => $viewData['step']]),
        ]);
    }

    private function buildEditorViewData(Hotel $hotel, string $step): array
    {
        $hotel->load([
            'rooms',
            'prices' => function ($query) {
                $query->whereNotNull('end_date')
                    ->whereDate('end_date', '>=', now()->toDateString());
            },
        ]);

        $step = in_array($step, ['info', 'rooms', 'prices'], true) ? $step : 'info';
        if ($step === 'prices' && ($hotel->rooms?->count() ?? 0) === 0) {
            return [
                'hotel' => $hotel,
                'roomViews' => collect(),
                'roomOptions' => collect(),
                'step' => 'rooms',
                'redirectStep' => 'rooms',
            ];
        }

        return [
            'hotel' => $hotel,
            'destinations' => Destination::query()
                ->orderBy('province')
                ->orderBy('name')
                ->get(['id', 'name', 'province']),
            'roomViews' => RoomView::query()->orderBy('name')->get(['id', 'name']),
            'roomOptions' => $hotel->rooms ?? collect(),
            'step' => $step,
        ];
    }

    private function validateHotel(Request $request, ?Hotel $hotel): array
    {
        $locationResolver = app(LocationResolver::class);

        $prefilled = [
            'google_maps_url' => (string) $request->input('map', ''),
            'city' => $request->input('city'),
            'province' => $request->input('province'),
            'country' => $request->input('country'),
            'address' => $request->input('address'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'destination_id' => $request->input('destination_id'),
        ];
        $locationResolver->enrichFromGoogleMapsUrl($prefilled);
        $this->applyDestinationContext($prefilled);

        $request->merge([
            'map' => $prefilled['google_maps_url'] ?? $request->input('map'),
            'city' => $prefilled['city'] ?? $request->input('city'),
            'province' => $prefilled['province'] ?? $request->input('province'),
            'country' => $prefilled['country'] ?? $request->input('country'),
            'address' => $prefilled['address'] ?? $request->input('address'),
            'latitude' => $prefilled['latitude'] ?? $request->input('latitude'),
            'longitude' => $prefilled['longitude'] ?? $request->input('longitude'),
            'destination_id' => $prefilled['destination_id'] ?? $request->input('destination_id'),
        ]);

        $validated = $request->validate([
            'destination_id' => ['nullable', 'integer', 'exists:destinations,id'],
            'name' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'region' => ['nullable', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:255'],
            'airport_duration' => ['nullable', 'integer', 'min:0'],
            'airport_distance' => ['nullable', 'integer', 'min:0'],
            'contact_person' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'min_stay' => ['nullable', 'string', 'max:255'],
            'max_stay' => ['nullable', 'string', 'max:255'],
            'check_in_time' => ['nullable', 'string', 'max:255'],
            'check_out_time' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'max:255'],
            'cover' => ['nullable', 'string', 'max:255'],
            'cover_file' => ['nullable', 'image'],
            'existing_cover' => ['nullable', 'string', 'max:255'],
            'web' => ['nullable', 'url', 'max:500'],
            'map' => ['nullable', 'url', 'max:5000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'description' => ['nullable', 'string'],
            'description_traditional' => ['nullable', 'string'],
            'description_simplified' => ['nullable', 'string'],
            'facility' => ['nullable', 'string'],
            'facility_traditional' => ['nullable', 'string'],
            'facility_simplified' => ['nullable', 'string'],
            'additional_info' => ['nullable', 'string'],
            'additional_info_traditional' => ['nullable', 'string'],
            'additional_info_simplified' => ['nullable', 'string'],
            'wedding_info' => ['nullable', 'string'],
            'entrance_fee' => ['nullable', 'string'],
            'wedding_cancellation_policy' => ['nullable', 'string'],
            'cancellation_policy' => ['nullable', 'string'],
            'cancellation_policy_traditional' => ['nullable', 'string'],
            'cancellation_policy_simplified' => ['nullable', 'string'],
        ]);

        $resolved = [
            'google_maps_url' => (string) ($validated['map'] ?? ''),
            'city' => $validated['city'] ?? null,
            'province' => $validated['province'] ?? null,
            'country' => $validated['country'] ?? null,
            'address' => $validated['address'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'destination_id' => $validated['destination_id'] ?? null,
        ];
        $locationResolver->enrichFromGoogleMapsUrl($resolved);
        $this->applyDestinationContext($resolved);
        $locationResolver->resolveDestinationId($resolved, true);

        $validated['map'] = $resolved['google_maps_url'] ?? ($validated['map'] ?? null);
        $validated['city'] = $resolved['city'] ?? null;
        $validated['province'] = $resolved['province'] ?? null;
        $validated['country'] = $resolved['country'] ?? null;
        $validated['address'] = $resolved['address'] ?? null;
        $validated['latitude'] = $resolved['latitude'] ?? null;
        $validated['longitude'] = $resolved['longitude'] ?? null;
        $validated['destination_id'] = $resolved['destination_id'] ?? null;

        $validated['region'] = trim((string) ($validated['region'] ?? ''));
        if ($validated['region'] === '') {
            $validated['region'] = trim((string) ($validated['province'] ?? ''));
        }

        return $validated;
    }

    private function applyDestinationContext(array &$validated): void
    {
        $destinationId = (int) ($validated['destination_id'] ?? 0);
        if ($destinationId <= 0) {
            return;
        }

        $destination = Destination::query()->find($destinationId);
        if (! $destination) {
            return;
        }

        if (empty($validated['city']) && ! empty($destination->city)) {
            $validated['city'] = (string) $destination->city;
        }
        if (empty($validated['province']) && ! empty($destination->province)) {
            $validated['province'] = (string) $destination->province;
        }
        if (empty($validated['country']) && ! empty($destination->country)) {
            $validated['country'] = (string) $destination->country;
        }
    }

    private function generateHotelCode(): string
    {
        do {
            $code = 'HTL-' . now()->format('Ymd') . '-' . random_int(1000, 9999);
        } while (Hotel::query()->where('code', $code)->exists());

        return $code;
    }

    private function resolveHotelCover(Request $request, ?string $currentCover): array
    {
        $uploaded = $request->file('cover_file');
        $currentNormalized = $this->normalizeHotelCoverPath((string) $currentCover);

        if ($uploaded) {
            $originalPath = $uploaded->store('hotels/cover', 'public');
            $processedPath = ImageThumbnailGenerator::processAndGenerate('public', $originalPath, 3, 2, 360, 240) ?? $originalPath;
            $deletePath = $currentNormalized !== '' && ! Str::startsWith($currentNormalized, ['http://', 'https://']) && $currentNormalized !== $processedPath
                ? $currentNormalized
                : '';

            return [
                'cover' => $processedPath,
                'delete' => $deletePath,
            ];
        }

        $existing = $this->normalizeHotelCoverPath((string) $request->input('existing_cover', ''));
        if ($existing !== '') {
            return ['cover' => $existing, 'delete' => ''];
        }

        $legacyCover = $this->normalizeHotelCoverPath((string) $request->input('cover', ''));
        if ($legacyCover !== '') {
            return ['cover' => $legacyCover, 'delete' => ''];
        }

        if ($currentNormalized !== '') {
            return ['cover' => $currentNormalized, 'delete' => ''];
        }

        return ['cover' => '', 'delete' => ''];
    }

    private function normalizeHotelCoverPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');
        if (Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }
        if (! Str::startsWith($path, ['hotels/cover/', 'hotels/covers/'])) {
            if (! Str::contains($path, '/')) {
                $coverCandidate = 'hotels/cover/' . $path;
                $coversCandidate = 'hotels/covers/' . $path;
                $path = Storage::disk('public')->exists($coverCandidate)
                    ? $coverCandidate
                    : (Storage::disk('public')->exists($coversCandidate) ? $coversCandidate : $coverCandidate);
            }
        }

        return trim($path);
    }

    private function deleteHotelCovers(array $paths): void
    {
        foreach ($paths as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }
            Storage::disk('public')->delete($path);
            Storage::disk('public')->delete(ImageThumbnailGenerator::thumbnailPathFor($path));
        }
    }

    private function normalizeRooms(Request $request, array $rows, array &$retainedCovers = [], string $roomStatus = 'active'): array
    {
        $payload = [];
        foreach (array_values($rows) as $index => $row) {
            $rooms = trim((string) ($row['rooms'] ?? ''));
            if ($rooms === '') {
                continue;
            }
            $cover = $this->resolveRoomCover($request, $index, $row, $retainedCovers);
            $payload[] = [
                'room_view_id' => Arr::get($row, 'room_view_id'),
                'cover' => $cover,
                'rooms' => $rooms,
                'capacity_adult' => (int) ($row['capacity_adult'] ?? 0),
                'capacity_child' => (int) ($row['capacity_child'] ?? 0),
                'view' => $row['view'] ?? null,
                'beds' => $row['beds'] ?? null,
                'size' => $row['size'] ?? null,
                'amenities' => $row['amenities'] ?? null,
                'amenities_traditional' => $row['amenities_traditional'] ?? null,
                'amenities_simplified' => $row['amenities_simplified'] ?? null,
                'additional_info' => $row['additional_info'] ?? null,
                'additional_info_traditional' => $row['additional_info_traditional'] ?? null,
                'additional_info_simplified' => $row['additional_info_simplified'] ?? null,
                'include' => $row['include'] ?? null,
                'include_traditional' => $row['include_traditional'] ?? null,
                'include_simplified' => $row['include_simplified'] ?? null,
                'status' => $roomStatus === 'inactive' ? 'inactive' : 'active',
            ];
        }
        return $payload;
    }

    private function resolveRoomCover(Request $request, int $index, array $row, array &$retainedCovers): string
    {
        $uploaded = $request->file("rooms.{$index}.cover");
        if ($uploaded) {
            $originalPath = $uploaded->store('hotels/rooms', 'public');
            $processedPath = ImageThumbnailGenerator::processAndGenerate('public', $originalPath, 3, 2, 360, 240) ?? $originalPath;
            return $processedPath;
        }

        $existing = trim((string) ($row['existing_cover'] ?? ''));
        if ($existing !== '') {
            $normalized = $this->normalizeRoomCoverPath($existing);
            if ($normalized !== '') {
                $retainedCovers[] = $normalized;
            }
            return $normalized;
        }

        return '';
    }

    private function normalizeRoomCovers($covers): array
    {
        if (is_string($covers)) {
            $covers = [$covers];
        }
        if (! is_array($covers)) {
            return [];
        }

        $normalized = [];
        foreach ($covers as $path) {
            if (! is_string($path)) {
                continue;
            }
            $candidate = $this->normalizeRoomCoverPath($path);
            if ($candidate === '' || Str::startsWith($candidate, ['http://', 'https://'])) {
                continue;
            }
            $normalized[] = $candidate;
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeRoomCoverPath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');
        if (Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }
        if (! Str::startsWith($path, 'hotels/rooms/')) {
            if (! Str::contains($path, '/')) {
                $path = 'hotels/rooms/' . $path;
            }
        }

        return trim($path);
    }

    private function deleteRoomCovers(array $paths): void
    {
        foreach ($paths as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }
            Storage::disk('public')->delete($path);
            Storage::disk('public')->delete(ImageThumbnailGenerator::thumbnailPathFor($path));
        }
    }

    private function normalizePrices(array $rows, array $allowedRoomIds = []): array
    {
        $payload = [];
        foreach ($rows as $index => $row) {
            $roomsId = (int) ($row['rooms_id'] ?? 0);
            if ($roomsId <= 0 || ($allowedRoomIds !== [] && !in_array($roomsId, $allowedRoomIds, true))) {
                continue;
            }

            $contractRate = $this->parseRateValue($row['contract_rate'] ?? 0);
            $markupType = (($row['markup_type'] ?? 'fixed') === 'percent') ? 'percent' : 'fixed';
            $markup = $this->parseRateValue($row['markup'] ?? 0);
            $kickBack = $this->parseRateValue($row['kick_back'] ?? 0);

            if ($markupType === 'percent' && $markup > 100) {
                throw ValidationException::withMessages([
                    "hotel_prices.{$index}.markup" => 'Markup percent cannot be greater than 100.',
                ]);
            }

            $payload[] = [
                'rooms_id' => $roomsId,
                'start_date' => $row['start_date'] ?? null,
                'end_date' => $row['end_date'] ?? null,
                'markup_type' => $markupType,
                'markup' => round($markup, 0),
                'kick_back' => round($kickBack, 0),
                'contract_rate' => round($contractRate, 0),
                'publish_rate' => round($this->calculatePublishRate($contractRate, $markupType, $markup), 0),
                'author' => auth()->id(),
            ];
        }
        return $payload;
    }

    private function parseRateValue($value): float
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return 0;
        }

        if (preg_match('/^\d+([.,]\d{1,2})?$/', $raw) === 1 && ! str_contains($raw, ' ')) {
            return max(0, (float) str_replace(',', '.', $raw));
        }

        $digitsOnly = preg_replace('/[^\d]/', '', $raw);
        if (! is_string($digitsOnly) || $digitsOnly === '') {
            return 0;
        }

        return max(0, (float) $digitsOnly);
    }

    private function calculatePublishRate(float $contractRate, string $markupType, float $markup): float
    {
        $base = max(0, $contractRate);
        $value = max(0, $markup);

        if ($markupType === 'percent') {
            return $base + ($base * ($value / 100));
        }

        return $base + $value;
    }

}


