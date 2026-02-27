@extends('layouts.master')

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">{{ $transport->name }}</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                    {{ $transport->code }} • {{ ucfirst(str_replace('_', ' ', (string) $transport->transport_type)) }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('transports.edit', $transport) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Edit</a>
                <a href="{{ route('transports.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Back</a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800 xl:col-span-2">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Overview</h2>
                <div class="mt-3 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                    <div><span class="text-gray-500 dark:text-gray-400">Provider:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->provider_name ?: '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Service Scope:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->service_scope ? ucfirst(str_replace('_', ' ', $transport->service_scope)) : '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Location:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->location ?: '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">City/Province:</span> <span class="text-gray-800 dark:text-gray-100">{{ trim(($transport->city ?? '') . (($transport->city && $transport->province) ? ', ' : '') . ($transport->province ?? '')) ?: '-' }}</span></div>
                </div>

                @if ($transport->description)
                    <div class="mt-4">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</h3>
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">{{ $transport->description }}</p>
                    </div>
                @endif

                @if ($transport->inclusions || $transport->exclusions)
                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Inclusions</h3>
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">{{ $transport->inclusions ?: '-' }}</p>
                        </div>
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Exclusions</h3>
                            <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">{{ $transport->exclusions ?: '-' }}</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Contact</h2>
                <div class="mt-3 space-y-2 text-sm">
                    <div><span class="text-gray-500 dark:text-gray-400">PIC:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->contact_name ?: '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Phone:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->contact_phone ?: '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Email:</span> <span class="text-gray-800 dark:text-gray-100">{{ $transport->contact_email ?: '-' }}</span></div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Website:</span>
                        @if ($transport->website)
                            <a href="{{ $transport->website }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">{{ $transport->website }}</a>
                        @else
                            <span class="text-gray-800 dark:text-gray-100">-</span>
                        @endif
                    </div>
                    <div><span class="text-gray-500 dark:text-gray-400">Status:</span> <span class="{{ $transport->is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">{{ $transport->is_active ? 'Active' : 'Inactive' }}</span></div>
                </div>
            </div>
        </div>

        @if (!empty($transport->gallery_images))
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Transport Gallery</h2>
                <div class="mt-3 grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-5">
                    @foreach ($transport->gallery_images as $image)
                        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                            <img
                                src="{{ asset('storage/' . \App\Support\ImageThumbnailGenerator::thumbnailPathFor($image)) }}"
                                onerror="this.onerror=null;this.src='{{ asset('storage/' . $image) }}';"
                                alt="Transport gallery"
                                class="h-28 w-full object-cover">
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Unit Details</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900/40">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Unit</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Capacity</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Contract</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Publish</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Overtime</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Benefits</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($transport->units as $unit)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-3 py-2 text-gray-800 dark:text-gray-100">
                                    <div class="font-medium">{{ $unit->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $unit->vehicle_type ?: '-' }} • {{ $unit->brand_model ?: '-' }} • {{ ucfirst((string) $unit->transmission) }}</div>
                                    @if (!empty($unit->images))
                                        <div class="mt-2 flex flex-wrap gap-2">
                                            @foreach ($unit->images as $image)
                                                <div class="overflow-hidden rounded-md border border-gray-200 dark:border-gray-700">
                                                    <img
                                                        src="{{ asset('storage/' . \App\Support\ImageThumbnailGenerator::thumbnailPathFor($image)) }}"
                                                        onerror="this.onerror=null;this.src='{{ asset('storage/' . $image) }}';"
                                                        alt="Unit image"
                                                        class="h-12 w-16 object-cover">
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $unit->seat_capacity }} seat{{ $unit->seat_capacity > 1 ? 's' : '' }}<br><span class="text-xs text-gray-500 dark:text-gray-400">Luggage: {{ $unit->luggage_capacity ?? '-' }}</span></td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $unit->currency }} {{ number_format((float) $unit->contract_rate, 0) }}</td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $unit->publish_rate !== null ? $unit->currency . ' ' . number_format((float) $unit->publish_rate, 0) : '-' }}</td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $unit->overtime_rate !== null ? $unit->currency . ' ' . number_format((float) $unit->overtime_rate, 0) : '-' }}</td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $unit->benefits ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">No unit data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
