<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\Destination;
use App\Support\CompanySettingsCache;
use App\Support\LocationResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanySettingController extends Controller
{
    public function edit()
    {
        $settings = CompanySetting::query()->firstOrCreate([], [
            'company_name' => 'VOYEX CRM',
            'tagline' => 'Smart Travel CRM Platform',
        ]);

        $destinations = Destination::query()
            ->orderBy('province')
            ->orderBy('name')
            ->get(['id', 'name', 'province']);

        return view('modules/company-settings/edit', compact('settings', 'destinations'));
    }

    public function update(Request $request)
    {
        $settings = CompanySetting::query()->firstOrCreate([], [
            'company_name' => 'VOYEX CRM',
            'tagline' => 'Smart Travel CRM Platform',
        ]);

        $locationResolver = app(LocationResolver::class);
        $prefilled = $request->only([
            'google_maps_url', 'city', 'province', 'country', 'address', 'latitude', 'longitude', 'timezone', 'destination_id',
        ]);
        $locationResolver->enrichFromGoogleMapsUrl($prefilled);
        $this->applyDestinationContext($prefilled);

        $request->merge($prefilled);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'tagline' => ['nullable', 'string', 'max:180'],
            'legal_name' => ['nullable', 'string', 'max:180'],
            'contact_email' => ['nullable', 'email', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_whatsapp' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'url', 'max:500'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'destination_id' => ['nullable', 'integer', 'exists:destinations,id'],
            'google_maps_url' => ['nullable', 'url', 'max:5000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'footer_note' => ['nullable', 'string'],
            'auth_primary_color' => ['nullable', 'regex:/^#([0-9a-fA-F]{6})$/'],
            'auth_primary_hover_color' => ['nullable', 'regex:/^#([0-9a-fA-F]{6})$/'],
            'auth_background_from_color' => ['nullable', 'regex:/^#([0-9a-fA-F]{6})$/'],
            'auth_background_to_color' => ['nullable', 'regex:/^#([0-9a-fA-F]{6})$/'],
            'auth_card_background_color' => ['nullable', 'regex:/^#([0-9a-fA-F]{6})$/'],
            'auth_card_border_color' => ['nullable', 'regex:/^#([0-9a-fA-F]{6})$/'],
            'favicon' => ['nullable', 'image', 'mimes:png,ico,webp,jpg,jpeg'],
            'logo' => ['nullable', 'image', 'mimes:png,webp,jpg,jpeg'],
        ]);

        $locationResolver->enrichFromGoogleMapsUrl($validated);
        $this->applyDestinationContext($validated);
        $locationResolver->resolveDestinationId($validated, true);

        if ($request->hasFile('favicon')) {
            if ($settings->favicon_path) {
                Storage::disk('public')->delete($settings->favicon_path);
            }
            $validated['favicon_path'] = $request->file('favicon')->store('company', 'public');
        }
        if ($request->hasFile('logo')) {
            if ($settings->logo_path) {
                Storage::disk('public')->delete($settings->logo_path);
            }
            $validated['logo_path'] = $request->file('logo')->store('company', 'public');
        }

        $this->normalizeColorInputs($validated);

        unset($validated['favicon'], $validated['logo']);
        $settings->update($validated);
        CompanySettingsCache::flush();

        return redirect()->route('company-settings.edit')->with('success', 'Company settings updated successfully.');
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

    private function normalizeColorInputs(array &$validated): void
    {
        foreach ([
            'auth_primary_color',
            'auth_primary_hover_color',
            'auth_background_from_color',
            'auth_background_to_color',
            'auth_card_background_color',
            'auth_card_border_color',
        ] as $key) {
            if (! array_key_exists($key, $validated)) {
                continue;
            }

            $value = trim((string) ($validated[$key] ?? ''));
            $validated[$key] = $value !== '' ? strtolower($value) : null;
        }
    }
}

