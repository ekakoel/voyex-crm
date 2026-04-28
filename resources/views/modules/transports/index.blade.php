@extends('layouts.master')
@section('page_title', ui_phrase('modules_transports_page_title'))
@section('page_subtitle', ui_phrase('modules_transports_page_subtitle'))
@section('page_actions')
    <a href="{{ route('transports.create') }}" class="btn-primary">{{ ui_phrase('modules_transports_add_transport') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--transports" data-service-filter-page data-page-spinner="off">
        <x-index-stats :cards="$statsCards ?? []" />
        <div class="module-grid-3-9">
            <aside class="module-grid-side space-y-4">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('common_filters') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('index_refine_list_quickly') }}</p>
                    </div>
                    <form method="GET" action="{{ route('transports.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-service-filter-form data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="q" value="{{ request('q') }}" placeholder="{{ ui_phrase('modules_transports_search') }}" class="app-input sm:col-span-2" data-service-filter-input>
                        <select name="vendor_id" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('common_all_vendors') }}</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>{{ $vendor->name }}</option>
                            @endforeach
                        </select>
                        <select name="transport_type" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('common_all_types') }}</option>
                            @foreach ($types as $type)
                                <option value="{{ $type }}" @selected((string) request('transport_type') === (string) $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                            @endforeach
                        </select>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ([10,25,50,100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ ui_phrase('index_per_page_option', ['size' => $size]) }}</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <a href="{{ route('transports.index') }}" class="btn-ghost" data-service-filter-reset>{{ ui_phrase('common_reset') }}</a>
                        </div>
                    </form>
                </div>
            </aside>
            <div class="module-grid-main" data-service-filter-results>
        @if (session('success'))
            <div class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">{{ session('success') }}</div>
        @endif
        <div class="hidden md:block app-card overflow-hidden">
            <div class="overflow-x-auto">
            <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">#</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('common_service') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('common_type') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('modules_transports_unit_spec') }}</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('modules_transports_rate_per_day') }}</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ ui_phrase('common_status') }}</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">{{ ui_phrase('common_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @forelse ($transports as $index=>$transport)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            @php($isActive = ! $transport->trashed())
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">{{ ++$index }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                <div>{{ $transport->name }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $transport->vendor?->name ?? '-' }}</div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ ucfirst(str_replace('_', ' ', $transport->transport_type)) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                {{ $transport->brand_model ?: '-' }}<br>
                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ (int) ($transport->seat_capacity ?? 0) }} seats</span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                @if ($transport->contract_rate !== null)
                                    <div>
                                        Contract: <x-money :amount="(float) $transport->contract_rate" currency="IDR" />
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Markup: {{ ($transport->markup_type ?? 'fixed') === 'percent' ? rtrim(rtrim(number_format((float) ($transport->markup ?? 0), 2, '.', ''), '0'), '.') . '%' : \App\Support\Currency::format((float) ($transport->markup ?? 0), 'IDR') }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        Publish: <x-money :amount="(float) $transport->publish_rate" currency="IDR" />
                                    </div>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-sm">
                                <x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                            </td>
                            <td class="px-4 py-3 text-right text-sm actions-compact">
    <div class="flex items-center justify-end gap-2">
        <a href="{{ route('transports.show', $transport) }}" class="btn-outline-sm" title="{{ ui_phrase('common_view') }}" aria-label="{{ ui_phrase('common_view') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ ui_phrase('common_view') }}</span></a>
                                <a href="{{ route('transports.edit', $transport) }}"  class="btn-secondary-sm" title="{{ ui_phrase('common_edit') }}" aria-label="{{ ui_phrase('common_edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('common_edit') }}</span></a>
                                <form action="{{ route('transports.toggle-status', $transport->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" onclick="return confirm('{{ $isActive ? ui_phrase('modules_transports_confirm_deactivate') : ui_phrase('modules_transports_confirm_activate') }}')" class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }}">{{ $isActive ? ui_phrase('common_deactivate') : ui_phrase('common_activate') }}</button>
                                </form>
    </div>
</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('index_no_data_available', ['entity' => ui_phrase('entities_transport_services')]) }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
        <div class="md:hidden space-y-3">
            @forelse ($transports as $transport)
                <div class="app-card p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $transport->code }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $transport->name }}</p>
                        </div>
                        <span class="text-xs font-medium rounded-full bg-gray-100 px-2 py-0.5 text-gray-700 dark:bg-gray-900/40 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $transport->transport_type)) }}</span>
                    </div>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                        <div>{{ ui_phrase('common_provider') }}</div>
                        <div>{{ $transport->vendor?->name ?? '-' }}</div>
                        <div>{{ ui_phrase('common_unit') }}</div>
                        <div>{{ $transport->brand_model ?: '-' }}</div>
                        <div>{{ ui_phrase('common_rate') }}</div>
                        <div>
                            @if ($transport->contract_rate !== null)
                                <div><x-money :amount="(float) $transport->contract_rate" currency="IDR" /></div>
                                <div class="text-gray-500 dark:text-gray-400">
                                    {{ ($transport->markup_type ?? 'fixed') === 'percent' ? rtrim(rtrim(number_format((float) ($transport->markup ?? 0), 2, '.', ''), '0'), '.') . '%' : \App\Support\Currency::format((float) ($transport->markup ?? 0), 'IDR') }}
                                </div>
                            @else
                                -
                            @endif
                        </div>
                        <div>{{ ui_phrase('common_status') }}</div>
                        <div><x-status-badge :status="$transport->trashed() ? 'inactive' : 'active'" size="xs" /></div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <a href="{{ route('transports.show', $transport) }}" class="btn-outline-sm" title="{{ ui_phrase('common_view') }}" aria-label="{{ ui_phrase('common_view') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ ui_phrase('common_view') }}</span></a>
                        <a href="{{ route('transports.edit', $transport) }}" class="btn-secondary-sm" title="{{ ui_phrase('common_edit') }}" aria-label="{{ ui_phrase('common_edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ ui_phrase('common_edit') }}</span></a>
                        <form action="{{ route('transports.toggle-status', $transport->id) }}" method="POST" class="inline">
                            @csrf
                            @method('PATCH')
                            <button type="submit" onclick="return confirm('{{ $transport->trashed() ? ui_phrase('modules_transports_confirm_activate_mobile') : ui_phrase('modules_transports_confirm_deactivate_mobile') }}')" class="{{ $transport->trashed() ? 'btn-primary-sm' : 'btn-muted-sm' }}">{{ $transport->trashed() ? ui_phrase('common_activate') : ui_phrase('common_deactivate') }}</button>
                        </form>
                    </div>
                </div>
            @empty
                <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('index_no_data_available', ['entity' => ui_phrase('entities_transport_services')]) }}</div>
            @endforelse
        </div>
        <div>{{ $transports->links() }}</div>
            </div>
        </div>
</div>
@endsection



