@extends('layouts.master')
@section('page_title', 'Food & Beverage')
@section('page_subtitle', 'Manage food & beverage data.')
@section('page_actions')
    <a href="{{ route('food-beverages.create') }}" class="btn-primary">Add F&B</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--food-beverages" data-service-filter-page data-page-spinner="off">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="module-grid-3-9">
            <aside class="module-grid-side space-y-4">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Filters</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Refine your list quickly.</p>
                    </div>
                    <form method="GET" action="{{ route('food-beverages.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-service-filter-form data-disable-submit-lock="1" data-page-spinner="off">
                        <select name="vendor_id" class="app-input" data-service-filter-input>
                            <option value="">All Vendors</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                        <select name="service_type" class="app-input" data-service-filter-input>
                            <option value="">All Types</option>
                            @foreach ($types as $type)
                                <option value="{{ $type['value'] }}" @selected((string) request('service_type') === (string) $type['value'])>{{ $type['label'] }}</option>
                            @endforeach
                        </select>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ([10,25,50,100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ $size }}/page</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <a href="{{ route('food-beverages.index') }}" class="btn-ghost" data-service-filter-reset>Reset</a>
                        </div>
                    </form>
                </div>
            </aside>
            <div class="module-grid-main space-y-4" data-service-filter-results>
        @if (session('success'))
            <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
            <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Service</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Duration</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Rate / Pax</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($foodBeverages as $index => $foodBeverage)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            @php($isActive = ! $foodBeverage->trashed())
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ++$index }}</td>
                            <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                <div>{{ $foodBeverage->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $foodBeverage->vendor->name ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ ucwords(str_replace('_', ' ', (string) $foodBeverage->service_type)) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ $foodBeverage->duration_minutes }} min</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                <div>
                                    Contract: <x-money :amount="(float) ($foodBeverage->contract_rate ?? 0)" currency="IDR" />
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Markup:
                                    {{ ($foodBeverage->markup_type ?? 'fixed') === 'percent'
                                        ? rtrim(rtrim(number_format((float) ($foodBeverage->markup ?? 0), 2, '.', ''), '0'), '.') . '%'
                                        : \App\Support\Currency::format((float) ($foodBeverage->markup ?? 0), 'IDR') }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    Publish: <x-money :amount="(float) ($foodBeverage->publish_rate ?? 0)" currency="IDR" />
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center text-sm">
                                <x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                            </td>
                            <td class="px-4 py-3 text-right text-sm actions-compact">
    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('food-beverages.create', ['copy' => $foodBeverage->id]) }}" class="btn-outline-sm" title="Copy" aria-label="Copy"><i class="fa-regular fa-copy"></i><span class="sr-only">Copy</span></a>
        <a href="{{ route('food-beverages.edit', $foodBeverage) }}"  class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                                <form action="{{ route('food-beverages.toggle-status', $foodBeverage->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" onclick="return confirm('{{ $isActive ? 'Deactivate this F&B service?' : 'Activate this F&B service?' }}')" class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }}">{{ $isActive ? 'Deactivate' : 'Activate' }}</button>
                                </form>
    </div>
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No F&B service available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        <div class="md:hidden space-y-3">
            @forelse ($foodBeverages as $foodBeverage)
                <div class="app-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $foodBeverage->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $foodBeverage->vendor->name ?? '-' }}</p>
                        </div>
                        <span class="text-xs font-medium rounded-full bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-900/40 dark:text-gray-300">{{ ucwords(str_replace('_', ' ', (string) $foodBeverage->service_type)) }}</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <div>Duration</div>
                        <div>{{ $foodBeverage->duration_minutes }} min</div>
                        <div>Rate</div>
                        <div>
                            <div>Contract: <x-money :amount="(float) ($foodBeverage->contract_rate ?? 0)" currency="IDR" /></div>
                            <div class="text-gray-500 dark:text-gray-400">
                                Markup:
                                {{ ($foodBeverage->markup_type ?? 'fixed') === 'percent'
                                    ? rtrim(rtrim(number_format((float) ($foodBeverage->markup ?? 0), 2, '.', ''), '0'), '.') . '%'
                                    : \App\Support\Currency::format((float) ($foodBeverage->markup ?? 0), 'IDR') }}
                            </div>
                            <div class="text-gray-500 dark:text-gray-400">Publish: <x-money :amount="(float) ($foodBeverage->publish_rate ?? 0)" currency="IDR" /></div>
                        </div>
                        <div>Status</div>
                        <div><x-status-badge :status="$foodBeverage->trashed() ? 'inactive' : 'active'" size="xs" /></div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('food-beverages.create', ['copy' => $foodBeverage->id]) }}" class="btn-outline-sm" title="Copy" aria-label="Copy"><i class="fa-regular fa-copy"></i><span class="sr-only">Copy</span></a>
                        <a href="{{ route('food-beverages.edit', $foodBeverage) }}" class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                        <form action="{{ route('food-beverages.toggle-status', $foodBeverage->id) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" onclick="return confirm('{{ $foodBeverage->trashed() ? 'Activate this F&B service?' : 'Deactivate this F&B service?' }}')" class="{{ $foodBeverage->trashed() ? 'btn-primary-sm' : 'btn-muted-sm' }}">{{ $foodBeverage->trashed() ? 'Activate' : 'Deactivate' }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">No F&B service available.</div>
            @endforelse
        </div>
        <div>{{ $foodBeverages->links() }}</div>
            </div>
        </div>
</div>
@endsection





