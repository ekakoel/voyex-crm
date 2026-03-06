@extends('layouts.master')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Destinations</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Master data destinasi untuk grouping vendor, accommodation, attraction, airport, dan transport.</p>
            </div>
            <a href="{{ route('destinations.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Add Destination</a>
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">{{ session('success') }}</div>
        @endif

        <form method="GET" class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 md:grid-cols-3">
            <input name="q" value="{{ request('q') }}" placeholder="Search code/name/city/province" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            <div class="md:col-span-2 flex items-center gap-2">
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Filter</button>
                <a href="{{ route('destinations.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900/40">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Destination</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">City / Province</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Linked Data</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($destinations as $destination)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $destination->code }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                <div>{{ $destination->province ?: $destination->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $destination->slug }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ trim(($destination->city ?? '') . (($destination->city && $destination->province) ? ', ' : '') . ($destination->province ?? '')) ?: '-' }}</td>
                            <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-200">
                                V: {{ (int) ($destination->vendors_count ?? 0) }} |
                                A: {{ (int) ($destination->accommodations_count ?? 0) }} |
                                TA: {{ (int) ($destination->tourist_attractions_count ?? 0) }} |
                                AP: {{ (int) ($destination->airports_count ?? 0) }} |
                                TR: {{ (int) ($destination->transports_count ?? 0) }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('destinations.show', $destination) }}" class="mr-3 font-medium text-slate-600 hover:text-slate-700 dark:text-slate-300">View</a>
                                <a href="{{ route('destinations.edit', $destination) }}" class="mr-3 font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Edit</a>
                                <form action="{{ route('destinations.destroy', $destination) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Delete this destination?')" class="font-medium text-rose-600 hover:text-rose-700 dark:text-rose-400">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No destinations available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $destinations->links() }}</div>
    </div>
@endsection
