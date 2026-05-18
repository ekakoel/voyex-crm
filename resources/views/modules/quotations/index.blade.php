@extends('layouts.master')
@php
    $isMyQuotationPage = (bool) ($isMyQuotationPage ?? false);
    $listRouteName = (string) ($listRouteName ?? 'quotations.index');
    $exportScope = (string) ($exportScope ?? ($isMyQuotationPage ? 'my' : 'published'));
    $statusFilterOptions = collect($statusFilterOptions ?? \App\Models\Quotation::STATUS_OPTIONS)->values();
    $showNeedsMyApproval = (bool) ($showNeedsMyApproval ?? false);
    $upcomingQuotations = $upcomingQuotations ?? null;
    $expiredQuotations = $expiredQuotations ?? null;
    $finalQuotations = $finalQuotations ?? null;
    $quotationSections = [
        [
            'key' => 'upcoming',
            'title' => ui_phrase('Upcoming Quotations'),
            'items' => $upcomingQuotations,
        ],
        [
            'key' => 'passed',
            'title' => ui_phrase('Passed Quotation'),
            'items' => $expiredQuotations,
        ],
        [
            'key' => 'final',
            'title' => ui_phrase('Final Quotations'),
            'items' => $finalQuotations,
        ],
    ];
    $activeQuotationTab = (string) request('tab', 'upcoming');
    $availableTabKeys = collect($quotationSections)->pluck('key')->all();
    if (!in_array($activeQuotationTab, $availableTabKeys, true)) {
        $activeQuotationTab = 'upcoming';
    }
@endphp
@section('page_title', $isMyQuotationPage ? ui_phrase('my page title') : ui_phrase('page title'))
@section('page_subtitle', $isMyQuotationPage ? ui_phrase('my page subtitle') : ui_phrase('page subtitle'))
@section('page_actions')
    <a href="{{ route('quotations.export', array_merge(request()->only(['q', 'per_page', 'needs_my_approval']), ['scope' => $exportScope])) }}"
        class="btn-secondary">{{ ui_phrase('Export CSV') }}</a>
    @if ($isMyQuotationPage)
        <a href="{{ route('quotations.index') }}" class="btn-outline">{{ ui_phrase('Approved/Final List') }}</a>
    @else
        <a href="{{ route('quotations.my') }}" class="btn-outline">{{ ui_phrase('My Quotations') }}</a>
    @endif
    <a href="{{ route('quotations.create') }}" class="btn-primary">{{ ui_phrase('Add Quotation') }}</a>
@endsection
@section('content')
    <div class="space-y-6 module-page module-page--quotations" data-service-filter-page data-page-spinner="off">
        @if ($showNeedsMyApproval && request()->boolean('needs_my_approval'))
            <div class="flex items-center">
                <span
                    class="inline-flex items-center gap-2 rounded-full border border-sky-300 bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300">
                    <i class="fa-solid fa-bell"></i>
                    {{ ui_phrase('Needs My Approval') }}
                </span>
            </div>
        @endif
        <div class="module-grid-9-3">
            <aside class="module-grid-side">
                @include('components.module-index-sidebar-info')
                <section class="app-card p-4">
                    <div class="mb-3">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Quotation Logs') }}</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('Latest quotation activity updates.') }}</p>
                    </div>
                    <div class="space-y-2">
                        @forelse (($quotationLogs ?? collect()) as $log)
                            @php
                                $logUser = trim((string) ($log->user?->displayNameFor(auth()->user()) ?? $log->user?->name ?? ui_phrase('System')));
                                $logAction = trim((string) ($log->action ?? 'updated'));
                                $logActionLabel = \Illuminate\Support\Str::headline(str_replace('_', ' ', $logAction));
                                $logSubjectId = (int) ($log->subject_id ?? 0);
                                $logDateTime = optional($log->created_at)->format('d M Y H:i') ?? '-';
                            @endphp
                            <div class="flex items-center gap-2 text-xs text-gray-700 dark:text-gray-200">
                                <span class="min-w-0 truncate">{{ $logUser }}, {{ $logActionLabel }} {{ ui_phrase('quotation id') }} {{ $logSubjectId > 0 ? $logSubjectId : '-' }}</span>
                                <span class="min-w-6 flex-1 border-b border-dotted border-gray-300 dark:border-gray-600"></span>
                                <span class="shrink-0 text-gray-500 dark:text-gray-400">({{ $logDateTime }})</span>
                            </div>
                        @empty
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ ui_phrase('No quotation logs yet.') }}</p>
                        @endforelse
                    </div>
                </section>
            </aside>
            <div class="module-grid-main" data-service-filter-results>
                <div class="app-card p-5">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Filters') }}</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ ui_phrase('Refine your list quickly.') }}</p>
                    </div>
                    <form method="GET" action="{{ route($listRouteName) }}"
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2" data-service-filter-form data-disable-submit-lock="1"
                        data-page-spinner="off">
                        <input name="q" value="{{ request('q') }}" placeholder="{{ ui_phrase('search') }}"
                            class="sm:col-span-2 app-input" data-service-filter-input>
                        <select name="per_page" class="app-input" data-service-filter-input>
                            @foreach ([10, 25, 50, 100] as $size)
                                <option value="{{ $size }}" @selected((string) request('per_page', 10) === (string) $size)>{{ ui_phrase(':size/page', ['size' => $size]) }}
                                </option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-2 sm:col-span-2 filter-actions">
                            <a href="{{ route($listRouteName) }}" class="btn-ghost" data-service-filter-reset>{{ ui_phrase('Reset') }}</a>
                        </div>
                    </form>
                </div>
                @if (session('success'))
                    <div
                        class="rounded-lg mb-6 border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300">
                        {{ session('success') }}
                    </div>
                @endif
                @if (session('error'))
                    <div
                        class="rounded-lg mb-6 border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                        {{ session('error') }}
                    </div>
                @endif
                @if ($showNeedsMyApproval && request()->boolean('needs_my_approval'))
                    <div
                        class="rounded-lg mb-6 border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-700 dark:border-sky-700 dark:bg-sky-900/20 dark:text-sky-300">
                        {{ ui_phrase('showing requires approval') }}
                    </div>
                @endif
                <div class="app-card p-3">
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach ($quotationSections as $section)
                            @php
                                $isActiveTab = $activeQuotationTab === $section['key'];
                                $tabUrl = route($listRouteName, array_merge(request()->except(['upcoming_page', 'expired_page', 'final_page']), ['tab' => $section['key']]));
                            @endphp
                            <a href="{{ $tabUrl }}"
                                class="inline-flex items-center rounded-md border px-3 py-1.5 text-xs font-semibold transition {{ $isActiveTab ? 'border-slate-700 bg-slate-700 text-white dark:border-slate-200 dark:bg-slate-200 dark:text-slate-900' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800' }}">
                                {{ $section['title'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
                @foreach ($quotationSections as $section)
                    @php
                        if ($section['key'] !== $activeQuotationTab) {
                            continue;
                        }
                        $sectionItems = $section['items'];
                    @endphp
                    <section>
                        <div class="md:hidden space-y-3">
                    @forelse ($sectionItems ?? [] as $quotation)
                        <div class="app-card p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                        {{ $quotation->order_number ?: '-' }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $quotation->itinerary?->title ?: '-' }}
                                    </p>
                                    @if ($showNeedsMyApproval && (bool) ($quotation->needs_my_approval_badge ?? false))
                                        <span
                                            class="mt-1 inline-flex items-center rounded-full border border-amber-300 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                                                    {{ ui_phrase('Need Approval') }}
                                                </span>
                                            @endif
                                </div>
                                <x-status-badge :status="$quotation->trashed() ? 'inactive' : $quotation->status" size="xs" />
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600 dark:text-gray-300">
                                <div>{{ ui_phrase('Validity') }}</div>
                                <div>{{ $quotation->validity_date?->format('Y-m-d') ?? '-' }}</div>
                                <div>{{ ui_phrase('Amount') }}</div>
                                <div><x-money :amount="$quotation->display_final_amount ?? 0" :currency="$currentCurrency ?? 'IDR'" /></div>
                                <div>{{ ui_phrase('Created by') }}</div>
                                <div>
                                    <x-masked-user-name :user="$quotation->creator" /><br>
                                    <x-local-time :value="$quotation->created_at" />
                                </div>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <a href="{{ route('quotations.show', $quotation) }}" class="btn-ghost-sm" title="{{ ui_phrase('View') }}"
                                    aria-label="{{ ui_phrase('View') }}"><i class="fa-solid fa-eye"></i><span class="sr-only">{{ ui_phrase('View') }}</span></a>
                                @can('update', $quotation)
                                    @if (($quotation->status ?? '') !== 'final')
                                        <a href="{{ route('quotations.edit', $quotation) }}" class="btn-secondary-sm"
                                            title="{{ ui_phrase('Edit') }}" aria-label="{{ ui_phrase('Edit') }}"><i class="fa-solid fa-pen"></i><span
                                                class="sr-only">{{ ui_phrase('Edit') }}</span></a>
                                    @endif
                                @endcan
                                @if (in_array(($quotation->status ?? ''), ['approved', 'final'], true))
                                    <a href="{{ route('quotations.pdf', $quotation) }}" target="_blank" rel="noopener"
                                        class="btn-outline-sm">PDF</a>
                                @endif
                                @can('delete', $quotation)
                                    @if (!in_array($quotation->status ?? '', ['approved', 'final'], true))
                                        <form action="{{ route('quotations.toggle-status', $quotation->id) }}" method="POST"
                                            class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                onclick="return confirm('{{ ui_phrase('confirm deactivate') }}')"
                                                class="btn-ghost-sm text-rose-600 hover:text-rose-700 dark:text-rose-300 dark:hover:text-rose-200"
                                                title="{{ ui_phrase('Delete') }}" aria-label="{{ ui_phrase('Delete') }}">
                                                <i class="fa-solid fa-trash"></i><span class="sr-only">{{ ui_phrase('Delete') }}</span>
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </div>
                    @empty
                        <div class="app-card p-6 text-center text-sm text-gray-500 dark:text-gray-400">
                            {{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Quotations')]) }}
                        </div>
                    @endforelse
                </div>
                <div class="hidden md:block app-card overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="app-table divide-y divide-gray-200 dark:divide-gray-700 text-sm">
                            <thead>
                                <tr>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        #</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        {{ ui_phrase('Number') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        {{ ui_phrase('Service Date') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        {{ ui_phrase('Validity') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        {{ ui_phrase('Created by') }}</th>
                                    <th
                                        class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">
                                        {{ ui_phrase('Status') }}</th>
                                    <th
                                        class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300 actions-compact">
                                        {{ ui_phrase('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                                @forelse ($sectionItems ?? [] as $index=>$quotation)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">{{ ++$index }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-800 dark:text-gray-100">
                                            <div class="flex flex-col items-start">
                                                <span>{{ $quotation->order_number ?: '-' }}</span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $quotation->itinerary?->title ?: '-' }}</span>
                                                @if ($showNeedsMyApproval && (bool) ($quotation->needs_my_approval_badge ?? false))
                                                    <span
                                                        class="mt-1 inline-flex items-center rounded-full border border-amber-300 bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
                                                        {{ ui_phrase('Need Approval') }}
                                                    </span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $quotation->service_date?->format('Y-m-d') ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            {{ $quotation->validity_date?->format('Y-m-d') ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <x-masked-user-name :user="$quotation->creator" /><br>
                                            <i><x-local-time :value="$quotation->created_at" /></i>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-200">
                                            <x-status-badge :status="$quotation->trashed() ? 'inactive' : $quotation->status" size="xs" />
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm actions-compact">
                                            <div class="flex items-center justify-end gap-2">
                                                <a href="{{ route('quotations.show', $quotation) }}" class="btn-outline-sm"
                                                    title="{{ ui_phrase('View') }}" aria-label="{{ ui_phrase('View') }}"><i class="fa-solid fa-eye"></i><span
                                                        class="sr-only">{{ ui_phrase('View') }}</span></a>
                                                @can('update', $quotation)
                                                    @if (($quotation->status ?? '') !== 'final')
                                                        <a href="{{ route('quotations.edit', $quotation) }}"
                                                            class="btn-secondary-sm" title="{{ ui_phrase('Edit') }}" aria-label="{{ ui_phrase('Edit') }}"><i
                                                                class="fa-solid fa-pen"></i><span
                                                                class="sr-only">{{ ui_phrase('Edit') }}</span></a>
                                                    @endif
                                                @endcan
                                                @if (in_array(($quotation->status ?? ''), ['approved', 'final'], true))
                                                    <a href="{{ route('quotations.pdf', $quotation) }}" target="_blank"
                                                        rel="noopener" class="btn-outline-sm">PDF</a>
                                                @endif
                                                @can('delete', $quotation)
                                                    @if (!in_array($quotation->status ?? '', ['approved', 'final'], true))
                                                        <form action="{{ route('quotations.toggle-status', $quotation->id) }}"
                                                            method="POST" class="inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit"
                                                                onclick="return confirm('{{ ui_phrase('confirm deactivate') }}')"
                                                                class="btn-ghost-sm text-rose-600 hover:text-rose-700 dark:text-rose-300 dark:hover:text-rose-200"
                                                                title="{{ ui_phrase('Delete') }}" aria-label="{{ ui_phrase('Delete') }}">
                                                                <i class="fa-solid fa-trash"></i><span class="sr-only">{{ ui_phrase('Delete') }}</span>
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7"
                                            class="px-4 py-6 text-center text-sm text-gray-500 dark:text-gray-400">No
                                            {{ ui_phrase('No :entity available.', ['entity' => ui_phrase('Quotations')]) }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($sectionItems && method_exists($sectionItems, 'links'))
                    <div>{{ $sectionItems->links() }}</div>
                @endif
                    </section>
                @endforeach
            </div>
        </div>
    </div>
@endsection
