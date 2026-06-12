@extends('layouts.master')
@section('page_title', ui_phrase('Inquiry Detail'))
@section('page_subtitle', ui_phrase('Review complete inquiry information.'))
@section('page_actions')
    @can('update', $inquiry)
        @if (! $inquiry->hasLinkedQuotation() && ! $inquiry->isFinal())
            <a href="{{ route('inquiries.edit', $inquiry) }}" class="btn-secondary">
                {{ ui_phrase('Edit Inquiry') }}
            </a>
        @endif
    @endcan
    @php
        $currentUserId = (int) (auth()->id() ?? 0);
        $handlerIdForGenerate = (int) ($inquiry->handled_by ?? $inquiry->assigned_to ?? 0);
        $hasLinkedQuotation = $inquiry->hasLinkedQuotation();
        $canProcessInquiry = ! $inquiry->isFinal() && ($handlerIdForGenerate <= 0 || $handlerIdForGenerate === $currentUserId);
    @endphp
    @if ($canProcessInquiry)
        <a href="{{ route('itineraries.create', ['inquiry_id' => $inquiry->id]) }}" class="btn-primary">
            <i class="fa-solid fa-route"></i>
            <span>{{ ui_phrase('Create Itinerary') }}</span>
        </a>
    @endif
    @if (! $hasLinkedQuotation && (auth()->user()?->can('module.quotations.create') ?? false) && $canProcessInquiry)
        <a href="{{ route('quotations.create', ['inquiry_id' => $inquiry->id]) }}" class="btn-secondary">
            <i class="fa-solid fa-file-invoice-dollar"></i>
            <span>{{ ui_phrase('Generate Quotation') }}</span>
        </a>
    @endif
    <a href="{{ route('inquiries.index') }}" class="btn-ghost" data-page-back-action>{{ ui_phrase('Back') }}</a>
@endsection
@section('content')
    <div class="module-page module-page--inquiries">
        @php
            $inquiryWorkflowSteps = [
                ['key' => 'new_request', 'label' => ui_phrase('Inquiry')],
                ['key' => 'quotation_in_progress', 'label' => ui_phrase('Quotation')],
                ['key' => 'quotation_final', 'label' => ui_phrase('Approved / Closed')],
            ];
            $inquiryCurrentStep = (string) ($inquiry->status ?? 'new_request');
            $relatedItineraries = collect($itineraries ?? []);
            $relatedQuotations = collect($quotations ?? []);
            $hasRelatedRecords = $relatedItineraries->isNotEmpty() || $relatedQuotations->isNotEmpty();
        @endphp
        @if ($errors->any())
            <div
                class="rounded-lg mb-6 border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700 dark:border-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                {{ $errors->first() }}
            </div>
        @endif
        <div class="module-grid-8-4">
            <div class="module-grid-side order-2">
                @if ($hasRelatedRecords)
                    <div class="app-card p-5">
                        <div class="space-y-4">
                            @if ($relatedItineraries->isNotEmpty())
                                <div>
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ ui_phrase('Related Itineraries') }}</h3>
                                    <div class="mt-2 space-y-2">
                                        @foreach ($relatedItineraries as $itinerary)
                                            @php
                                                $breakDayCount = (int) collect($itinerary->dayPoints ?? [])
                                                    ->filter(fn ($point) => filled($point->break_start_time) && filled($point->break_end_time))
                                                    ->count();
                                            @endphp
                                            <a href="{{ route('itineraries.show', $itinerary) }}"
                                                class="block rounded-lg border border-gray-200 px-3 py-2 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900/30">
                                                <div class="flex items-center justify-between gap-3">
                                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                                        {{ $itinerary->title ?: '-' }}
                                                    </p>
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                                        {{ (int) ($itinerary->duration_days ?? 0) }}D
                                                    </span>
                                                </div>
                                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                                    {{ ui_phrase('Break Time') }}:
                                                    {{ $breakDayCount > 0 ? $breakDayCount . ' day(s)' : '-' }}
                                                </p>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if ($relatedQuotations->isNotEmpty())
                                <div>
                                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                        {{ ui_phrase('Related Quotations') }}</h3>
                                    <div class="mt-2 space-y-2">
                                        @foreach ($relatedQuotations as $quotation)
                                            @php
                                                $quotationCardClass = 'block rounded-lg border border-gray-200 px-3 py-2 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-900/30';
                                            @endphp
                                            @if ($quotation->trashed())
                                                <div class="{{ $quotationCardClass }}">
                                            @else
                                                <a href="{{ route('quotations.show', $quotation) }}" class="{{ $quotationCardClass }}">
                                            @endif
                                                <div class="flex items-center justify-between gap-3">
                                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100">
                                                        {{ $quotation->order_number ?: ($quotation->quotation_number ?: '-') }}</p>
                                                    <x-status-badge :status="$quotation->status" size="xs" />
                                                </div>
                                            @if ($quotation->trashed())
                                                </div>
                                            @else
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
                <div class="app-card p-5">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
                            {{ ui_phrase('Activity Timeline') }}</h2>
                        <p class="text-sm text-gray-600 dark:text-gray-300">{{ ui_phrase('tracking changes') }}</p>
                    </div>
                    <x-activity-timeline :activities="$activities" />
                </div>
            </div>
            <div class="module-grid-main order-1">
                <x-workflow-stepper :steps="$inquiryWorkflowSteps" :current="$inquiryCurrentStep" :title="ui_phrase('Workflow Progress')" />
                <div class="app-card p-5">
                    <h2 class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ ui_phrase('Inquiry Overview') }}
                    </h2>
                    <dl class="mt-4 grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Customer') }}:</dt>
                            <dd class="mt-1 font-medium text-gray-800 dark:text-gray-100">
                                ({{ $inquiry->customer->code ?? '-' }}) {{ $inquiry->customer->name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Status') }}:</dt>
                            <dd class="mt-1"><x-status-badge :status="$inquiry->status" size="xs" /></dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Priority') }}:</dt>
                            <dd class="mt-1 font-medium text-gray-800 dark:text-gray-100">
                                {{ ui_phrase((string) $inquiry->priority) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Assigned To') }}:</dt>
                            <dd class="mt-1 font-medium text-gray-800 dark:text-gray-100">
                                {{ ui_user_name($inquiry->handledBy ?? $inquiry->assignedTo) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Source') }}:</dt>
                            <dd class="mt-1 font-medium text-gray-800 dark:text-gray-100">
                                {{ $inquiry->source ? ui_phrase((string) $inquiry->source) : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Deadline') }}:</dt>
                            <dd class="mt-1 font-medium text-gray-800 dark:text-gray-100">
                                {{ $inquiry->deadline?->format('Y-m-d') ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Reminder Email') }}:</dt>
                            <dd class="mt-1 font-medium text-gray-800 dark:text-gray-100">
                                {{ $inquiry->reminder_enabled ? ui_phrase('Enabled') : ui_phrase('Disabled') }}</dd>
                        </div>
                        <div class="md:col-span-2">
                            <dt class="text-gray-500 dark:text-gray-400">{{ ui_phrase('Notes') }}:</dt>
                            <dd class="mt-1 font-medium text-gray-800 dark:text-gray-100">{!! $inquiry->notes ?: '-' !!}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection
