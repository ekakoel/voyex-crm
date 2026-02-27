@extends('layouts.master')

@section('content')
    <div class="max-w-5xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Company Settings</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Configure company identity, contact details, and branding for the system.</p>
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <form method="POST" action="{{ route('company-settings.update') }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PATCH')

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Company Name *</label>
                        <input name="company_name" value="{{ old('company_name', $settings->company_name) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100" required>
                        @error('company_name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Legal Name</label>
                        <input name="legal_name" value="{{ old('legal_name', $settings->legal_name) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Email</label>
                        <input type="email" name="contact_email" value="{{ old('contact_email', $settings->contact_email) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Contact Phone</label>
                        <input name="contact_phone" value="{{ old('contact_phone', $settings->contact_phone) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">WhatsApp</label>
                        <input name="contact_whatsapp" value="{{ old('contact_whatsapp', $settings->contact_whatsapp) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Address</label>
                        <input name="address" value="{{ old('address', $settings->address) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Website</label>
                        <input type="url" name="website" value="{{ old('website', $settings->website) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">City</label>
                        <input name="city" value="{{ old('city', $settings->city) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Country</label>
                        <input name="country" value="{{ old('country', $settings->country) }}" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Timezone</label>
                            <input name="timezone" value="{{ old('timezone', $settings->timezone) }}" placeholder="Asia/Jakarta" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Currency</label>
                            <input name="currency" value="{{ old('currency', $settings->currency) }}" maxlength="3" placeholder="IDR" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm uppercase dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Footer Note</label>
                    <textarea name="footer_note" rows="3" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">{{ old('footer_note', $settings->footer_note) }}</textarea>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Favicon</label>
                        <input type="file" name="favicon" accept="image/png,image/x-icon,image/webp,image/jpeg" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        @error('favicon') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        @if ($settings->favicon_path)
                            <div class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <img src="{{ asset('storage/' . $settings->favicon_path) }}" alt="Favicon" class="h-6 w-6 rounded border border-gray-200 dark:border-gray-700">
                                <span>Current favicon</span>
                            </div>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">Company Logo</label>
                        <input type="file" name="logo" accept="image/png,image/webp,image/jpeg" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                        @error('logo') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        @if ($settings->logo_path)
                            <div class="mt-2 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                                <img src="{{ asset('storage/' . $settings->logo_path) }}" alt="Logo" class="h-8 w-8 rounded border border-gray-200 dark:border-gray-700 object-cover">
                                <span>Current logo</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
@endsection
