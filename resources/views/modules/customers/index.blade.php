@extends('layouts.master')
@section('page_title', __('ui.modules.customers.page_title'))
@section('page_subtitle', __('ui.modules.customers.page_subtitle'))
@section('page_actions')
    <a href="{{ route('customers.import') }}" class="btn-outline">Import CSV</a>
    <a href="{{ route('customers.create') }}" class="btn-primary">
        {{ __('ui.modules.customers.add_customer') }}
    </a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--customers" data-service-filter-page data-page-spinner="off">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="module-grid-3-9">
            <aside class="module-grid-side space-y-4">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.common.filters') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.index.refine_list_quickly') }}</p>
                    </div>
                    <form method="GET" action="{{ route('customers.index') }}" class="grid grid-cols-1 gap-3" data-service-filter-form data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="q" value="{{ request('q') }}"
                            placeholder="{{ __('ui.modules.customers.search') }}" class="app-input" data-service-filter-input>
                        <select name="customer_type" class="app-input" data-service-filter-input>
                            <option value="">{{ __('ui.modules.customers.type') }}</option>
                            @foreach (['individual' => 'Individual', 'company' => 'Company'] as $value => $label)
                                <option value="{{ $value }}" @selected(request('customer_type') === $value)>{{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <x-forms.searchable-select name="country" :options="$countries" :value="request('country')"
                            list-id="country-filter-options" placeholder="Country" />
                        <select name="created_by" class="app-input" data-service-filter-input>
                            <option value="">{{ __('ui.modules.customers.creator') }}</option>
                            @foreach ($creators as $creator)
                                <option value="{{ $creator->id }}" @selected((string) request('created_by') === (string) $creator->id)>{{ $creator->name }}
                                </option>
                            @endforeach
                        </select>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ __('ui.index.per_page_option', ['size' => $size]) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 filter-actions">
                            <a href="{{ route('customers.index') }}" class="btn-ghost" data-service-filter-reset>{{ __('ui.common.reset') }}</a>
                        </div>
                    </form>
                </div>
            </aside>
            <div class="module-grid-main space-y-4" data-service-filter-results>
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
                <div class="hidden md:block app-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        #</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Code</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Name</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        {{ __('ui.modules.customers.email_phone') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        Country</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        {{ __('ui.modules.customers.company') }}</th>
                                    <th
                                        class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        {{ __('ui.common.status') }}</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">
                                        {{ __('ui.common.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($customers as $index => $customer)
                                    @php($isActive = ! $customer->trashed())
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ++$index }}</td>
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
                                            <x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm actions-compact">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('customers.edit', $customer) }}" class="btn-secondary-sm" title="{{ __('ui.common.edit') }}" aria-label="{{ __('ui.common.edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ __('ui.common.edit') }}</span></a>
                                                <form action="{{ route('customers.toggle-status', $customer->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" onclick="return confirm('{{ $isActive ? __('ui.modules.customers.confirm_deactivate') : __('ui.modules.customers.confirm_activate') }}')" class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }}">{{ $isActive ? __('ui.common.deactivate') : __('ui.common.activate') }}</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8"
                                            class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No
                                            {{ __('ui.index.no_data_available', ['entity' => __('ui.entities.customers')]) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
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
                                <div>{{ __('ui.modules.customers.phone') }}</div>
                                <div>{{ $customer->phone ?? '-' }}</div>
                                <div>{{ __('ui.modules.customers.country') }}</div>
                                <div>{{ $customer->country ?? '-' }}</div>
                                <div>{{ __('ui.modules.customers.company') }}</div>
                                <div>{{ $customer->company_name ?? '-' }}</div>
                                <div>{{ __('ui.common.status') }}</div>
                                <div><x-status-badge :status="$customer->trashed() ? 'inactive' : 'active'" size="xs" /></div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="{{ route('customers.edit', $customer) }}" class="btn-secondary-sm" title="{{ __('ui.common.edit') }}" aria-label="{{ __('ui.common.edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ __('ui.common.edit') }}</span></a>
                                <form action="{{ route('customers.toggle-status', $customer->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" onclick="return confirm('{{ $customer->trashed() ? __('ui.modules.customers.confirm_activate') : __('ui.modules.customers.confirm_deactivate') }}')" class="{{ $customer->trashed() ? 'btn-primary-sm' : 'btn-muted-sm' }}">{{ $customer->trashed() ? __('ui.common.activate') : __('ui.common.deactivate') }}</button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ __('ui.index.no_data_available', ['entity' => __('ui.entities.customers')]) }}
                        </div>
                    @endforelse
                </div>
                <div>{{ $customers->links() }}</div>
            </div>
        </div>
    </div>
@endsection







