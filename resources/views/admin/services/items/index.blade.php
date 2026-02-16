@extends('layouts.master')

@php
    $routePrefix = match($serviceType) {
        'accommodations' => 'admin.services.items.accommodations',
        'transports' => 'admin.services.items.transports',
        'guides' => 'admin.services.items.guides',
        'attractions' => 'admin.services.items.attractions',
        'travel_activities' => 'admin.services.items.travel-activities',
        default => 'admin.services.items.accommodations',
    };
@endphp

@section('content')
    <div class="space-y-6">
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($typeLabels as $key => $label)
                @php
                    $tabRoute = match($key) {
                        'accommodations' => 'admin.services.items.accommodations.index',
                        'transports' => 'admin.services.items.transports.index',
                        'guides' => 'admin.services.items.guides.index',
                        'attractions' => 'admin.services.items.attractions.index',
                        'travel_activities' => 'admin.services.items.travel-activities.index',
                    };
                @endphp
                <a href="{{ route($tabRoute) }}"
                   class="rounded-lg px-3 py-2 text-sm font-medium {{ $serviceType === $key ? 'bg-indigo-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-100">{{ $serviceTypeLabel }}</h1>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">Manage {{ strtolower($serviceTypeLabel) }} services.</p>
            </div>
            <a href="{{ route($routePrefix.'.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Add {{ $serviceTypeLabel }}</a>
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">{{ session('success') }}</div>
        @endif

        <div class="hidden md:block overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead class="bg-gray-50 dark:bg-gray-900/40">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Vendor</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Price</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($services as $service)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">{{ $service->name }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $service->vendor->name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">Rp {{ number_format($service->unit_price ?? 0, 2, ',', '.') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $service->is_active ? 'Active' : 'Inactive' }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route($routePrefix.'.edit', $service) }}" class="mr-3 font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Edit</a>
                                <form action="{{ route($routePrefix.'.destroy', $service) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" onclick="return confirm('Are you sure you want to delete this service?')" class="font-medium text-rose-600 hover:text-rose-700 dark:text-rose-400">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No services available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $services->links() }}</div>
    </div>
@endsection
