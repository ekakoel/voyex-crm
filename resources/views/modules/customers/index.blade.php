@extends('layouts.master')
@section('page_title', 'Customers')
@section('page_subtitle', 'Manage customer data.')
@section('page_actions')
    <a href="{{ route('customers.import') }}" class="btn-outline">Import CSV</a>
    <a href="{{ route('customers.create') }}" class="btn-primary">
        Add Customer
    </a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--customers">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
            <aside class="space-y-4 xl:col-span-3">
                <div class="app-card p-5 space-y-4 mb-6">
                    <div class="text-left">
                        <div class="text-[11px] uppercase tracking-widest text-slate-400 dark:text-slate-500">Countries</div>
                    </div>
                    @php($maxCountryTotal = max(1, (int) ($topCountries->max('total') ?? 1)))
                    <div class="space-y-2">
                        @forelse ($topCountries as $country)
                            @php($ratio = min(100, (int) round(($country->total / $maxCountryTotal) * 100)))
                            <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                                <div class="flex items-center justify-between text-xs">
                                    <div class="font-medium text-slate-700 dark:text-slate-200">{{ $country->country }}</div>
                                    <div class="text-slate-500 dark:text-slate-400">{{ $country->total }}</div>
                                </div>
                                <div class="mt-2 h-1.5 rounded-full bg-slate-100 dark:bg-slate-700">
                                    <div class="h-1.5 rounded-full bg-teal-500" style="width: {{ $ratio }}%"></div>
                                </div>
                            </div>
                        @empty
                            <div class="text-sm text-slate-500 dark:text-slate-400">No country data yet.</div>
                        @endforelse
                    </div>
                </div>
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">Filters</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Refine your customer list quickly.</p>
                    </div>
                    <form method="GET" class="grid grid-cols-1 gap-3">
                        <input name="q" value="{{ request('q') }}"
                            placeholder="Search name / code / email / phone / company / country" class="app-input">
                        <select name="customer_type" class="app-input">
                            <option value="">Type</option>
                            @foreach (['individual' => 'Individual', 'company' => 'Company'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('customer_type') === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <x-forms.searchable-select name="country" :options="$countries" :value="request('country')"
                            list-id="country-filter-options" placeholder="Country" />
                        <select name="created_by" class="app-input">
                            <option value="">Creator</option>
                            @foreach ($creators as $creator)
                                <option value="{{ $creator->id }}" @selected((string) request('created_by') === (string) $creator->id)>{{ $creator->name }}
                                </option>
                            @endforeach
                        </select>
                        <select name="per_page" class="app-input">
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ $size }}/page
                                </option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 filter-actions">
                            <button class="btn-primary">Filter</button>
                            <a href="{{ route('customers.index') }}" class="btn-ghost">Reset</a>
                        </div>
                    </form>
                </div>
            </aside>
            <div class="space-y-4 xl:col-span-9">
                @if (session('success'))
                    <div
                        class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div
                        class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                        {{ session('error') }}
                    </div>
                @endif
                <div class="md:hidden space-y-3">
                    @forelse ($customers as $customer)
                        <div class="app-card p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $customer->name }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $customer->code }} -
                                        {{ $customer->email ?? '-' }}</p>
                                </div>
                                <span
                                    class="text-xs font-medium rounded-full bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-900/40 dark:text-gray-300">{{ $customer->customer_type }}</span>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <div>Phone</div>
                                <div>{{ $customer->phone ?? '-' }}</div>
                                <div>Country</div>
                                <div>{{ $customer->country ?? '-' }}</div>
                                <div>Company</div>
                                <div>{{ $customer->company_name ?? '-' }}</div>
                                <div>Status</div>
                                <div>{{ $customer->trashed() ? 'Inactive' : 'Active' }}</div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="{{ route('customers.edit', $customer) }}" class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                                <form action="{{ route('customers.toggle-status', $customer->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" onclick="return confirm('{{ $customer->trashed() ? 'Activate this customer?' : 'Deactivate this customer?' }}')" class="{{ $customer->trashed() ? 'btn-primary-sm' : 'btn-muted-sm' }}">{{ $customer->trashed() ? 'Activate' : 'Deactivate' }}</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            No customers available.
                        </div>
                    @endforelse
                </div>
                <div class="hidden md:block app-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Code</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Name</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Email/Phone</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Country</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Company</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Status</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">
                                        Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($customers as $customer)
                                    @php($isActive = ! $customer->trashed())
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $customer->code }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                            {{ $customer->name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                            {{ $customer->email ?? '-' }} <br>
                                            {{ $customer->phone ?? '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $customer->country ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $customer->company_name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-center text-sm">
                                            <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $isActive ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 'bg-gray-200 text-gray-700 dark:bg-gray-700/60 dark:text-gray-300' }}">
                                                {{ $isActive ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm actions-compact">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('customers.edit', $customer) }}" class="btn-secondary-sm" title="Edit" aria-label="Edit"><i class="fa-solid fa-pen"></i><span class="sr-only">Edit</span></a>
                                                <form action="{{ route('customers.toggle-status', $customer->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" onclick="return confirm('{{ $isActive ? 'Deactivate this customer?' : 'Activate this customer?' }}')" class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }}">{{ $isActive ? 'Deactivate' : 'Activate' }}</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7"
                                            class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No
                                            customers available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div>{{ $customers->links() }}</div>
            </div>
        </div>
    </div>
@endsection






