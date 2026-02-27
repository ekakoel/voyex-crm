<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Accommodation;
use App\Support\ImageThumbnailGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AccommodationController extends Controller
{
    public function index(Request $request)
    {
        $query = Accommodation::query()
            ->withCount('rooms')
            ->withMin('rooms', 'contract_rate')
            ->latest('id');

        if ($request->filled('q')) {
            $term = (string) $request->string('q');
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('city', 'like', "%{$term}%")
                    ->orWhere('province', 'like', "%{$term}%");
            });
        }

        if ($request->filled('category')) {
            $query->where('category', (string) $request->string('category'));
        }

        $accommodations = $query->paginate(10)->withQueryString();
        $categories = Accommodation::query()->select('category')->distinct()->orderBy('category')->pluck('category');

        return view('modules.accommodations.index', compact('accommodations', 'categories'));
    }

    public function create()
    {
        return view('modules.accommodations.create');
    }

    public function show(Accommodation $accommodation)
    {
        $accommodation->load([
            'rooms' => function ($query) {
                $query->orderBy('contract_rate')->orderBy('name');
            },
        ]);

        return view('modules.accommodations.show', compact('accommodation'));
    }

    public function store(Request $request)
    {
        [$validated, $roomPayload] = $this->validatePayload($request, null);
        $validated['gallery_images'] = $this->storeGalleryImages($request->file('gallery_images', []), 'accommodations');

        DB::transaction(function () use ($validated, $roomPayload) {
            $accommodation = Accommodation::query()->create($validated);
            $accommodation->rooms()->createMany($roomPayload);
        });

        return redirect()->route('accommodations.index')->with('success', 'Accommodation created successfully.');
    }

    public function edit(Accommodation $accommodation)
    {
        $accommodation->load('rooms');
        return view('modules.accommodations.edit', compact('accommodation'));
    }

    public function update(Request $request, Accommodation $accommodation)
    {
        [$validated, $roomPayload] = $this->validatePayload($request, $accommodation);
        if ($request->hasFile('gallery_images')) {
            $this->deleteGalleryImages($accommodation->gallery_images ?? []);
            $validated['gallery_images'] = $this->storeGalleryImages($request->file('gallery_images', []), 'accommodations');
        } else {
            $validated['gallery_images'] = $accommodation->gallery_images ?? [];
        }

        DB::transaction(function () use ($accommodation, $validated, $roomPayload) {
            $accommodation->update($validated);
            $accommodation->rooms()->delete();
            $accommodation->rooms()->createMany($roomPayload);
        });

        return redirect()->route('accommodations.index')->with('success', 'Accommodation updated successfully.');
    }

    public function destroy(Accommodation $accommodation)
    {
        $this->deleteGalleryImages($accommodation->gallery_images ?? []);
        $accommodation->delete();

        return redirect()->route('accommodations.index')->with('success', 'Accommodation deleted successfully.');
    }

    private function validatePayload(Request $request, ?Accommodation $accommodation): array
    {
        $hasExistingGallery = $accommodation && is_array($accommodation->gallery_images) && count($accommodation->gallery_images) > 0;
        $galleryRules = ['array', 'max:5'];
        if (! $hasExistingGallery) {
            array_unshift($galleryRules, 'required', 'min:1');
        } elseif ($request->hasFile('gallery_images')) {
            array_unshift($galleryRules, 'min:1');
        } else {
            array_unshift($galleryRules, 'sometimes');
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:30', Rule::unique('accommodations', 'code')->ignore($accommodation?->id)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:50'],
            'star_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'location' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'check_in_time' => ['nullable', 'date_format:H:i'],
            'check_out_time' => ['nullable', 'date_format:H:i'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_email' => ['nullable', 'email', 'max:120'],
            'website' => ['nullable', 'url', 'max:500'],
            'main_facilities' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'cancellation_policy' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'gallery_images' => $galleryRules,
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'is_active' => ['nullable', 'boolean'],

            'rooms' => ['required', 'array', 'min:1'],
            'rooms.*.name' => ['required', 'string', 'max:120'],
            'rooms.*.room_type' => ['nullable', 'string', 'max:80'],
            'rooms.*.bed_type' => ['nullable', 'string', 'max:80'],
            'rooms.*.view_type' => ['nullable', 'string', 'max:80'],
            'rooms.*.max_occupancy' => ['required', 'integer', 'min:1', 'max:20'],
            'rooms.*.room_size_sqm' => ['nullable', 'numeric', 'min:1'],
            'rooms.*.contract_rate' => ['required', 'numeric', 'min:0'],
            'rooms.*.publish_rate' => ['nullable', 'numeric', 'min:0'],
            'rooms.*.currency' => ['required', 'string', 'size:3'],
            'rooms.*.meal_plan' => ['nullable', 'string', 'max:80'],
            'rooms.*.amenities' => ['nullable', 'string'],
            'rooms.*.benefits' => ['nullable', 'string'],
            'rooms.*.is_refundable' => ['nullable', 'boolean'],
            'rooms.*.quantity_available' => ['nullable', 'integer', 'min:0'],
            'rooms.*.cancellation_policy' => ['nullable', 'string'],
            'rooms.*.notes' => ['nullable', 'string'],
            'rooms.*.is_active' => ['nullable', 'boolean'],
        ]);

        $validated['code'] = strtoupper(trim($validated['code']));
        $validated['is_active'] = $request->boolean('is_active');

        $roomPayload = [];
        foreach (array_values($validated['rooms']) as $row) {
            $roomPayload[] = [
                'name' => trim((string) $row['name']),
                'room_type' => $row['room_type'] ?? null,
                'bed_type' => $row['bed_type'] ?? null,
                'view_type' => $row['view_type'] ?? null,
                'max_occupancy' => (int) ($row['max_occupancy'] ?? 2),
                'room_size_sqm' => ($row['room_size_sqm'] ?? '') !== '' ? $row['room_size_sqm'] : null,
                'contract_rate' => $row['contract_rate'],
                'publish_rate' => ($row['publish_rate'] ?? '') !== '' ? $row['publish_rate'] : null,
                'currency' => strtoupper((string) ($row['currency'] ?? 'IDR')),
                'meal_plan' => $row['meal_plan'] ?? null,
                'amenities' => $row['amenities'] ?? null,
                'benefits' => $row['benefits'] ?? null,
                'is_refundable' => filter_var($row['is_refundable'] ?? false, FILTER_VALIDATE_BOOL),
                'quantity_available' => ($row['quantity_available'] ?? '') !== '' ? (int) $row['quantity_available'] : null,
                'cancellation_policy' => $row['cancellation_policy'] ?? null,
                'notes' => $row['notes'] ?? null,
                'is_active' => filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOL),
            ];
        }

        unset($validated['rooms']);

        return [$validated, $roomPayload];
    }

    private function storeGalleryImages(array $files, string $directory): array
    {
        $stored = [];
        foreach ($files as $file) {
            if (! $file) {
                continue;
            }
            $originalPath = $file->store($directory, 'public');
            $stored[] = $originalPath;
            ImageThumbnailGenerator::generate('public', $originalPath);
        }

        return $stored;
    }

    private function deleteGalleryImages(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_string($path) && $path !== '') {
                Storage::disk('public')->delete($path);
                Storage::disk('public')->delete(ImageThumbnailGenerator::thumbnailPathFor($path));
            }
        }
    }
}
