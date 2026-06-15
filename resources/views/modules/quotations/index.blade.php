@extends('layouts.master')
@section('page_title', $isMyQuotationPage ? ui_phrase('My Quotations') : ui_phrase('Quotations'))
@section('page_subtitle', $isMyQuotationPage
    ? ui_phrase('Monitor your active quotation pipeline and follow-up actions.')
    : ($bookingsModuleEnabled
        ? ui_phrase('Manage quotation lifecycle from draft to conversion.')
        : ui_phrase('Manage quotation lifecycle from draft to approval and downstream billing.')))
@section('page_actions')
    <a href="{{ route('quotations.export', array_merge(request()->only(['q', 'per_page']), ['scope' => $exportScope])) }}"
        class="btn-secondary">{{ ui_phrase('Export CSV') }}</a>
    @if ($isMyQuotationPage)
        <a href="{{ route('quotations.index') }}" class="btn-outline">{{ ui_phrase('Accepted/Converted List') }}</a>
    @else
        <a href="{{ route('quotations.my') }}" class="btn-outline">{{ ui_phrase('My Quotations') }}</a>
    @endif
    <a href="{{ route('quotations.create') }}" class="btn-primary">{{ ui_phrase('Add Quotation') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--quotations" data-service-filter-page data-page-spinner="off">
        <div class="module-grid-main" data-service-filter-results>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <x-ui.metric-card :title="ui_phrase('Total Quotations')" :value="(int) ($quotationMetrics['total'] ?? 0)" icon="fa-solid fa-file-invoice" />
                <x-ui.metric-card :title="ui_phrase('Need Validation')" :value="(int) ($quotationMetrics['need_validation'] ?? 0)" icon="fa-solid fa-hourglass-half" />
                <x-ui.metric-card :title="ui_phrase('Sent')" :value="(int) ($quotationMetrics['sent'] ?? 0)" icon="fa-solid fa-paper-plane" />
                <x-ui.metric-card :title="ui_phrase('Approved')" :value="(int) ($quotationMetrics['approved'] ?? 0)" icon="fa-solid fa-thumbs-up" />
            </div>

            <section>
                <div class="md:hidden space-y-3">
                    @forelse ($activeQuotationRows as $row)
                        @php($quotation = $row['quotation'])
                        <div class="app-card relative p-4 pt-5">
                            <div class="absolute right-3 top-3 z-10">
                                <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                    <a href="{{ $row['show_url'] }}"
                                        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                        <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                        <span>{{ ui_phrase('View') }}</span>
                                    </a>
                                    @if ($row['can_edit'])
                                        <a href="{{ $row['edit_url'] }}"
                                            class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                            <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                            <span>{{ ui_phrase('Edit') }}</span>
                                        </a>
                                    @endif
                                    @if ($row['can_open_pdf'])
                                        <a href="{{ $row['pdf_url'] }}" target="_blank" rel="noopener"
                                            class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                            <i class="fa-solid fa-file-pdf w-4 text-gray-500 dark:text-gray-400"></i>
                                            <span>{{ ui_phrase('PDF') }}</span>
                                        </a>
                                    @endif
                                    @if ($row['can_delete'] && $canManageActivationActions)
                                        <div class="my-1 border-t border-gray-200 dark:border-gray-700"></div>
                                        <x-ui.confirm-action :action="$row['toggle_url']" method="PATCH" :modal-name="'quotations-index-delete-mobile-' . $quotation->id"
                                            :title="ui_phrase('Delete') . ' ' . ui_phrase('Quotation')" :message="ui_phrase('confirm deactivate')" :notice-message="__('confirm.notification_after_action')" notice-tone="danger"
                                            :confirm-label="ui_phrase('Delete')" :trigger-label="ui_phrase('Delete')" trigger-icon="fa-solid fa-trash w-4"
                                            trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
                                            confirm-class="btn-danger-sm" />
                                    @endif
                                </x-ui.table-action-dropdown>
                            </div>
                            <div class="flex items-start gap-3 pr-12">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        {{ $row['order_number'] }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $row['itinerary_label'] }}
                                        @if ($row['itinerary_id'] > 0)
                                            ({{ ui_phrase('Itinerary') }} #{{ $row['itinerary_id'] }})
                                        @endif
                                    </p>
                                </div>
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <div>{{ ui_phrase('Validity') }}</div>
                                <div><x-ui.date-display :date="$quotation->validity_date" format="Y-m-d" /></div>
                                <div>{{ ui_phrase('Amount') }}</div>
                                <div><x-ui.money :amount="$row['display_final_amount']" :currency="$currentCurrency ?? 'IDR'" /></div>
                                <div>{{ ui_phrase('Created by') }}</div>
                                <div>
                                    <x-masked-user-name :user="$row['creator']" /><br>
                                    <x-local-time :value="$quotation->created_at" />
                                </div>
                                <div>{{ ui_phrase('Handled By') }}</div>
                                <div>{{ $row['handled_by_name'] }}</div>
                                <div>{{ ui_phrase('Status') }}</div>
                                <div><x-ui.status-badge :status="$row['status_badge']" size="xs" /></div>
                            </div>
                        </div>
                    @empty
                        <x-ui.empty-state :title="ui_phrase('No :entity available.', ['entity' => ui_phrase('Quotations')])" :description="ui_phrase('Try changing filter criteria or create a new quotation.')" />
                    @endforelse
                </div>
                <div class="app-card p-3">
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($quotationTabs as $tab)
                            <a href="{{ $tab['url'] }}"
                                class="{{ $tab['is_active'] ? 'bg-blue-800 text-white border-blue-900' : 'bg-white text-gray-700 border-gray-300 dark:bg-gray-900 dark:text-gray-400 dark:border-gray-600' }} inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition hover:border-blue-800 dark:hover:text-white">
                                {{ $tab['label'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
                <div class="app-card p-4 mt-3">
                    <form method="GET" action="{{ route($listRouteName) }}"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4" data-service-filter-form
                        data-filter-min-text="3" data-disable-submit-lock="1" data-page-spinner="off">
                        <input name="q" value="{{ request('q') }}" placeholder="{{ ui_phrase('Search') }}"
                            class="app-input sm:col-span-2 lg:col-span-2" data-service-filter-input
                            data-filter-min-text="3">
                        <select name="status" class="app-input" data-service-filter-input>
                            <option value="">{{ ui_phrase('Status') }}</option>
                            @foreach ($statusFilterOptions as $statusOption)
                                <option value="{{ $statusOption }}" @selected((string) request('status') === (string) $statusOption)>
                                    {{ ui_phrase((string) $statusOption) }}
                                </option>
                            @endforeach
                        </select>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ($perPageOptions as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>
                                    {{ ui_phrase(':size/page', ['size' => $size]) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 lg:col-span-4 filter-actions h-[42px]">
                            <a href="{{ route($listRouteName) }}"
                                class="btn-secondary h-[42px] rounded-[var(--app-radius-sm)] px-4"
                                data-service-filter-reset>{{ ui_phrase('Reset') }}</a>
                        </div>
                    </form>
                </div>
                <div class="hidden md:block app-card overflow-hidden mt-3">
                    <div class="overflow-x-auto">
                        <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="table-header">
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        #</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Number') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Service Date') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Validity') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Created by') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Handled By') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                        {{ ui_phrase('Status') }}</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300 actions-compact">
                                        {{ ui_phrase('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($activeQuotationRows as $row)
                                    @php($quotation = $row['quotation'])
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                            {{ $row['row_number'] }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                            <div class="flex flex-col items-start">
                                                <span>{{ $row['order_number'] }}</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $row['itinerary_label'] }}
                                                    @if ($row['itinerary_id'] > 0)
                                                        ({{ ui_phrase('Itinerary') }} #{{ $row['itinerary_id'] }})
                                                    @endif
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <x-ui.date-display :date="$quotation->service_date" format="Y-m-d" /></td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <x-ui.date-display :date="$quotation->validity_date" format="Y-m-d" /></td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <x-masked-user-name :user="$row['creator']" /><br>
                                            <i><x-local-time :value="$quotation->created_at" /></i>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $row['handled_by_name'] }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <x-ui.status-badge :status="$row['status_badge']" size="xs" />
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm actions-compact">
                                            <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                                <a href="{{ $row['show_url'] }}"
                                                    class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                                    <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                                    <span>{{ ui_phrase('View') }}</span>
                                                </a>
                                                @if ($row['can_edit'])
                                                    <a href="{{ $row['edit_url'] }}"
                                                        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                                        <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                                        <span>{{ ui_phrase('Edit') }}</span>
                                                    </a>
                                                @endif
                                                @if ($row['can_open_pdf'])
                                                    <a href="{{ $row['pdf_url'] }}"
                                                        target="_blank" rel="noopener"
                                                        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                                        <i class="fa-solid fa-file-pdf w-4 text-gray-500 dark:text-gray-400"></i>
                                                        <span>{{ ui_phrase('PDF') }}</span>
                                                    </a>
                                                @endif
                                                @if ($row['can_delete'] && $canManageActivationActions)
                                                    <div class="my-1 border-t border-gray-200 dark:border-gray-700">
                                                    </div>
                                                    <x-ui.confirm-action :action="$row['toggle_url']" method="PATCH"
                                                        :modal-name="'quotations-index-delete-desktop-' . $quotation->id" :title="ui_phrase('Delete') . ' ' . ui_phrase('Quotation')" :message="ui_phrase('confirm deactivate')"
                                                        :notice-message="__('confirm.notification_after_action')" notice-tone="danger" :confirm-label="ui_phrase('Delete')"
                                                        :trigger-label="ui_phrase('Delete')" trigger-icon="fa-solid fa-trash w-4"
                                                        trigger-class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-900/20"
                                                        confirm-class="btn-danger-sm" />
                                                @endif
                                            </x-ui.table-action-dropdown>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-6">
                                            <x-ui.empty-state :title="ui_phrase('No :entity available.', [
                                                'entity' => ui_phrase('Quotations'),
                                            ])" :description="ui_phrase(
                                                'Try changing filter criteria or create a new quotation.',
                                            )" />
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($activeQuotationSection['paginator'] && method_exists($activeQuotationSection['paginator'], 'links'))
                    <div>{{ $activeQuotationSection['paginator']->links() }}</div>
                @endif
            </section>
        </div>
    </div>
@endsection
