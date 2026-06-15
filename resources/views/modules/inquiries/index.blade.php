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
            <div class="app-card p-3">
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($inquiryTabs as $tab)
                        <a href="{{ $tab['url'] }}"
                            class="{{ $tab['is_active'] ? 'bg-blue-800 text-white border-blue-900' : 'bg-white text-gray-700 border-gray-300 dark:bg-gray-900 dark:text-gray-400 dark:border-gray-600' }} inline-flex items-center gap-2 rounded-full border px-3 py-1.5 text-xs font-semibold transition hover:border-blue-800 dark:hover:text-white">
                            <span>{{ $tab['label'] }}</span>
                            <span
                                class="{{ $tab['is_active'] ? 'bg-blue-800/40 text-white' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300' }} rounded-full px-2 py-0.5 text-[11px] font-semibold">{{ $tab['count'] }}</span>
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
                            @forelse ($inquiryRows as $row)
                                @php($inquiry = $row['inquiry'])
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                    <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                        {{ $row['row_number'] }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        {{ $row['customer_name'] }}<br>
                                        <x-ui.status-badge :status="$row['status']" size="xs" />
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                        <div class="font-medium text-gray-800 dark:text-gray-100">
                                            {{ $inquiry->inquiry_number }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ ui_phrase($row['priority']) }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                        {{ $row['creator_name'] }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                        {{ $row['assigned_to_name'] }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                        {{ $row['deadline_display'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                        @if ($row['has_linked_quotation'])
                                            <div class="inline-flex flex-wrap items-center gap-2">
                                                @if ($row['can_open_linked_quotation'])
                                                    <a href="{{ $row['quotation_show_url'] }}"
                                                        class="font-medium text-indigo-600 hover:text-indigo-700 hover:underline dark:text-indigo-400">
                                                        {{ $row['quotation_summary'] }}
                                                    </a>
                                                @else
                                                    <span
                                                        class="font-medium text-gray-700 dark:text-gray-200">{{ $row['quotation_summary'] }}</span>
                                                @endif
                                                <x-ui.status-badge :status="$row['quotation_status']" size="xs" />
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm actions-compact">
                                        <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                            <a href="{{ $row['show_url'] }}"
                                                class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                                <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                                <span>{{ ui_phrase('Detail') }}</span>
                                            </a>
                                            @if ($row['can_edit'])
                                                <a href="{{ $row['edit_url'] }}"
                                                    class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                                    <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                                    <span>{{ ui_phrase('Edit') }}</span>
                                                </a>
                                            @endif
                                            @if ($row['can_process'])
                                                <a href="{{ $row['create_itinerary_url'] }}"
                                                    class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                                    <i class="fa-solid fa-route w-4 text-gray-500 dark:text-gray-400"></i>
                                                    <span>{{ ui_phrase('Create Itinerary') }}</span>
                                                </a>
                                            @endif
                                            @if ($row['can_generate_quotation'])
                                                <a href="{{ $row['create_quotation_url'] }}"
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
                @forelse ($inquiryRows as $row)
                    @php($inquiry = $row['inquiry'])
                    <div class="app-card relative p-4 pt-5">
                        <div class="absolute right-3 top-3 z-10">
                            <x-ui.table-action-dropdown :label="ui_phrase('Actions')">
                                <a href="{{ $row['show_url'] }}"
                                    class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                    <i class="fa-solid fa-eye w-4 text-gray-500 dark:text-gray-400"></i>
                                    <span>{{ ui_phrase('Detail') }}</span>
                                </a>
                                @if ($row['can_edit'])
                                    <a href="{{ $row['edit_url'] }}"
                                        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                        <i class="fa-solid fa-pen w-4 text-gray-500 dark:text-gray-400"></i>
                                        <span>{{ ui_phrase('Edit') }}</span>
                                    </a>
                                @endif
                                @if ($row['can_process'])
                                    <a href="{{ $row['create_itinerary_url'] }}"
                                        class="flex w-full items-center gap-2 rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                                        <i class="fa-solid fa-route w-4 text-gray-500 dark:text-gray-400"></i>
                                        <span>{{ ui_phrase('Create Itinerary') }}</span>
                                    </a>
                                @endif
                                @if ($row['can_generate_quotation'])
                                    <a href="{{ $row['create_quotation_url'] }}"
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
                                    {{ $row['customer_name'] }}</p>
                            </div>
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                            <div>{{ ui_phrase('Priority') }}</div>
                            <div>{{ ui_phrase($row['priority']) }}</div>
                            <div>{{ ui_phrase('Status') }}</div>
                            <div><x-ui.status-badge :status="$row['status']" size="xs" /></div>
                            <div>{{ ui_phrase('Created By') }}</div>
                            <div>{{ $row['creator_name'] }}</div>
                            <div>{{ ui_phrase('Assigned To') }}</div>
                            <div>{{ $row['assigned_to_name'] }}</div>
                            <div>{{ ui_phrase('Deadline') }}</div>
                            <div>{{ $row['deadline_display'] }}</div>
                        </div>
                        <div class="mt-3">
                            @if ($row['has_linked_quotation'])
                                <div
                                    class="inline-flex flex-wrap items-center gap-2 text-xs text-gray-600 dark:text-gray-300">
                                    @if ($row['can_open_linked_quotation'])
                                        <a href="{{ $row['quotation_show_url'] }}"
                                            class="font-medium text-indigo-600 hover:text-indigo-700 hover:underline dark:text-indigo-400">
                                            {{ $row['quotation_summary'] }}
                                        </a>
                                    @else
                                        <span
                                            class="font-medium text-gray-700 dark:text-gray-200">{{ $row['quotation_summary'] }}</span>
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
