<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\NormalizesDisplayCurrencyToIdr;
use App\Http\Controllers\Controller;
use App\Models\Destination;
use App\Models\Transport;
use App\Models\Vendor;
use App\Support\ImageThumbnailGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TransportController extends Controller
{
    use NormalizesDisplayCurrencyToIdr;

    public function index(Request $request)
    {
        $query = Transport::query()
            ->withTrashed()
            ->with(['vendor:id,name'])
            ->latest('id');

        if ($request->filled('q')) {
            $term = (string) $request->string('q');
            $query->where(function ($q) use ($term) {
                $q->where('code', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%")
                    ->orWhere('brand_model', 'like', "%{$term}%")
                    ->orWhereHas('vendor', fn ($vendor) => $vendor->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', (int) $request->integer('vendor_id'));
        }

        if ($request->filled('transport_type')) {
            $query->where('transport_type', (string) $request->string('transport_type'));
        }

        $perPage = (int) $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
        $transports = $query->paginate($perPage)->withQueryString();
        $types = Transport::query()->select('transport_type')->whereNotNull('transport_type')->distinct()->orderBy('transport_type')->pluck('transport_type');
        $vendors = Vendor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);

        return view('modules.transports.index', compact('transports', 'types', 'vendors'));
    }

    public function create()
    {
        $vendors = Vendor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province', 'destination_id']);
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);

        return view('modules.transports.create', compact('vendors', 'destinations'));
    }

    public function show(Transport $transport)
    {
        $transport->load('vendor:id,name,contact_name,contact_phone,contact_email,website,location,address,city,province,country,timezone');

        return view('modules.transports.show', compact('transport'));
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request, null);
        $validated['images'] = $this->storeImages($request->file('images', []));

        Transport::query()->create($validated);

        return redirect()->route('transports.index')->with('success', 'Transport created successfully.');
    }

    public function edit(Transport $transport)
    {
        $vendors = Vendor::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province', 'destination_id']);
        $destinations = Destination::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'city', 'province']);

        return view('modules.transports.edit', compact('transport', 'vendors', 'destinations'));
    }

    public function update(Request $request, Transport $transport)
    {
        $validated = $this->validatePayload($request, $transport);

        $existingImages = $this->normalizeImages($transport->images ?? []);
        $requestedRemoved = (array) $request->input('removed_images', []);
        $removed = array_values(array_intersect($existingImages, $requestedRemoved));
        $remaining = array_values(array_diff($existingImages, $removed));
        if ($removed !== []) {
            $this->deleteImages($removed);
        }

        $newImages = $this->storeImages($request->file('images', []));
        $validated['images'] = array_slice(array_values(array_unique(array_merge($remaining, $newImages))), 0, 2);

        $transport->update($validated);

        return redirect()->route('transports.index')->with('success', 'Transport updated successfully.');
    }

    public function destroy(Transport $transport)
    {
        $this->deleteImages($transport->images ?? []);
        $transport->delete();

        return redirect()->route('transports.index')->with('success', 'Transport deactivated successfully.');
    }

    public function toggleStatus($transport)
    {
        $transport = Transport::withTrashed()->findOrFail($transport);
        if ($transport->trashed()) {
            $transport->restore();
            $transport->update(['is_active' => true]);

            return redirect()->route('transports.index')->with('success', 'Transport activated successfully.');
        }

        $transport->update(['is_active' => false]);
        $transport->delete();

        return redirect()->route('transports.index')->with('success', 'Transport deactivated successfully.');
    }

    public function removeGalleryImage(Request $request, Transport $transport)
    {
        $validated = $request->validate([
            'image' => ['required', 'string'],
        ]);

        $image = (string) $validated['image'];
        $images = $this->normalizeImages($transport->images ?? []);
        if (! in_array($image, $images, true)) {
            return response()->json(['message' => 'Image not found.'], 404);
        }

        $remaining = array_values(array_diff($images, [$image]));
        $this->deleteImages([$image]);
        $transport->update(['images' => $remaining]);

        return response()->json([
            'message' => 'Image removed successfully.',
            'remaining_count' => count($remaining),
        ]);
    }

    private function validatePayload(Request $request, ?Transport $transport): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'transport_type' => ['required', 'string', 'max:50'],
            'vendor_id' => ['required', 'integer', 'exists:vendors,id'],

            'brand_model' => ['nullable', 'string', 'max:120'],
            'seat_capacity' => ['required', 'integer', 'min:1', 'max:80'],
            'luggage_capacity' => ['nullable', 'integer', 'min:0'],
            'contract_rate' => ['required', 'numeric', 'min:0'],
            'markup_type' => ['nullable', 'in:fixed,percent'],
            'markup' => ['nullable', 'numeric', 'min:0'],
            'publish_rate' => ['nullable', 'numeric', 'min:0'],
            'overtime_rate' => ['nullable', 'numeric', 'min:0'],
            'fuel_type' => ['nullable', 'string', 'max:60'],
            'transmission' => ['nullable', 'string', 'max:40'],
            'air_conditioned' => ['nullable', 'boolean'],
            'with_driver' => ['nullable', 'boolean'],

            'description' => ['nullable', 'string'],
            'inclusions' => ['nullable', 'string'],
            'exclusions' => ['nullable', 'string'],
            'cancellation_policy' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],

            'images' => ['nullable', 'array', 'max:2'],
            'images.*' => ['image'],
            'removed_images' => ['nullable', 'array'],
            'removed_images.*' => ['string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['air_conditioned'] = $request->boolean('air_conditioned', true);
        $validated['with_driver'] = $request->boolean('with_driver', true);
        $validated['markup_type'] = (($validated['markup_type'] ?? 'fixed') === 'percent') ? 'percent' : 'fixed';
        $validated['contract_rate'] = max(0, (float) ($validated['contract_rate'] ?? 0));
        $validated['markup'] = max(0, (float) ($validated['markup'] ?? 0));
        $validated['overtime_rate'] = max(0, (float) ($validated['overtime_rate'] ?? 0));

        // Persist service pricing in canonical IDR.
        $validated['contract_rate'] = $this->displayCurrencyToIdr($validated['contract_rate']);
        if ($validated['markup_type'] === 'fixed') {
            $validated['markup'] = $this->displayCurrencyToIdr($validated['markup']);
        }
        $validated['overtime_rate'] = $this->displayCurrencyToIdr($validated['overtime_rate']);

        if ($validated['markup_type'] === 'percent' && $validated['markup'] > 100) {
            throw ValidationException::withMessages([
                'markup' => 'Markup percent cannot be greater than 100.',
            ]);
        }

        $validated['publish_rate'] = round($this->calculatePublishRate(
            $validated['contract_rate'],
            $validated['markup_type'],
            $validated['markup']
        ), 0);
        $validated['contract_rate'] = round($validated['contract_rate'], 0);
        $validated['markup'] = round($validated['markup'], 0);

        unset($validated['removed_images']);

        return $validated;
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

    /**
     * @param  mixed  $images
     * @return array<int, string>
     */
    private function normalizeImages($images): array
    {
        if (is_string($images)) {
            $decoded = json_decode($images, true);
            $images = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($images)) {
            return [];
        }

        return array_values(array_filter($images, function ($path) {
            return is_string($path) && trim($path) !== '';
        }));
    }

    private function storeImages(array $files): array
    {
        $stored = [];
        foreach ($files as $file) {
            if (! $file) {
                continue;
            }
            $originalPath = $file->store('transports/units', 'public');
            $processedPath = ImageThumbnailGenerator::processAndGenerate('public', $originalPath, 3, 2, 360, 240) ?? $originalPath;
            $stored[] = $processedPath;
        }

        return $stored;
    }

    private function deleteImages(array $paths): void
    {
        foreach ($paths as $path) {
            if (is_string($path) && $path !== '') {
                Storage::disk('public')->delete($path);
                Storage::disk('public')->delete(ImageThumbnailGenerator::thumbnailPathFor($path));
            }
        }
    }
}
