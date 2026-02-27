<?php

namespace App\Http\Controllers\Director;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanySettingController extends Controller
{
    public function edit()
    {
        $settings = CompanySetting::query()->firstOrCreate([], [
            'company_name' => 'VOYEX CRM',
        ]);

        return view('modules/company-settings/edit', compact('settings'));
    }

    public function update(Request $request)
    {
        $settings = CompanySetting::query()->firstOrCreate([], [
            'company_name' => 'VOYEX CRM',
        ]);

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:120'],
            'legal_name' => ['nullable', 'string', 'max:180'],
            'contact_email' => ['nullable', 'email', 'max:120'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'contact_whatsapp' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'url', 'max:500'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:120'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'currency' => ['nullable', 'string', 'size:3'],
            'footer_note' => ['nullable', 'string'],
            'favicon' => ['nullable', 'image', 'mimes:png,ico,webp,jpg,jpeg', 'max:2048'],
            'logo' => ['nullable', 'image', 'mimes:png,webp,jpg,jpeg', 'max:4096'],
        ]);

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

        $validated['currency'] = isset($validated['currency']) && $validated['currency'] !== ''
            ? strtoupper($validated['currency'])
            : null;

        unset($validated['favicon'], $validated['logo']);
        $settings->update($validated);

        return redirect()->route('company-settings.edit')->with('success', 'Company settings updated successfully.');
    }
}
