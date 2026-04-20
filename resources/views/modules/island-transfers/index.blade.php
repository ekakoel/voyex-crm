@extends('layouts.master')

@section('page_title', __('ui.modules.island_transfers.page_title'))
@section('page_subtitle', __('ui.modules.island_transfers.page_subtitle'))
@section('page_actions')
    <a href="{{ route('island-transfers.create') }}" class="btn-primary">{{ __('ui.modules.island_transfers.add_transfer') }}</a>
@endsection

@section('content')
    <div class="space-y-5 module-page module-page--island-transfers" data-service-filter-page data-page-spinner="off">
        <x-index-stats :cards="$statsCards ?? []" />

        <div class="module-grid-3-9">
            <aside class="module-grid-side space-y-4">
                <div class="app-card p-5 space-y-4">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ __('ui.modules.island_transfers.filters_title') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('ui.modules.island_transfers.filters_subtitle') }}</p>
                    </div>

                    <form method="GET" action="{{ route('island-transfers.index') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-service-filter-form data-disable-submit-lock="1" data-page-spinner="off">
                        <select name="vendor_id" class="app-input" data-service-filter-input>
                            <option value="">{{ __('ui.modules.island_transfers.all_vendors') }}</option>
                            @foreach ($vendors as $vendor)
                                <option value="{{ $vendor->id }}" @selected((string) request('vendor_id') === (string) $vendor->id)>{{ $vendor->name }}</option>
                            @endforeach
                        </select>

                        <select name="transfer_type" class="app-input" data-service-filter-input>
                            <option value="">{{ __('ui.modules.island_transfers.all_types') }}</option>
                            @foreach (($transferTypeOptions ?? []) as $type)
                                <option value="{{ $type['value'] }}" @selected((string) request('transfer_type') === (string) $type['value'])>{{ $type['label'] }}</option>
                            @endforeach
                        </select>

                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ __('ui.modules.island_transfers.per_page_option', ['size' => $size]) }}</option>
                            @endforeach
                        </select>

                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <a href="{{ route('island-transfers.index') }}" class="btn-ghost" data-service-filter-reset>{{ __('ui.modules.island_transfers.reset') }}</a>
                        </div>
                    </form>
                </div>
            </aside>

            <div class="module-grid-main" data-service-filter-results>
                @if (session('success'))
                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="hidden md:block app-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.island_transfers.transfer') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.island_transfers.type') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.island_transfers.vendor') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.island_transfers.route') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.island_transfers.duration') }}</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.island_transfers.pricing') }}</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">{{ __('ui.modules.island_transfers.status') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">{{ __('ui.modules.island_transfers.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($islandTransfers as $transfer)
                                    @php($isActive = ! $transfer->trashed())
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $transfer->name }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.types.' . (string) $transfer->transfer_type) }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <div>{{ $transfer->vendor?->name ?? '-' }}</div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ trim((string) (($transfer->vendor?->city ?? '-') . (!empty($transfer->vendor?->province) ? ', '.$transfer->vendor?->province : ''))) }}</div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $transfer->departure_point_name ?: '-' }} -> {{ $transfer->arrival_point_name ?: '-' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">{{ __('ui.modules.island_transfers.duration_short', ['minutes' => (int) ($transfer->duration_minutes ?? 0)]) }}</td>
                                        <td class="px-4 py-3 text-xs text-gray-700 dark:text-gray-200">
                                            <div>{{ __('ui.modules.island_transfers.contract_rate') }}: <x-money :amount="(float) ($transfer->contract_rate ?? 0)" currency="IDR" /></div>
                                            <div>
                                                {{ __('ui.modules.island_transfers.markup') }}:
                                                {{ ($transfer->markup_type ?? 'fixed') === 'percent'
                                                    ? rtrim(rtrim(number_format((float) ($transfer->markup ?? 0), 2, '.', ''), '0'), '.') . '%'
                                                    : \App\Support\Currency::format((float) ($transfer->markup ?? 0), 'IDR') }}
                                            </div>
                                            <div class="text-gray-500 dark:text-gray-400">{{ __('ui.modules.island_transfers.publish_rate') }}: <x-money :amount="(float) ($transfer->publish_rate ?? 0)" currency="IDR" /></div>
                                        </td>
                                        <td class="px-4 py-3 text-center text-sm">
                                            <x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" />
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm actions-compact">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('island-transfers.show', $transfer->id) }}" class="btn-outline-sm">{{ __('ui.modules.island_transfers.view_details') }}</a>
                                                <a href="{{ route('island-transfers.edit', $transfer->id) }}" class="btn-secondary-sm" title="{{ __('ui.modules.island_transfers.edit') }}" aria-label="{{ __('ui.modules.island_transfers.edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ __('ui.modules.island_transfers.edit') }}</span></a>
                                                <form action="{{ route('island-transfers.toggle-status', $transfer->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" onclick="return confirm('{{ $isActive ? __('ui.modules.island_transfers.confirm_deactivate') : __('ui.modules.island_transfers.confirm_activate') }}')" class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }}">
                                                        {{ $isActive ? __('ui.modules.island_transfers.deactivate') : __('ui.modules.island_transfers.activate') }}
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('ui.modules.island_transfers.no_data') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="md:hidden space-y-3">
                    @forelse ($islandTransfers as $transfer)
                        @php($isActive = ! $transfer->trashed())
                        <div class="app-card p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $transfer->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $transfer->vendor?->name ?? '-' }}</p>
                                </div>
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700 dark:bg-gray-900/40 dark:text-gray-300">
                                    {{ __('ui.modules.island_transfers.types.' . (string) $transfer->transfer_type) }}
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <div>{{ __('ui.modules.island_transfers.route') }}</div>
                                <div>{{ $transfer->departure_point_name ?: '-' }} -> {{ $transfer->arrival_point_name ?: '-' }}</div>
                                <div>{{ __('ui.modules.island_transfers.duration') }}</div>
                                <div>{{ __('ui.modules.island_transfers.duration_short', ['minutes' => (int) ($transfer->duration_minutes ?? 0)]) }}</div>
                                <div>{{ __('ui.modules.island_transfers.pricing') }}</div>
                                <div>
                                    <div>{{ __('ui.modules.island_transfers.contract_rate') }}: <x-money :amount="(float) ($transfer->contract_rate ?? 0)" currency="IDR" /></div>
                                    <div>
                                        {{ __('ui.modules.island_transfers.markup') }}:
                                        {{ ($transfer->markup_type ?? 'fixed') === 'percent'
                                            ? rtrim(rtrim(number_format((float) ($transfer->markup ?? 0), 2, '.', ''), '0'), '.') . '%'
                                            : \App\Support\Currency::format((float) ($transfer->markup ?? 0), 'IDR') }}
                                    </div>
                                    <div>{{ __('ui.modules.island_transfers.publish_rate') }}: <x-money :amount="(float) ($transfer->publish_rate ?? 0)" currency="IDR" /></div>
                                </div>
                                <div>{{ __('ui.modules.island_transfers.status') }}</div>
                                <div><x-status-badge :status="$isActive ? 'active' : 'inactive'" size="xs" /></div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="{{ route('island-transfers.show', $transfer->id) }}" class="btn-outline-sm">{{ __('ui.modules.island_transfers.view_details') }}</a>
                                <a href="{{ route('island-transfers.edit', $transfer->id) }}" class="btn-secondary-sm" title="{{ __('ui.modules.island_transfers.edit') }}" aria-label="{{ __('ui.modules.island_transfers.edit') }}"><i class="fa-solid fa-pen"></i><span class="sr-only">{{ __('ui.modules.island_transfers.edit') }}</span></a>
                                <form action="{{ route('island-transfers.toggle-status', $transfer->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" onclick="return confirm('{{ $isActive ? __('ui.modules.island_transfers.confirm_deactivate') : __('ui.modules.island_transfers.confirm_activate') }}')" class="{{ $isActive ? 'btn-muted-sm' : 'btn-primary-sm' }}">
                                        {{ $isActive ? __('ui.modules.island_transfers.deactivate') : __('ui.modules.island_transfers.activate') }}
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">{{ __('ui.modules.island_transfers.no_data') }}</div>
                    @endforelse
                </div>

                <div>{{ $islandTransfers->links() }}</div>
            </div>
        </div>
    </div>
@endsection
