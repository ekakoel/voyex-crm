<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Airport;
use App\Models\Destination;
use App\Support\ImageThumbnailGenerator;
use App\Support\LocationResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AirportController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ]);

        $query = Airport::query()
            ->withTrashed()
            ->with('destination:id,name')
            ->select([
                'id',
                'code',
                'name',
                'location',
                'city',
                'province',
                'country',
                'destination_id',
                'is_active',
                'deleted_at',
            ])
            ->latest('id');

        if ($request->filled('q')) {
            $term = trim((string) $request->string('q'));
            if (mb_strlen($term) >= 3) {
                $query->where(function ($q) use ($term) {
                    $q->where('code', 'like', "%{$term}%")
                        ->orWhere('name', 'like', "%{$term}%")
                        ->orWhere('city', 'like', "%{$term}%")
                        ->orWhere('province', 'like', "%{$term}%");
                });
            }
        }

        $query->when(($validated['status'] ?? null) === 'active', fn ($q) => $q->whereNull('deleted_at'));
        $query->when(($validated['status'] ?? null) === 'inactive', fn ($q) => $q->onlyTrashed());

        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
        $airports = $query->paginate($perPage)->withQueryString();
        $perPageOptions = [10, 25, 50, 100];
        $canManageActivationActions = auth()->user()?->canManageActivationActions() === true;
        $airportRows = $this->buildAirportIndexRows($airports, $canManageActivationActions);
        $statsCards = [
            [
                'key' => 'total',
                'label' => 'Total Airports',
                'value' => Airport::query()->count(),
                'caption' => 'All records',
            ],
            [
                'key' => 'active',
                'label' => 'Active Airports',
                'value' => Airport::query()->whereNull('deleted_at')->count(),
                'caption' => 'Currently active',
            ],
            [
                'key' => 'mapped',
                'label' => 'With Coordinates',
                'value' => Airport::query()
                    ->whereNotNull('latitude')
                    ->whereNotNull('longitude')
                    ->count(),
                'caption' => 'Ready for maps',
            ],
        ];

        return view('modules.airports.index', compact(
            'airports',
            'airportRows',
            'statsCards',
            'perPageOptions'
        ));
    }

    public function create()
    {
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);

        return view('modules.airports.create', compact('destinations'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request, null);
        $coverResult = $this->resolveAirportCover($request, null);
        $validated['cover'] = $coverResult['cover'];
        unset($validated['cover_file'], $validated['existing_cover']);
        Airport::query()->create($validated);

        return redirect()->route('airports.index')->with('success', ui_phrase('Airport created successfully.'));
    }

    public function show(Airport $airport)
    {
        $airport->load('destination:id,name');
        if (Schema::hasColumn('airports', 'created_by')) {
            $airport->load('creator:id,name');
        }
        if (Schema::hasColumn('airports', 'updated_by')) {
            $airport->load('updater:id,name');
        }

        return view('modules.airports.show', compact('airport'));
    }

    public function edit(Airport $airport)
    {
        if (Schema::hasColumn('airports', 'created_by')) {
            $airport->load('creator:id,name');
        }
        if (Schema::hasColumn('airports', 'updated_by')) {
            $airport->load('updater:id,name');
        }

        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);

        return view('modules.airports.edit', compact('airport', 'destinations'));
    }

    public function update(Request $request, Airport $airport)
    {
        $validated = $this->validatePayload($request, $airport);
        $coverResult = $this->resolveAirportCover($request, $airport->cover);
        $validated['cover'] = $coverResult['cover'];
        unset($validated['cover_file'], $validated['existing_cover']);
        $airport->update($validated);
        if ($coverResult['delete'] !== '') {
            $this->deleteAirportCovers([$coverResult['delete']]);
        }

        return redirect()->route('airports.index')->with('success', ui_phrase('Airport updated successfully.'));
    }

    public function destroy(Airport $airport)
    {
        $airport->delete();

        return redirect()->route('airports.index')->with('success', ui_phrase('Airport deactivated successfully.'));
    }

    public function toggleStatus($airport)
    {
        abort_unless(auth()->user()?->canManageActivationActions(), 403);
        $airport = Airport::withTrashed()->findOrFail($airport);
        if ($airport->trashed()) {
            $airport->restore();
            $airport->update(['is_active' => true]);

            return redirect()
                ->route('airports.index')
                ->with('success', ui_phrase('Airport activated successfully.'));
        }

        $airport->update(['is_active' => false]);
        $airport->delete();

        return redirect()
            ->route('airports.index')
            ->with('success', ui_phrase('Airport deactivated successfully.'));
    }

    private function validatePayload(Request $request, ?Airport $airport): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('airports', 'code')->ignore($airport?->id)],
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'google_maps_url' => ['nullable', 'url', 'max:5000'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'destination_id' => ['nullable', 'integer', 'exists:destinations,id'],
            'country' => ['nullable', 'string', 'max:100'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'notes' => ['nullable', 'string'],
            'cover_file' => ['nullable', 'image'],
            'existing_cover' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active');
        app(LocationResolver::class)->enrichFromGoogleMapsUrl($validated);
        $this->applyDestinationContext($validated);
        app(LocationResolver::class)->resolveDestinationId($validated, true);

        if (empty($validated['location'])) {
            $parts = array_filter([
                trim((string) ($validated['city'] ?? '')),
                trim((string) ($validated['province'] ?? '')),
            ]);
            if ($parts !== []) {
                $validated['location'] = implode(', ', $parts);
            }
        }

        return $validated;
    }

    private function resolveAirportCover(Request $request, ?string $currentCover): array
    {
        $uploaded = $request->file('cover_file');
        $currentNormalized = $this->normalizeAirportCoverPath((string) $currentCover);

        if ($uploaded) {
            $originalPath = $uploaded->store('airports/covers', 'public');
            $processedPath = ImageThumbnailGenerator::processAndGenerate('public', $originalPath, 16, 9, 360, 240) ?? $originalPath;
            $deletePath = $currentNormalized !== '' && ! Str::startsWith($currentNormalized, ['http://', 'https://']) && $currentNormalized !== $processedPath
                ? $currentNormalized
                : '';

            return [
                'cover' => $processedPath,
                'delete' => $deletePath,
            ];
        }

        $existing = $this->normalizeAirportCoverPath((string) $request->input('existing_cover', ''));
        if ($existing !== '') {
            return ['cover' => $existing, 'delete' => ''];
        }

        if ($currentNormalized !== '') {
            return ['cover' => $currentNormalized, 'delete' => ''];
        }

        return ['cover' => '', 'delete' => ''];
    }

    private function normalizeAirportCoverPath(string $path): string
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

        if (! Str::startsWith($path, ['airports/covers/', 'airports/cover/'])) {
            if (! Str::contains($path, '/')) {
                $coversCandidate = 'airports/covers/' . $path;
                $coverCandidate = 'airports/cover/' . $path;
                $path = Storage::disk('public')->exists($coversCandidate)
                    ? $coversCandidate
                    : (Storage::disk('public')->exists($coverCandidate) ? $coverCandidate : $coversCandidate);
            }
        }

        return trim($path);
    }

    private function deleteAirportCovers(array $paths): void
    {
        foreach ($paths as $path) {
            if (! is_string($path) || $path === '') {
                continue;
            }
            Storage::disk('public')->delete($path);
            Storage::disk('public')->delete(ImageThumbnailGenerator::thumbnailPathFor($path));
        }
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
        if (empty($validated['timezone']) && ! empty($destination->timezone)) {
            $validated['timezone'] = (string) $destination->timezone;
        }
    }

    private function buildAirportIndexRows($airports, bool $canManageActivationActions): array
    {
        return $airports->getCollection()->values()->map(function (Airport $airport, int $index) use ($airports, $canManageActivationActions): array {
            $isActive = ! $airport->trashed();
            $city = trim((string) ($airport->city ?? ''));
            $province = trim((string) ($airport->province ?? ''));
            $locationLabel = trim($city . ($city !== '' && $province !== '' ? ', ' : '') . $province);

            return [
                'airport' => $airport,
                'row_number' => (int) ($airports->firstItem() ?? 1) + $index,
                'is_active' => $isActive,
                'country' => $airport->country ?: '-',
                'destination_name' => $airport->destination?->name ?: '-',
                'location_label' => $locationLabel !== '' ? $locationLabel : '-',
                'show_url' => route('airports.show', $airport),
                'edit_url' => route('airports.edit', $airport),
                'toggle_url' => route('airports.toggle-status', $airport->id),
                'toggle_title' => $isActive
                    ? ui_phrase('Deactivate') . ' ' . ui_phrase('Airport')
                    : ui_phrase('Activate') . ' ' . ui_phrase('Airport'),
                'toggle_message' => $isActive ? ui_phrase('confirm deactivate') : ui_phrase('confirm activate'),
                'toggle_impact_items' => [
                    $isActive
                        ? ui_phrase('Airport will be set as inactive and hidden from active options.')
                        : ui_phrase('Airport will be set as active and available for selection.'),
                ],
                'toggle_label' => $isActive ? ui_phrase('Deactivate') : ui_phrase('Activate'),
                'toggle_icon' => $isActive ? 'fa-solid fa-toggle-off w-4' : 'fa-solid fa-toggle-on w-4',
                'toggle_class' => $isActive
                    ? 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-900/20'
                    : 'flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-900/20',
                'can_manage_activation' => $canManageActivationActions,
            ];
        })->all();
    }
}
