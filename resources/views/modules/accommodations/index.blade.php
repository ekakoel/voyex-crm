@extends('layouts.master')

@section('content')
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">Accommodations</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Manage hotel/villa/resort partners and contract room rates.</p>
            </div>
            <a href="{{ route('accommodations.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Add Accommodation</a>
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">{{ session('success') }}</div>
        @endif

        <form method="GET" class="grid grid-cols-1 gap-3 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800 md:grid-cols-4">
            <input name="q" value="{{ request('q') }}" placeholder="Search code/name/city/province" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
            <select name="category" class="rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100">
                <option value="">All Categories</option>
                @foreach ($categories as $category)
                    <option value="{{ $category }}" @selected((string) request('category') === (string) $category)>{{ ucfirst($category) }}</option>
                @endforeach
            </select>
            <div class="md:col-span-2 flex items-center gap-2">
                <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Filter</button>
                <a href="{{ route('accommodations.index') }}" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Reset</a>
            </div>
        </form>

        <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/40">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Code</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Accommodation</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Location</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Rooms</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Min Contract Rate</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($accommodations as $accommodation)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ $accommodation->code }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                <div>{{ $accommodation->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    @if ($accommodation->star_rating)
                                        {{ $accommodation->star_rating }}★
                                    @else
                                        -
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ ucfirst($accommodation->category) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ trim(($accommodation->city ?? '') . (($accommodation->city && $accommodation->province) ? ', ' : '') . ($accommodation->province ?? '')) ?: '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $accommodation->rooms_count }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                {{ $accommodation->rooms_min_contract_rate !== null ? number_format((float) $accommodation->rooms_min_contract_rate, 2) : '-' }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('accommodations.show', $accommodation) }}" class="mr-3 font-medium text-slate-600 hover:text-slate-700 dark:text-slate-300">View</a>
                                <a href="{{ route('accommodations.edit', $accommodation) }}" class="mr-3 font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Edit</a>
                                <form action="{{ route('accommodations.destroy', $accommodation) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Delete this accommodation?')" class="font-medium text-rose-600 hover:text-rose-700 dark:text-rose-400">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No accommodations available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $accommodations->links() }}</div>
    </div>
@endsection
