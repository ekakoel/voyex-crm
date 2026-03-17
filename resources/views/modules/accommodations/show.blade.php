@extends('layouts.master')

@section('content')
    <div class="space-y-6">
        @section('page_actions')<a href="{{ route('accommodations.edit', $accommodation) }}"  class="btn-primary">Edit</a>
                <a href="{{ route('accommodations.index') }}"  class="btn-ghost">Back</a>@endsection

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            <div class="app-card p-4 xl:col-span-2">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Overview</h2>
                <div class="mt-3 grid grid-cols-1 gap-3 text-sm md:grid-cols-2">
                    <div><span class="text-gray-500 dark:text-gray-400">Name:</span> <span class="text-gray-800 dark:text-gray-100">{{ $accommodation->name }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Code:</span> <span class="text-gray-800 dark:text-gray-100">{{ $accommodation->code ?: '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Category:</span> <span class="text-gray-800 dark:text-gray-100">{{ $accommodation->category ? ucfirst(str_replace('_', ' ', (string) $accommodation->category)) : '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Star Rating:</span> <span class="text-gray-800 dark:text-gray-100">{{ $accommodation->star_rating ? $accommodation->star_rating.'*' : '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Location:</span> <span class="text-gray-800 dark:text-gray-100">{{ $accommodation->location ?: '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">City/Province:</span> <span class="text-gray-800 dark:text-gray-100">{{ trim(($accommodation->city ?? '') . (($accommodation->city && $accommodation->province) ? ', ' : '') . ($accommodation->province ?? '')) ?: '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Check-in:</span> <span class="text-gray-800 dark:text-gray-100">{{ $accommodation->check_in_time ? substr((string) $accommodation->check_in_time, 0, 5) : '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Check-out:</span> <span class="text-gray-800 dark:text-gray-100">{{ $accommodation->check_out_time ? substr((string) $accommodation->check_out_time, 0, 5) : '-' }}</span></div>
                    <div class="md:col-span-2"><span class="text-gray-500 dark:text-gray-400">Address:</span> <span class="text-gray-800 dark:text-gray-100">{{ $accommodation->address ?: '-' }}</span></div>
                </div>

                @if ($accommodation->description)
                    <div class="mt-4">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Description</h3>
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">{{ $accommodation->description }}</p>
                    </div>
                @endif

                @if ($accommodation->main_facilities)
                    <div class="mt-4">
                        <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Main Facilities</h3>
                        <p class="mt-1 text-sm text-gray-700 dark:text-gray-200">{{ $accommodation->main_facilities }}</p>
                    </div>
                @endif
            </div>

            <div class="app-card p-4">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Contact</h2>
                <div class="mt-3 space-y-2 text-sm">
                    <div><span class="text-gray-500 dark:text-gray-400">PIC:</span> <span class="text-gray-800 dark:text-gray-100">{{ $accommodation->contact_name ?: '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Phone:</span> <span class="text-gray-800 dark:text-gray-100">{{ $accommodation->contact_phone ?: '-' }}</span></div>
                    <div><span class="text-gray-500 dark:text-gray-400">Email:</span> <span class="text-gray-800 dark:text-gray-100">{{ $accommodation->contact_email ?: '-' }}</span></div>
                    <div>
                        <span class="text-gray-500 dark:text-gray-400">Website:</span>
                        @if ($accommodation->website)
                            <a href="{{ $accommodation->website }}" target="_blank" rel="noopener" class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">{{ $accommodation->website }}</a>
                        @else
                            <span class="text-gray-800 dark:text-gray-100">-</span>
                        @endif
                    </div>
                    <div><span class="text-gray-500 dark:text-gray-400">Status:</span> <span class="{{ $accommodation->is_active ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">{{ $accommodation->is_active ? 'Active' : 'Inactive' }}</span></div>
                </div>
            </div>
        </div>

        @if (!empty($accommodation->gallery_images))
            <div class="app-card p-4">
                <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Gallery</h2>
                <div class="mt-3 grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-5">
                    @foreach ($accommodation->gallery_images as $image)
                        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                            <img
                                src="{{ asset('storage/' . \App\Support\ImageThumbnailGenerator::thumbnailPathFor($image)) }}"
                                onerror="this.onerror=null;this.src='{{ asset('storage/' . $image) }}';"
                                alt="Accommodation gallery"
                                class="h-28 w-full object-cover">
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="app-card p-4">
            <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">Room Details</h2>
            <div class="mt-3 overflow-x-auto app-card">
                <table class="app-table w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead>
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Room</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Type</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Max Pax</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Contract</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Publish</th>
                            <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Benefits</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($accommodation->rooms as $room)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                <td class="px-3 py-2 text-gray-800 dark:text-gray-100">
                                    <div>{{ $room->name }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $room->bed_type ?: '-' }} - {{ $room->view_type ?: '-' }}</div>
                                    @if (!empty($room->images))
                                        <div class="mt-2 grid grid-cols-3 gap-1">
                                            @foreach ($room->images as $roomImage)
                                                <div class="overflow-hidden rounded border border-gray-200 dark:border-gray-700">
                                                    <img
                                                        src="{{ asset('storage/' . \App\Support\ImageThumbnailGenerator::thumbnailPathFor($roomImage)) }}"
                                                        onerror="this.onerror=null;this.src='{{ asset('storage/' . $roomImage) }}';"
                                                        alt="Room image"
                                                        class="h-12 w-full object-cover">
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $room->room_type ?: '-' }}</td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $room->max_occupancy }}</td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-200"><x-money :amount="(float) $room->contract_rate" :currency="$room->currency ?? 'IDR'" /></td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $room->publish_rate !== null ? \App\Support\Currency::format((float) $room->publish_rate, $room->currency ?? 'IDR') : '-' }}</td>
                                <td class="px-3 py-2 text-gray-700 dark:text-gray-200">{{ $room->benefits ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-3 py-4 text-center text-gray-500 dark:text-gray-400">No room data.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection



