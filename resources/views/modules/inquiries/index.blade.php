@extends('layouts.master')
@section('page_title', ui_phrase('Inquiries'))
@section('page_subtitle', ui_phrase('Manage inquiry records.'))
@section('page_actions')
    <a href="{{ route('inquiries.create') }}" class="btn-primary">
        {{ ui_phrase('Add Inquiry') }}
    </a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--inquiries" data-service-filter-page data-page-spinner="off">
        <div class="module-grid-main" data-service-filter-results>
            @php
                $tabCounts = $tabCounts ?? ['new' => 0, 'in_progress' => 0, 'archived' => 0];
                $selectedTab = $selectedTab ?? 'new';
                $tabQueryBase = request()->except('tab', 'page');
                $inquiryTabs = [
                    ['key' => 'new', 'label' => ui_phrase('New'), 'count' => (int) ($tabCounts['new'] ?? 0)],
                    [
                        'key' => 'in_progress',
                        'label' => ui_phrase('In Progress'),
                        'count' => (int) ($tabCounts['in_progress'] ?? 0),
                    ],
                    [
                        'key' => 'archived',
                        'label' => ui_phrase('Archived'),
                        'count' => (int) ($tabCounts['archived'] ?? 0),
                    ],
                ];
            @endphp
            <div class="app-card p-3">
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($inquiryTabs as $tab)
                        @php
                            $isActiveTab = $selectedTab === $tab['key'];
                            $tabUrl = route('inquiries.index', array_merge($tabQueryBase, ['tab' => $tab['key']]));
                        @endphp
                        <a href="{{ $tabUrl }}"
                            class="{{ $isActiveTab ? 'bg-blue-800 text-white border-blue-900' : 'bg-white text-gray-700 border-gray-300 dark:bg-gray-900 dark:text-gray-400 dark:border-gray-600' }} inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition hover:border-blue-800 dark:hover:text-white">
                            <span>{{ $tab['label'] }}</span>
                            <span
                                class="{{ $isActiveTab ? 'bg-blue-800/40 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }} rounded-full px-2 py-0.5 text-[11px] font-semibold">{{ $tab['count'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="app-card p-4">
                @include('modules.inquiries.partials.index-filters')
            </div>
            <div class="hidden overflow-hidden md:block">
                <div class="app-card overflow-x-auto">
                    <table class="app-table w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                        <thead class="table-header">
                            <tr>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                    #</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                    {{ ui_phrase('Customer') }}</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                    {{ ui_phrase('Inquiry No') }}</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                    {{ ui_phrase('Created By') }}</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                    {{ ui_phrase('Assigned To') }}</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                    {{ ui_phrase('Deadline') }}</th>
                                <th
                                    class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300">
                                    {{ ui_phrase('Quotation') }}</th>
                                <th
                                    class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-white dark:text-gray-300 actions-compact">
                                    {{ ui_phrase('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($inquiries as $index=>$inquiry)
                                @php
                                    $currentUserId = (int) (auth()->id() ?? 0);
                                    $handlerIdForActions = (int) ($inquiry->handled_by ?? ($inquiry->assigned_to ?? 0));
                                    $linkedQuotation = $inquiry->quotation;
                                    $hasLinkedQuotation = (bool) $linkedQuotation;
                                    $canProcessInquiry =
                                        !$inquiry->isFinal() &&
                                        ($handlerIdForActions <= 0 || $handlerIdForActions === $currentUserId);
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                        {{ $inquiries->firstItem() + $index }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $inquiry->customer->name ?? '-' }}<br>
                                        <x-ui.status-badge :status="(string) ($inquiry->status ?? 'new_request')" size="xs" />
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                        <div class="font-medium text-gray-800 dark:text-gray-100">
                                            {{ $inquiry->inquiry_number }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ ui_phrase((string) $inquiry->priority) }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                        {{ ui_user_name($inquiry->creator) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                        {{ ui_user_name($inquiry->handledBy ?? $inquiry->assignedTo) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                        {{ $inquiry->deadline ? $inquiry->deadline->format('Y-m-d') : '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                        @if ($linkedQuotation)
                                            @php
                                                $quotationNumber = trim(
                                                    (string) ($linkedQuotation->quotation_number ?? ''),
                                                );
                                                $orderNumber = trim((string) ($linkedQuotation->order_number ?? ''));
                                                $quotationSummary =
                                                    ($quotationNumber !== '' ? $quotationNumber : '-') .
                                                    ' | ' .
                                                    ($orderNumber !== '' ? $orderNumber : '-');
                                            @endphp
                                            <div class="inline-flex flex-wrap items-center gap-2">
                                                @if (
                                                    !$linkedQuotation->trashed() &&
                                                        Route::has('quotations.show') &&
                                                        (auth()->user()?->can('module.quotations.access') ?? false))
                                                    <a href="{{ route('quotations.show', $linkedQuotation) }}"
                                                        class="font-medium text-indigo-600 hover:text-indigo-700 hover:underline dark:text-indigo-400">
                                                        {{ $quotationSummary }}
                                                    </a>
                                                @else
                                                    <span
                                                        class="font-medium text-gray-700 dark:text-gray-200">{{ $quotationSummary }}</span>
                                                @endif
                                                <x-ui.status-badge :status="(string) ($linkedQuotation->status ?? 'draft')" size="xs" />
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm actions-compact">
                                        <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                            <a href="{{ route('inquiries.show', $inquiry) }}"
                                                class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                                <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                                <span>{{ ui_phrase('Detail') }}</span>
                                            </a>
                                            @can('update', $inquiry)
                                                @if (!$hasLinkedQuotation && !$inquiry->isFinal())
                                                    <a href="{{ route('inquiries.edit', $inquiry) }}"
                                                        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                                        <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                                        <span>{{ ui_phrase('Edit') }}</span>
                                                    </a>
                                                @endif
                                            @endcan
                                            @if ($canProcessInquiry)
                                                <a href="{{ route('itineraries.create', ['inquiry_id' => $inquiry->id]) }}"
                                                    class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                                    <i class="fa-solid fa-route w-4 text-gray-500 dark:text-gray-400"></i>
                                                    <span>{{ ui_phrase('Create Itinerary') }}</span>
                                                </a>
                                            @endif
                                            @if (!$hasLinkedQuotation && $canProcessInquiry && (auth()->user()?->can('module.quotations.create') ?? false))
                                                <a href="{{ route('quotations.create', ['inquiry_id' => $inquiry->id]) }}"
                                                    class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                                    <i
                                                        class="fa-solid fa-file-invoice-dollar w-4 text-gray-500 dark:text-gray-400"></i>
                                                    <span>{{ ui_phrase('Generate Quotation') }}</span>
                                                </a>
                                            @endif
                                        </x-ui.table-action-dropdown>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6">
                                        <x-module-empty-state :title="ui_phrase('No :entity available.', [
                                            'entity' => ui_phrase('Inquiries'),
                                        ])" :message="ui_phrase('Try changing filter criteria or add a new inquiry.')" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="md:hidden space-y-3">
                @forelse ($inquiries as $inquiry)
                    @php
                        $currentUserId = (int) (auth()->id() ?? 0);
                        $handlerIdForActions = (int) ($inquiry->handled_by ?? ($inquiry->assigned_to ?? 0));
                        $linkedQuotation = $inquiry->quotation;
                        $hasLinkedQuotation = (bool) $linkedQuotation;
                        $canProcessInquiry =
                            !$inquiry->isFinal() &&
                            ($handlerIdForActions <= 0 || $handlerIdForActions === $currentUserId);
                    @endphp
                    <div class="app-card relative p-4 pt-5">
                        <div class="absolute right-3 top-3 z-10">
                            <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                <a href="{{ route('inquiries.show', $inquiry) }}"
                                    class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                    <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                    <span>{{ ui_phrase('Detail') }}</span>
                                </a>
                                @can('update', $inquiry)
                                    @if (!$hasLinkedQuotation && !$inquiry->isFinal())
                                        <a href="{{ route('inquiries.edit', $inquiry) }}"
                                            class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                            <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                            <span>{{ ui_phrase('Edit') }}</span>
                                        </a>
                                    @endif
                                @endcan
                                @if ($canProcessInquiry)
                                    <a href="{{ route('itineraries.create', ['inquiry_id' => $inquiry->id]) }}"
                                        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                        <i class="fa-solid fa-route w-4 text-gray-500 dark:text-gray-400"></i>
                                        <span>{{ ui_phrase('Create Itinerary') }}</span>
                                    </a>
                                @endif
                                @if (!$hasLinkedQuotation && $canProcessInquiry && (auth()->user()?->can('module.quotations.create') ?? false))
                                    <a href="{{ route('quotations.create', ['inquiry_id' => $inquiry->id]) }}"
                                        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                        <i class="fa-solid fa-file-invoice-dollar w-4 text-gray-500 dark:text-gray-400"></i>
                                        <span>{{ ui_phrase('Generate Quotation') }}</span>
                                    </a>
                                @endif
                            </x-ui.table-action-dropdown>
                        </div>
                        <div class="flex items-start gap-3 pr-12">
                            <div>
                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                    {{ $inquiry->inquiry_number }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $inquiry->customer->name ?? '-' }}</p>
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                            <div>{{ ui_phrase('Priority') }}</div>
                            <div>{{ ui_phrase((string) $inquiry->priority) }}</div>
                            <div>{{ ui_phrase('Status') }}</div>
                            <div><x-ui.status-badge :status="(string) ($inquiry->status ?? 'new_request')" size="xs" /></div>
                            <div>{{ ui_phrase('Created By') }}</div>
                            <div>{{ ui_user_name($inquiry->creator) }}</div>
                            <div>{{ ui_phrase('Assigned To') }}</div>
                            <div>{{ ui_user_name($inquiry->handledBy ?? $inquiry->assignedTo) }}</div>
                            <div>{{ ui_phrase('Deadline') }}</div>
                            <div>{{ $inquiry->deadline ? $inquiry->deadline->format('Y-m-d') : '-' }}</div>
                        </div>
                        <div class="mt-3">
                            @if ($linkedQuotation)
                                @php
                                    $quotationNumber = trim((string) ($linkedQuotation->quotation_number ?? ''));
                                    $orderNumber = trim((string) ($linkedQuotation->order_number ?? ''));
                                    $quotationSummary =
                                        ($quotationNumber !== '' ? $quotationNumber : '-') .
                                        ' | ' .
                                        ($orderNumber !== '' ? $orderNumber : '-');
                                @endphp
                                <div
                                    class="inline-flex flex-wrap items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                                    @if (
                                        !$linkedQuotation->trashed() &&
                                            Route::has('quotations.show') &&
                                            (auth()->user()?->can('module.quotations.access') ?? false))
                                        <a href="{{ route('quotations.show', $linkedQuotation) }}"
                                            class="font-medium text-indigo-600 hover:text-indigo-700 hover:underline dark:text-indigo-400">
                                            {{ $quotationSummary }}
                                        </a>
                                    @else
                                        <span
                                            class="font-medium text-gray-700 dark:text-gray-200">{{ $quotationSummary }}</span>
                                    @endif
                                </div>
                            @else
                                -
                            @endif
                        </div>
                    </div>
                @empty
                    <x-module-empty-state :title="ui_phrase('No :entity available.', ['entity' => ui_phrase('Inquiries')])" :message="ui_phrase('Try changing filter criteria or add a new inquiry.')" />
                @endforelse
            </div>
        </div>
        <div>{{ $inquiries->links() }}</div>
    </div>
@endsection
